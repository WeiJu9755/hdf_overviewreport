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



$getteamclass = "/smarty/templates/$site_db/$templates/sub_modal/admin/attendance_ms/getteamclass.php";


$mDB = "";
$mDB = new MywebDB();

$mDB2 = "";
$mDB2 = new MywebDB();

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
$construction_dropdown .= "<option value=''></> ( all ) </option>";

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

//載入樓層
$Qry = "SELECT pro_id,
       caption AS floor -- 樓層
       FROM items
       WHERE pro_id = 'floor'";

$mDB->query($Qry);

$get_floor_dropdown = isset($_GET['floor']) ? $_GET['floor'] : '';

$floor_dropdown = "<select class=\"inline form-select\" name=\"floor\" id=\"floor\" style=\"width:auto;\">";
$floor_dropdown .= "<option></option>";

if ($mDB->rowCount() > 0) {
    while ($row = $mDB->fetchRow(2)) {
        $select_floor = $row['floor'];
        $selected = ($get_floor_dropdown == $select_floor) ? "selected" : "";

        // 工程名稱
        $floor_dropdown .= "<option value='$select_floor' $selected>$select_floor</option>";
    }
}

$floor_dropdown .= "</select>";

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



$casereport_list .= <<<EOT
<div class="w-100 m-auto px-3" style="min-height:300px;margin-bottom: 100px;">
	<div class="w-100" style="overflow-x: auto;">
		<div class="w-100" style="min-width:1760px;">
EOT;
if(trim($get_company_name_dropdown) === ""){
    // 公司名稱為空，查詢全部有 company_id 的紀錄並進行彙總
    $Qry = "SELECT 
        a.case_id,
        a.task_name,
        a.building,
        a.floor,
        a.stake_date,
        a.delivery_date,
        a.construction_start_date,
        a.construction_end_date,
        a.deadline_grouting_date,
        b.construction_id AS construction_name,
        g.construction_site,
        SUM(d.works_per_floor) AS works_per_floor
    FROM pjprogress_sub a
    LEFT JOIN CaseManagement b ON b.case_id = a.case_id
    LEFT JOIN construction g ON g.case_id = a.case_id
    LEFT JOIN overview_building d ON d.case_id = a.case_id AND d.building = a.building
    LEFT JOIN subcontractor e ON d.builder_id = e.subcontractor_id
    WHERE a.task_name = '灌漿'
    AND e.subcontractor_id IS NOT NULL";
    
    // 加上條件（統一加條件邏輯）
    if ($get_construction_dropdown !== "") {
        $Qry .= " AND b.construction_id = '$get_construction_dropdown'";
    }
    if ($get_building_dropdown !== "") {
        $Qry .= " AND a.building = '$get_building_dropdown'";
    }
    if ($get_floor_dropdown !== "") {
        $Qry .= " AND a.floor = '$get_floor_dropdown'";
    }

   $Qry .= " GROUP BY 
    a.stake_date,
    a.case_id,
    a.task_name,
    a.building,
    a.floor,
    a.delivery_date,
    a.construction_start_date,
    a.construction_end_date,
    a.deadline_grouting_date,
    b.construction_id,
    g.construction_site
ORDER BY a.stake_date DESC";
} elseif(trim($get_company_name_dropdown) === "all") {
    // 公司名稱不為空，查詢個別紀錄
    $Qry = "SELECT 
        a.case_id,
        a.task_name,
        a.building,
        a.floor,
        a.stake_date,
        a.delivery_date,
        a.construction_start_date,
        a.construction_end_date,
        a.deadline_grouting_date,
        b.construction_id AS construction_name,
        g.construction_site,
        d.works_per_floor,
        e.company_id,
        e.company_name AS company_name
    FROM pjprogress_sub a
    LEFT JOIN CaseManagement b ON b.case_id = a.case_id
    LEFT JOIN construction g ON g.case_id = a.case_id
    LEFT JOIN overview_building d ON d.case_id = a.case_id AND d.building = a.building
    LEFT JOIN company e ON d.builder_id = e.company_id
    WHERE a.task_name = '灌漿'";

    if ($get_construction_dropdown !== "") {
        $Qry .= " AND b.construction_id = '$get_construction_dropdown'";
    }
    if ($get_building_dropdown !== "") {
        $Qry .= " AND a.building = '$get_building_dropdown'";
    }
    if ($get_floor_dropdown !== "") {
        $Qry .= " AND a.floor = '$get_floor_dropdown'";
    }

    $Qry .= "
ORDER BY a.stake_date DESC";
} else {
    // 公司名稱不為空，查詢個別紀錄
    $Qry = "SELECT 
        a.case_id,
        a.task_name,
        a.building,
        a.floor,
        a.stake_date,
        a.delivery_date,
        a.construction_start_date,
        a.construction_end_date,
        a.deadline_grouting_date,
        b.construction_id AS construction_name,
        g.construction_site,
        d.works_per_floor,
        e.company_id,
        e.company_name AS company_name
    FROM pjprogress_sub a
    LEFT JOIN CaseManagement b ON b.case_id = a.case_id
    LEFT JOIN construction g ON g.case_id = a.case_id
    LEFT JOIN overview_building d ON d.case_id = a.case_id AND d.building = a.building
    LEFT JOIN company e ON d.builder_id = e.company_id
    WHERE a.task_name = '灌漿'";

    if ($get_construction_dropdown !== "") {
        $Qry .= " AND b.construction_id = '$get_construction_dropdown'";
    }
    if ($get_building_dropdown !== "") {
        $Qry .= " AND a.building = '$get_building_dropdown'";
    }
    if ($get_floor_dropdown !== "") {
        $Qry .= " AND a.floor = '$get_floor_dropdown'";
    }
    // 這裡只要直接加上篩選公司名稱
    $Qry .= " AND e.company_name = '$get_company_name_dropdown'";

    $Qry .= " GROUP BY 
    a.stake_date,
    a.case_id,
    a.task_name,
    a.building,
    a.floor,
    a.delivery_date,
    a.construction_start_date,
    a.construction_end_date,
    a.deadline_grouting_date,
    b.construction_id,
    g.construction_site
ORDER BY a.stake_date DESC";
}


$mDB->query($Qry);

$total = $mDB->rowCount();
if ($total > 0) {


	$casereport_list .= <<<EOT

	
</div>
	<table class="table table-bordered border-dark w-100">
		<thead class="table-light border-dark">
			<tr style="border-bottom: 1px solid #000;">
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">案件編號</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">工程名稱</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">工地</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;$show_company_column">下包商名稱</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">棟別</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">樓層</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">實際出工起始日期</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">鋁模板施作開始日期</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">交版日期</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">交版施工總天數</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">施工總人力(交版)</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">鋁模板施作結束日期</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">截止-灌漿施工總天數</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">施工總人力(灌漿)</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">每層工程量(M2)</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FFE699;">每層工率-交版</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FFE699;">每層工率-實際</th>
                
				
				
				
			</tr>
		</thead>
		<tbody class="table-group-divider">
EOT;

while ($row = $mDB->fetchRow(2)) {
    $case_id = $row['case_id'];
    $construction_name = $row['construction_name'];
    $construction_site = $row['construction_site'];
    $company_name = $row['company_name'];
    $stake_date = $row['stake_date'];
    $building = $row['building'];
    $floor = $row['floor'];
    $start_date = $row['construction_start_date'];
    $delivery_date = $row['delivery_date'];
    $construction_start_date = $row['construction_start_date'];
    $construction_end_date = $row['construction_end_date'];
	$works_per_floor = $row['works_per_floor'];

    // 計算施工天數
$construction_days = (strtotime($delivery_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
$grouting_days = (strtotime($construction_end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;

$construction_days = ($construction_days < 0) ? '-' : $construction_days;
$grouting_days = ($grouting_days < 0) ? '-' : $grouting_days;

    // 查詢施工人力總數
    $QryManpower = "SELECT 
            MIN(a.dispatch_date) AS first_date,
            COALESCE(SUM(b.manpower), 0) AS total_manpower,
            d.company_name
        FROM dispatch a
        LEFT JOIN dispatch_construction b ON a.dispatch_id = b.dispatch_id
        LEFT JOIN construction c ON b.construction_id = c.construction_id
        LEFT JOIN company d ON a.company_id = d.company_id
        WHERE DATE(a.dispatch_date) BETWEEN '$start_date' AND '$delivery_date' 
            AND b.floor = '$floor' 
            AND b.building = '$building'";

    if($company_name != ""){
        $QryManpower .= " AND d.company_name = '$company_name'";
    }

    $mDB2->query($QryManpower);
    $total_manpower = 0;
    if ($row2 = $mDB2->fetchRow(2)) {
        $first_date = $row2['first_date'];
        $total_manpower = $row2['total_manpower'];
    }

    // 查詢灌漿施工人力總數
    $QryGroutingManpower = "SELECT 
            COALESCE(SUM(b.manpower), 0) AS total_grouting_manpower
        FROM dispatch a
        LEFT JOIN dispatch_construction b ON a.dispatch_id = b.dispatch_id
        LEFT JOIN construction c ON b.construction_id = c.construction_id
        LEFT JOIN company d ON a.company_id = d.company_id
        WHERE DATE(a.dispatch_date) BETWEEN '$start_date' AND '$construction_end_date' 
            AND b.floor = '$floor' 
            AND b.building = '$building'";

    if($company_name != ""){
        $QryGroutingManpower .= " AND d.company_name = '$company_name'";
    }

    $mDB2->query($QryGroutingManpower);
    $total_grouting_manpower = 0;
    if ($row2 = $mDB2->fetchRow(2)) {
        $total_grouting_manpower = $row2['total_grouting_manpower'];
    }
	$work_rate_per_worker = ($total_manpower > 0 && $works_per_floor > 0) ? number_format($works_per_floor / $total_manpower, 2) : '0.00';
    $work_rate_per_grouting_worker = ($total_grouting_manpower > 0 && $works_per_floor > 0) ? number_format($works_per_floor / $total_grouting_manpower, 2) : '0.00';
    // 生成表格內容
	$casereport_list .= <<<EOT
	<tr>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$case_id</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$construction_name</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$construction_site</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;$show_company_column">$company_name</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$building</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$floor</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$first_date</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$start_date</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$delivery_date</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$construction_days</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$total_manpower</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$construction_end_date</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$grouting_days</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$total_grouting_manpower</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$works_per_floor</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$work_rate_per_worker</td>
		<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$work_rate_per_grouting_worker</td>
	</tr>
	EOT;
}
	$casereport_list .= <<<EOT
		</tbody>
	</table>
EOT;

}

else {

	$casereport_list .= <<<EOT
	<div class="size16 weight p-5 text-center">無任何符合查詢的資料</div>
EOT;}


$mDB2->remove();
$mDB->remove();




/*
$Close = getlang("關閉");
$Print = getlang("列印");
*/

$show_report = <<<EOT

<div class="mytable w-100 bg-white p-3 mt-3">
	<div class="myrow">
		<div class="mycell" style="width:20%;">
		</div>
		<div class="mycell weight pt-5 pb-4 text-center">
			<h3>工率報表</h3>
					<div class="w-100 p-3 m-auto text-center">

		<div class="inline size12 weight text-nowrap vtop mb-2 me-2">工程名稱 : $construction_dropdown 棟別 : $building_dropdown 樓層 : $floor_dropdown 下包商 : $company_name_dropdown</div>

		<button type="button" class="btn btn-success" onclick="caseselect();"><i class="fas fa-check"></i>&nbsp;查詢</button>
		</div>
		</div>
		<div class="mycell text-end p-2 vbottom" style="width:20%;">
			<div class="btn-group print"  role="group" style="position:fixed;top: 10px; right:10px;z-index: 9999;">
				<button id="close" class="btn btn-info btn-lg" type="button" onclick="window.print();"><i class="bi bi-printer"></i>&nbsp;列印</button>
				<button id="close" class="btn btn-danger btn-lg" type="button" onclick="window.close();"><i class="bi bi-power"></i>&nbsp;關閉</button>
			</div>
		</div>
	</div>
</div>
<div style="margin-bottom: 150px;">
	$casereport_list
</div>
EOT;

$show_center = <<<EOT

$show_report

<script>

function caseselect() {
    var construction_name = $('#construction_name').val();
	var building = $('#building').val();
	var floor = $('#floor').val();
	var company_name = $('#company_name').val();

    window.location = '/index.php?ch=$ch&fm=$fm'
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