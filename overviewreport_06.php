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
$now = date("Y");
$annual_year = isset($_GET['annual_year']) ? $_GET['annual_year'] : $now;
$start_date = $annual_year . '-01-01';
$end_date = $annual_year . '-12-31';



$getteamclass = "/smarty/templates/$site_db/$templates/sub_modal/admin/attendance_ms/getteamclass.php";


$mDB = "";
$mDB = new MywebDB();

$mDB2 = "";
$mDB2 = new MywebDB();

$mDB3 = "";
$mDB3 = new MywebDB();


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

        $building_dropdown .= "<option value='$select_building' $selected>$select_building</option>";
    }
}

$building_dropdown .= "</select>";



$show_disabled = "style=\"pointer-events: none;\"";

$mDB = "";
$mDB = new MywebDB();

$mDB2 = "";
$mDB2 = new MywebDB();

$showcase = "";
$show_inquiry = "";
// 基本查詢語句
$Qry = "SELECT DISTINCT 
        a.case_id,
        b.construction_id AS construction_name,
        c.building
    FROM overview_material_sub a
    LEFT JOIN CaseManagement b ON b.case_id = a.case_id
    LEFT JOIN overview_material_building c ON c.seq = a.seq
    LEFT JOIN construction d ON d.case_id = a.case_id
	WHERE (
				YEAR(a.est_feed_start_date) = '$annual_year' OR
				YEAR(a.feed_start_date) = '$annual_year' OR
				YEAR(a.feed_end_date) = '$annual_year' OR
				YEAR(a.est_return_start_date) = '$annual_year' OR
				YEAR(a.return_start_date) = '$annual_year' OR
				YEAR(a.return_end_date) = '$annual_year'
			)
";
if (!empty($get_construction_dropdown)) {
    $Qry .= " AND b.construction_id = '$get_construction_dropdown'";
}
if (!empty($get_building_dropdown)) {
    $Qry .= " AND c.building = '$get_building_dropdown'";
}

//echo $Qry;
//exit; 
$mDB->query($Qry);
if ($mDB->rowCount() > 0) {
    $show_inquiry .= <<<EOT
    <table class="table table-bordered" style="border: 2px solid #000; background-color: #FFFFFF; margin: 0 auto; width: 80%; max-width: 800px;">
        <thead>
            <tr class="text-center" style="border-bottom: 2px solid #000;">
                <th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">案件編號</th>
                <th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">工程名稱</th>
                <th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">棟別</th>
                <th class="text-center text-nowrap vmiddle" style="padding: 10px;background-color: #FCE4D6;">一月</th>
                <th class="text-center text-nowrap vmiddle" style="padding: 10px;background-color: #FCE4D6;">二月</th>
                <th class="text-center text-nowrap vmiddle" style="padding: 10px;background-color: #FCE4D6;">三月</th>
                <th class="text-center text-nowrap vmiddle" style="padding: 10px;background-color: #FCE4D6;">四月</th>
                <th class="text-center text-nowrap vmiddle" style="padding: 10px;background-color: #FCE4D6;">五月</th>
                <th class="text-center text-nowrap vmiddle" style="padding: 10px;background-color: #FCE4D6;">六月</th>
                <th class="text-center text-nowrap vmiddle" style="padding: 10px;background-color: #FCE4D6;">七月</th>
                <th class="text-center text-nowrap vmiddle" style="padding: 10px;background-color: #FCE4D6;">八月</th>
                <th class="text-center text-nowrap vmiddle" style="padding: 10px;background-color: #FCE4D6;">九月</th>
                <th class="text-center text-nowrap vmiddle" style="padding: 10px;background-color: #FCE4D6;">十月</th>
                <th class="text-center text-nowrap vmiddle" style="padding: 10px;background-color: #FCE4D6;">十一月</th>
                <th class="text-center text-nowrap vmiddle" style="padding: 10px;background-color: #FCE4D6;">十二月</th>
            </tr>
        </thead>
        <tbody class="table-group-divider">
EOT;

    while ($row = $mDB->fetchRow(2)) {
        $case_id = $row['case_id'];
        $construction_name = $row['construction_name'];
        $building = $row['building'];
        $month_floor_status = array_fill(1, 12, '');

        // 取得該案、工程、棟別對應的樓層及日期
       $Qry2 = "SELECT 
				a.floor,
				a.est_feed_start_date,
				a.feed_start_date,
				a.feed_end_date,
				a.est_return_start_date,
				a.return_start_date,
				a.return_end_date
			FROM overview_material_sub a
			LEFT JOIN overview_material_building c ON c.seq = a.seq
			LEFT JOIN CaseManagement b ON b.case_id = a.case_id
			
			WHERE a.case_id = '$case_id' 
			AND c.building = '$building' 
			AND b.construction_id = '$construction_name'
			";

			$mDB2->query($Qry2);

			$month_floor_status = [];

			while ($row2 = $mDB2->fetchRow(2)) {
				$floor = $row2['floor'];
				$dates = [
					['date' => cleanDate($row2['est_feed_start_date']), 'label' => '預估進料開始'],
					['date' => cleanDate($row2['feed_start_date']), 'label' => '實際進料開始'],
					['date' => cleanDate($row2['feed_end_date']), 'label' => '進料結束'],
					['date' => cleanDate($row2['est_return_start_date']), 'label' => '預估退料開始'],
					['date' => cleanDate($row2['return_start_date']), 'label' => '退料開始'],
					['date' => cleanDate($row2['return_end_date']), 'label' => '退料結束'],
				];

				foreach ($dates as $d) {
					if (!empty($d['date']) && date('Y', strtotime($d['date'])) == $annual_year) {
						$month = (int)date('n', strtotime($d['date']));
						$dateLabel = date('n/j', strtotime($d['date'])) . ' ' . $d['label'];
						$month_floor_status[$month][$floor][] = $dateLabel;
					}
				}
			}

        // 產生查詢結果
        $show_inquiry .= "<tr>
            <td class=\"text-center text-nowrap vmiddle\" style=\"padding: 10px;\">$case_id</td>
            <td class=\"text-center text-nowrap vmiddle\" style=\"padding: 10px;\">$construction_name</td>
            <td class=\"text-center text-nowrap vmiddle\" style=\"padding: 10px;\">$building</td>";

       for ($i = 1; $i <= 12; $i++) {
    			$cellContent = '';
				if (!empty($month_floor_status[$i])) {
					foreach ($month_floor_status[$i] as $floor => $labels) {
						$uniqueLabels = array_unique($labels);
						$labelText = implode('<br>', $uniqueLabels);
						$cellContent .= "<b>{$floor}</b><br>{$labelText}<br><br>";
					}
				}
				$show_inquiry .= "<td class=\"text-center text-nowrap vmiddle\" style=\"padding: 10px;\">$cellContent</td>";
			}
        $show_inquiry .= "</tr>";
    }

    $show_inquiry .= "</tbody></table>";
} else {
    $show_inquiry = "<div class=\"size16 weight text-center m-3\">查無任何資料！</div>";
}

// $mDB3->remove();
// $mDB2->remove();
$mDB->remove();

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
			<h3>物資時程年度表</h3>
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

	<div class="inline size12 weight text-nowrap pt-2 vtop mb-2">年度: </div>
	<div class="inline mb-2">
		<div class="input-group" id="annualyear" style="width:100%;max-width:180px;">
			<input type="text" class="form-control" id="annual_year" name="annual annual_year" placeholder="請輸入年份" aria-describedby="annual_year" value="$annual_year">
			<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#annualyear" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
		</div>
		<script type="text/javascript">
			$(function () {
				$('#annualyear').datetimepicker({
					locale: 'zh-tw'
					,format:"YYYY"
					,allowInputToggle: true
				});
			});
		</script>
		<style>
		.bootstrap-datetimepicker-widget {
			z-index: 1050 !important; /* 保證浮在其他元件上 */
			position: absolute !important;
		}
		</style>
	</div>
	
	<div class="inline size12 weight text-nowrap vtop mb-2 me-2">工程名稱: $construction_dropdown</div>
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
	var annual_year = $('#annual_year').val();
	var construction_name = $('#construction_name').val();
	var building = $('#building').val();
	var floor = $('#floor').val();

    window.location = '/index.php?ch=$ch&fm=$fm'
					  + '&annual_year=' + annual_year
					  + '&construction_name=' + construction_name
					  + '&building=' + building

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

function cleanDate($date) {
    return ($date == '0000-00-00' || $date == '') ? '' : $date;
}


?>