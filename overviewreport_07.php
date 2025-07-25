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

// 取得當月日期
$now = date("Y-m");
$annual_mooth = isset($_GET['annual_mooth']) ? $_GET['annual_mooth'] : $now;

// 建立起訖日期
$start = new DateTime($annual_mooth . '-01');
$end = new DateTime($annual_mooth . '-01');
$end->modify('last day of this month');

// 把 DateTime 格式轉成字串，用於 SQL
$start_date = $start->format('Y-m-d');
$end_date = $end->format('Y-m-d');

$table_date = "";

for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
    $formatted_date = $date->format('j');
    $table_date .= "<th class='text-center text-nowrap vmiddle' style='width:5%;padding: 10px;background-color: #FCE4D6;'>$formatted_date 日</th>\n";
}



$getteamclass = "/smarty/templates/$site_db/$templates/sub_modal/admin/attendance_ms/getteamclass.php";


$mDB = "";
$mDB = new MywebDB();

$mDB2 = "";
$mDB2 = new MywebDB();

$mDB3 = "";
$mDB3 = new MywebDB();



// 工程名稱
$Qry = "SELECT a.case_id,
       a.construction_site,
       b.construction_id AS construction_name,
       b.status1
  FROM construction a
  LEFT JOIN CaseManagement b ON a.case_id = b.case_id
  WHERE b.status1 = '已簽約'
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

        $construction_dropdown .= "<option value='$select_construction_name' $selected>$select_construction_name $case_id</option>";
    }
}

$construction_dropdown .= "</select>";

//載入下包商
$Qry = "SELECT DISTINCT 
    subcontractor_name, -- 協力下包商名稱
    subcontractor_id
FROM subcontractor a

WHERE subcontractor_name IS NOT NULL AND subcontractor_name != '';";

$get_subcontractor_dropdown = isset($_GET['subcontractor_name']) ? $_GET['subcontractor_name'] : '';

$mDB->query($Qry);
$selected_subcontractor = $_GET['subcontractor_name'] ?? '';
$select_subcontractor_name = "<select class=\"inline form-select\" name=\"construction_name\" id=\"subcontractor_name\" style=\"width:auto;\">";
$select_subcontractor_name .= "<option value=''></option>";

if ($mDB->rowCount() > 0) {
    while ($row = $mDB->fetchRow(2)) {
        $subcontractor_name = $row['subcontractor_name'];
		$subcontractor_id = $row['subcontractor_id'];
        $selected = ($subcontractor_name == $selected_subcontractor) ? "selected" : "";
        $select_subcontractor_name .= "<option value='$subcontractor_name' $selected>$subcontractor_name   $subcontractor_id</option>";
    }
}
$select_subcontractor_name .= "</select>";


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

// 載入日期





$show_disabled = "style=\"pointer-events: none;\"";


$show_inquiry = "";

$show_table = "";


if (!empty($get_subcontractor_dropdown)) {
    $Qry = "SELECT DISTINCT 
        a.case_id,
        b.construction_id AS construction_name,
        a.building,
        a.subcontractor_id,
        c.subcontractor_name
    FROM pjprogress_sub a
    LEFT JOIN CaseManagement b ON a.case_id = b.case_id
    LEFT JOIN subcontractor c ON a.subcontractor_id = c.subcontractor_id
    WHERE a.building IS NOT NULL 
      AND TRIM(a.building) <> ''
      AND c.subcontractor_name = '$get_subcontractor_dropdown'
	  AND (
					(a.delivery_date BETWEEN '$start_date' AND '$end_date') OR
					(a.deadline_grouting_date BETWEEN '$start_date' AND '$end_date')
				)";
} else {
    $Qry = "SELECT DISTINCT 
        a.case_id,
        b.construction_id AS construction_name,
        a.building
    FROM pjprogress_sub a
    LEFT JOIN CaseManagement b ON a.case_id = b.case_id
    WHERE a.building IS NOT NULL 
      AND TRIM(a.building) <> ''
	  AND (
					(a.delivery_date BETWEEN '$start_date' AND '$end_date') OR
					(a.deadline_grouting_date BETWEEN '$start_date' AND '$end_date')
				)";

    if (!empty($get_construction_dropdown)) {
        $Qry .= " AND b.construction_id = '$get_construction_dropdown'";
    }
    if (!empty($get_building_dropdown)) {
        $Qry .= " AND a.building = '$get_building_dropdown'";
    }

    $Qry .= " ORDER BY
        a.case_id,                                     
        CASE
            WHEN a.building REGEXP '^[A-Za-z]+$' THEN 0  
            ELSE 1
        END,
        a.building + 0,                               
        a.building";
}

$mDB->query($Qry);



if ($mDB->rowCount() > 0) {
while ($row = $mDB->fetchRow(2)) {
	$show_case = "";
	$grouting_schedule = [];
	$deadline_grouting_day = "";
	$case_id = $row['case_id'];
	$construction_name = $row['construction_name'];
	$building = $row['building'];

	$Qry2 = "SELECT a.case_id,
						a.task_name,
						a.floor,
						a.delivery_date,
						a.deadline_grouting_date,
						a.building,
						a.subcontractor_id
				FROM pjprogress_sub a
				WHERE a.case_id = '$case_id'
				AND a.task_name = '灌漿'
				AND a.building = '$building'
				
				AND (
					(a.delivery_date BETWEEN '$start_date' AND '$end_date') OR
					(a.deadline_grouting_date BETWEEN '$start_date' AND '$end_date')
				)";
	
	$mDB2->query($Qry2);
		while ($row2 = $mDB2->fetchRow(2)) {
						$floor = $row2['floor'];
						$delivery_date = $row2['delivery_date'];
						$deadline_grouting_date = $row2['deadline_grouting_date'];

						if (!empty($delivery_date) && $stakdelivery_datee_date != "0000-00-00") {
							$day = date('j', strtotime($delivery_date));
							$grouting_schedule[$day][] = "$floor 交板";
						}

						if (!empty($deadline_grouting_date) && $deadline_grouting_date != "0000-00-00") {
							$day = date('j', strtotime($deadline_grouting_date));
							$grouting_schedule[$day][] = "$floor 灌漿";
						}
					}
					for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
							$formatted_day = $date->format('j');

							if (isset($grouting_schedule[$formatted_day])) {
								$items = implode(' ', $grouting_schedule[$formatted_day]);
								$bg_color = (strpos($items, '交板') !== false && strpos($items, '灌漿') !== false) ? '#14EC80' :
											(strpos($items, '交板') !== false ? '#ED7D31' : '#FFFF00');
								$show_case .= "<td class='text-center text-nowrap vmiddle' style='width:5%;padding: 10px;background-color: $bg_color;'>$items</td>";
							} else {
								$show_case .= "<td class='text-center text-nowrap vmiddle' style='width:5%;padding: 10px;'></td>";
							}
						}

			$Qry3 = "SELECT DISTINCT 
						a.case_id, 
						a.builder_id, 
						c.subcontractor_name,
						a.works_per_floor, 
						a.eng_description, 
						a.construction_days_per_floor
					FROM overview_building a
					LEFT JOIN CaseManagement b ON a.case_id = b.case_id
					LEFT JOIN subcontractor c ON a.builder_id = c.subcontractor_id
					WHERE a.case_id = '$case_id'
					AND a.building = '$building'
					";
					$mDB3->query($Qry3);


			$subcontractor_info = '';
			$eng_description_info = '';
			$construction_days_per_floor_info = '';

			while ($row3 = $mDB3->fetchRow(2)) {
				$subcontractor_name = $row3['subcontractor_name'];
				$works_per_floor = $row3['works_per_floor'];
				$eng_description = $row3['eng_description']; 
				$construction_days_per_floor = $row3['construction_days_per_floor']; // 同上

				$subcontractor_info .= "<b>$subcontractor_name</b><br>每層工程量: $works_per_floor M<br>";
				$eng_description_info .= "<b>$eng_description</b><br>";
				$construction_days_per_floor_info .= "<b>$construction_days_per_floor</b><br>";
			}

				
				
					$show_table .= <<<EOT
					<tr class="text-center" style="border-bottom: 2px solid #000;">
						<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$case_id</td>
						<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$construction_name</td>
						<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$subcontractor_info</td>
						<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$eng_description_info</td>
						<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$construction_days_per_floor_info</td>
						<td class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;">$building</td>
						$show_case
					</tr>
EOT;
}


    $show_inquiry .= <<<EOT
    <table class="table table-bordered" style="border: 2px solid #000; background-color: #FFFFFF; margin: 0 auto; width: 80%; max-width: 800px;">
        <thead>
            <tr class="text-center" style="border-bottom: 2px solid #000;">
                <th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">案件編號</th> 
                <th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">工程名稱</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">下包商資料</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">施作樓層</th>
				<th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">業主要求天數</th>
                <th class="text-center text-nowrap vmiddle" style="width:5%;padding: 10px;background-color: #FCE4D6;">棟別</th>
				$table_date
               
            </tr>
			$show_table
        </thead>
        <tbody class="table-group-divider">
EOT;
	

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
			<h3>工程進度月報表</h3>
		</div>
		<div class="mycell text-end p-2 vbottom" style="width:20%;">
			<div class="mycell text-end p-2 vbottom" style="width:20%;">
			<div class="btn-group print"  role="group" style="position:fixed;top: 10px; right:10px;z-index: 9999;">
				<button id="close" class="btn btn-info btn-lg" type="button" onclick="window.print();"><i class="bi bi-printer"></i>&nbsp;列印</button>
				<button id="close" class="btn btn-danger btn-lg" type="button" onclick="window.close();"><i class="bi bi-power"></i>&nbsp;關閉</button>
			</div>
		</div>
		</div>
	</div>
</div>
<hr class="style_a m-2 p-0">

<div class="w-100 p-3 m-auto text-center">

	<div class="inline size12 weight text-nowrap pt-2 vtop mb-2">月份: </div>
	<div class="inline mb-2">
		<div class="input-group" id="annualyear" style="width:100%;max-width:180px;">
			<input type="text" class="form-control" id="annual_mooth" name="annual annual_mooth" placeholder="請輸入年份" aria-describedby="annual_mooth" value="$annual_mooth">
			<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#annualyear" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
		</div>
		<script type="text/javascript">
			$(function () {
				$('#annualyear').datetimepicker({
					locale: 'zh-tw'
					,format:"YYYY-MM"
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
	<div class="inline size12 weight text-nowrap vtop mb-2 me-2">下包商: $select_subcontractor_name</div>
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
	var annual_mooth = $('#annual_mooth').val();
	var construction_name = $('#construction_name').val();
	var building = $('#building').val();
	var subcontractor_name = $('#subcontractor_name').val();

    window.location = '/index.php?ch=$ch&fm=$fm'
					  + '&annual_mooth=' + annual_mooth
					  + '&construction_name=' + construction_name
					  + '&subcontractor_name=' + subcontractor_name 
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


?>