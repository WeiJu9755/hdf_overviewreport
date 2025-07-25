<?php

//error_reporting(E_ALL); 
//ini_set('display_errors', '1');

session_start();

$memberID = $_SESSION['memberID'];
$powerkey = $_SESSION['powerkey'];


require_once '/website/os/Mobile-Detect-2.8.34/Mobile_Detect.php';
$detect = new Mobile_Detect;

if (!($detect->isMobile() && !$detect->isTablet())) {
	$isMobile = "0";
} else {
	$isMobile = "1";
}


$m_location = "/website/smarty/templates/" . $site_db . "/" . $templates;
$m_pub_modal = "/website/smarty/templates/" . $site_db . "/pub_modal";





//載入公用函數
@include_once '/website/include/pub_function.php';

@include_once("/website/class/" . $site_db . "_info_class.php");



$fm = $_GET['fm'];
$ch = $_GET['ch'];
//$project_id = $_GET['project_id'];
//$auth_id = $_GET['auth_id'];

$case_id = $_GET['case_id'];
$now = date("Y-m-d");
$start_date = $_GET['start_date'] ?? '$now';
$end_date = $_GET['end_date'] ?? '$now';


$getteamclass = "/smarty/templates/$site_db/$templates/sub_modal/admin/attendance_ms/getteamclass.php";


$mDB = "";
$mDB = new MywebDB();

$mDB2 = "";
$mDB2 = new MywebDB();

$mDB3 = "";
$mDB3 = new MywebDB();


//載入區域
$Qry = "SELECT region 
FROM CaseManagement 
GROUP BY region 
ORDER BY 
    CASE 
        WHEN region = '北部' THEN 1
        WHEN region = '中部' THEN 2
        WHEN region = '南部' THEN 3
        ELSE 4  -- 其他區域放最後
    END;";

$mDB->query($Qry);


$selected_region = isset($_GET['region']) ? $_GET['region'] : "";

$select_region = "<select class=\"inline form-select \" name=\"region\" id=\"region\" style=\"width:auto;\">";
$select_region .= "<option></option>";

if ($mDB->rowCount() > 0) {
    while ($row = $mDB->fetchRow(2)) {
        $region = $row['region'];
        $selected = ($region == $selected_region) ? "selected" : "";
        $select_region .= "<option value='$region' $selected>$region</option>";
    }
}
$select_region .= "</select>";


//載入工程名稱
$Qry = "SELECT a.case_id,
       a.construction_site,
       b.construction_id AS construction_name,
       b.status1
  FROM construction a
  LEFT JOIN CaseManagement b ON a.case_id = b.case_id
  WHERE b.status1 = '已簽約' AND (b.ContractingModel = '連工帶料(UH)' OR b.ContractingModel = '代工(WH)')
			";

$mDB->query($Qry);


$get_construction_dropdown = isset($_GET['construction_name']) ? $_GET['construction_name'] : '';

$construction_dropdown = "<select class=\"inline form-select\" name=\"construction_name\" id=\"construction_name\" style=\"width:auto;\">";
$construction_dropdown .= "<option value=''></option>";

if ($mDB->rowCount() > 0) {
    while ($row = $mDB->fetchRow(2)) {
        $case_id = $row['case_id'];
        $select_construction_name = $row['construction_name'];
        $selected = ($get_construction_dropdown == $select_construction_name) ? "selected" : "";

        // 工程名稱
        $construction_dropdown .= "<option value='$select_construction_name' $selected>$select_construction_name $case_id</option>";
    }
}

$construction_dropdown .= "</select>";

//載入下包商

$Qry = "SELECT subcontractor_name AS company_name,
       subcontractor_id AS company_id
      
			FROM subcontractor a";

$mDB->query($Qry);

$get_company_name_dropdown = isset($_GET['company_name']) ? $_GET['company_name'] : '';
$show_company_column = ($get_company_name_dropdown !== "") ? "" : "display:none;";

$company_name_dropdown = "<select class=\"inline form-select\" name=\"company_name\" id=\"company_name\" style=\"width:auto;\" onchange=\"this.form.submit()\">";
$company_name_dropdown .= "<option value=''" . ($get_company_name_dropdown === '' ? ' selected' : '') . "></option>"; // 空白選項（真正預設空白）
$company_name_dropdown .= "<option value='all'" . ($get_company_name_dropdown === 'all' ? ' selected' : '') . ">(all)</option>";

if ($mDB->rowCount() > 0) {
    while ($row = $mDB->fetchRow(2)) {
        $company_id = $row['company_id'];
        $select_company_name = $row['company_name'];
        $selected = ($get_company_name_dropdown == $select_company_name) ? "selected" : "";

        // 工程名稱
        $company_name_dropdown .= "<option value='$select_company_name' $selected>$select_company_name $company_id</option>";
    }
}

$company_name_dropdown .= "</select>";

//載入棟別
$Qry = "SELECT pro_id,
       caption AS building -- 棟別
       FROM items
       WHERE pro_id = 'building';
			";

$mDB->query($Qry);


$get_building_dropdown = isset($_GET['building']) ? $_GET['building'] : '';

$building_dropdown = "<select class=\"inline form-select\" name=\"building\" id=\"building\" style=\"width:auto;\">";
$building_dropdown .= "<option></option>";

if ($mDB->rowCount() > 0) {
    while ($row = $mDB->fetchRow(2)) {
        $select_building = $row['building'];
        $selected = ($get_building_dropdown == $select_building) ? "selected" : "";


        // 工程名稱
        $building_dropdown .= "<option value='$select_building' $selected>$select_building</option>";
    }
}

$building_dropdown .= "</select>";



$show_disabled = "style=\"pointer-events: none;\"";

$show_inquiry = "";


//取得案件資料
$Qry = "SELECT DISTINCT 
           a.case_id,
           c.region,
           c.construction_id AS construction_name,
           b.engineering_name,
           e.subcontractor_name,
           d.building,
           f.floor,
           d.scheduled_entry_date,
           d.actual_entry_date,
           f.available_manpower,
           f.standard_manpower,
           f.engineering_date
        FROM overview_sub a
        LEFT JOIN construction b ON b.case_id = a.case_id
        LEFT JOIN CaseManagement c ON c.case_id = a.case_id
        LEFT JOIN overview_building d ON d.case_id = a.case_id
        LEFT JOIN subcontractor e ON e.subcontractor_id = d.builder_id
        LEFT JOIN overview_manpower_sub f ON f.seq = d.seq
        WHERE f.floor IS NOT NULL";

if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $start = $_GET['start_date'];
    $end = $_GET['end_date'];
    $Qry .= " AND d.actual_entry_date >= '$start'";
    $Qry .= " AND d.actual_entry_date <= '$end'";
}

if (!empty($selected_region)) {
    $Qry .= " AND c.region = '$selected_region'";
}
if (!empty($get_construction_dropdown)) {
    $Qry .= " AND b.construction_id = '$get_construction_dropdown'";
}
if (!empty($get_company_name_dropdown)) {
    $Qry .= " AND d.builder_id = '$get_company_name_dropdown'";
}
if (!empty($get_building_dropdown)) {
    $Qry .= " AND d.building = '$get_building_dropdown'";
}

$Qry .= " ORDER BY d.actual_entry_date DESC;";

//echo $Qry;
//exit; 

$mDB->query($Qry);


$Qry3 ="";
// $mDB3->query($Qry3);


if ($mDB->rowCount() > 0) {
	$show_inquiry .= <<<EOT
	<table class="table table-bordered" style="border: 2px solid #000; background-color: #FFFFFF; margin: 0 auto; width: 80%; max-width: 800px;">
		<thead>
			<tr class="text-center" style="border-bottom: 2px solid #000;">
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">案件編號</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">區域</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">工程名稱</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">工地</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">下包商名稱</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">棟別</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">樓層</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">預訂進場日</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">實際進場日</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">標準人力</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">可派人力</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">實際人力</th>
			</tr>
		</thead>
		<tbody class="table-group-divider">
EOT;

	while ($row = $mDB->fetchRow(2)) {
		$case_id = $row['case_id'];
		$region = $row['region'];
		$construction_name = $row['construction_name'];
		$engineering_name = $row['engineering_name'];
		$subcontractor_name = $row['subcontractor_name'];
		$building = $row['building'];
		$floor = $row['floor'];
		$scheduled_entry_date = $row['scheduled_entry_date'];
		$actual_entry_date = $row['actual_entry_date'];
		$available_manpower = $row['available_manpower'];
		$standard_manpower = $row['standard_manpower'];
		$engineering_date = $row['engineering_date'];

		$Qry2 = "SELECT COALESCE(SUM(b.manpower), 0) AS total_manpower
				 FROM dispatch a
				 LEFT JOIN dispatch_construction b ON a.dispatch_id = b.dispatch_id
				 LEFT JOIN construction c ON b.construction_id = c.construction_id
				 LEFT JOIN company d ON a.company_id = d.company_id
				 WHERE DATE(a.dispatch_date) >= '$engineering_date'
					AND b.floor = '$floor'
					AND b.building = '$building'";
		$mDB2->query($Qry2);
		while ($row2 = $mDB2->fetchRow(2)) {
			$total_manpower = $row2['total_manpower'];
		}

		$show_inquiry .= <<<EOT
			<tr>
				<td class="text-center text-nowrap vmiddle" style="padding: 10px;">$case_id</td>
				<td class="text-center text-nowrap vmiddle" style="padding: 10px;">$region</td>
				<td class="text-center text-nowrap vmiddle" style="padding: 10px;">$construction_name</td>
				<td class="text-center text-nowrap vmiddle" style="padding: 10px;">$engineering_name</td>
				<td class="text-center text-nowrap vmiddle" style="padding: 10px;">$subcontractor_name</td>
				<td class="text-center text-nowrap vmiddle" style="padding: 10px;">$building</td>
				<td class="text-center text-nowrap vmiddle" style="padding: 10px;">$floor</td>
				<td class="text-center text-nowrap vmiddle" style="padding: 10px;">$scheduled_entry_date</td>
				<td class="text-center text-nowrap vmiddle" style="padding: 10px;">$actual_entry_date</td>
				<td class="text-center text-nowrap vmiddle" style="padding: 10px;">$standard_manpower</td>
				<td class="text-center text-nowrap vmiddle" style="padding: 10px;">$available_manpower</td>
				<td class="text-center text-nowrap vmiddle" style="padding: 10px;">$total_manpower</td>
			</tr>
EOT;
	}

	$show_inquiry .= <<<EOT
		</tbody>
	</table>
EOT;
} else {

	$show_inquiry = "<div class=\"size16 weight text-center m-3\">查無任何資料！</div>";

}

$mDB3->remove();
$mDB2->remove();
$mDB->remove();


$now = date("Y-m-d");

/*
$Close = getlang("關閉");
$Print = getlang("列印");
*/

$show_report = <<<EOT
<div class="mytable w-100 bg-white p-3">
	<div class="myrow">
		<div class="mycell" style="width:20%;">
		</div>
		<div class="mycell weight pt-5 text-center">
			<h3>工程人力表</h3>
		</div>
		<div class="mycell text-end p-2 vbottom" style="width:20%;">
			<div class="btn-group print"  role="group" style="position:fixed;top: 10px; right:10px;z-index: 9999;">
				<button id="close" class="btn btn-info btn-lg" type="button" onclick="window.print();"><i class="bi bi-printer"></i>&nbsp;列印</button>
				<button id="close" class="btn btn-danger btn-lg" type="button" onclick="window.close();"><i class="bi bi-power"></i>&nbsp;關閉</button>
			</div>
		</div>
	</div>
</div>
<hr class="style_a m-2 p-0">
<div class="w-100 p-3 m-auto text-center">

	<div class="inline size12 weight text-nowrap pt-2 vtop mb-2">請選擇實際進場日期範圍： </div>
	<div class="inline mb-2">
		<div class="input-group" id="startdate" style="width:100%;max-width:180px;">
			<input type="text" class="form-control" id="start_date" name="start_date" placeholder="請輸入起始日期" aria-describedby="start_date" value="$start_date">
			<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#startdate" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
		</div>
		<script type="text/javascript">
			$(function () {
				$('#startdate').datetimepicker({
					locale: 'zh-tw'
					,format:"YYYY-MM-DD"
					,allowInputToggle: true
				});
			});
		</script>
	</div>
	<div class="inline mb-2">
		<div class="input-group" id="enddate" style="width:100%;max-width:180px;">
			<input type="text" class="form-control" id="end_date" name="end_date" placeholder="請輸入迄止日期" aria-describedby="end_date" value="$end_date">
			<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#enddate" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
		</div>
		<script type="text/javascript">
			$(function () {
				$('#enddate').datetimepicker({
					locale: 'zh-tw'
					,format:"YYYY-MM-DD"
					,allowInputToggle: true
				});
			});
		</script>
	</div>
	<div class="inline size12 weight text-nowrap vtop mb-2 me-2">區域 : $select_region</div>
	<div class="inline size12 weight text-nowrap vtop mb-2 me-2">工程名稱: $construction_dropdown</div>
	<div class="inline size12 weight text-nowrap vtop mb-2 me-2">下包商: $company_name_dropdown</div>
	<div class="inline size12 weight text-nowrap vtop mb-2 me-2">棟別: $building_dropdown</div>
	<button type="button" class="btn btn-success" onclick="caseselect();"><i class="fas fa-check"></i>&nbsp;查詢</button>
</div>
<div class="w-100 px-3 mb-5">
	<div class="overflow-auto" style="white-space: nowrap;">
		<div class="text-center">
			$show_inquiry
		</div>
	</div>
</div>

EOT;

$show_center = <<<EOT

$show_report

<script>

	function caseselect() {
    var region = $('#region').val();
	var start_date = $('#start_date').val();
	var end_date = $('#end_date').val();
	var construction_name = $('#construction_name').val();
	var building = $('#building').val();
	var floor = $('#floor').val();
	var company_name = $('#company_name').val();

    window.location = '/index.php?ch=$ch&fm=$fm'
                      + '&region=' + region 
					  + '&start_date=' + start_date
					  + '&end_date=' + end_date
					  + '&construction_name=' + construction_name
					  + '&building=' + building
					  + '&floor=' + floor
					  + '&company_name=' + company_name;
    return false;
}


/*
//更新主類別
function getMainSelectVal(){ 
    $.getJSON("$getmainclass",{site_db:'$site_db'},function(json){ 
        var main_class = $("#case_list"); 
		var last_option = main_class.val();
        $("option",case_list).remove(); //清空原有的選項
        var option = "<option></option>";
		main_class.append(option);
        $.each(json,function(index,array){
			if (array['caption'] == last_option)
				option = "<option value='"+array['caption']+"' selected>"+array['caption']+"</option>"; 
			else
				option = "<option value='"+array['caption']+"'>"+array['caption']+"</option>"; 
            main_class.append(option); 
        }); 
    }); 
}
*/

</script>
EOT;




?>