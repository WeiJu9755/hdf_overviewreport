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

$getteamclass = "/smarty/templates/$site_db/$templates/sub_modal/admin/attendance_ms/getteamclass.php";


$mDB = "";
$mDB = new MywebDB();

$mDB2 = "";
$mDB2 = new MywebDB();

$mDB3 = "";
$mDB3 = new MywebDB();

$mDB4 = "";
$mDB4 = new MywebDB();


//載入案件
$Qry = "SELECT case_id , construction_id FROM CaseManagement WHERE status1 = '已簽約' AND (ContractingModel = '連工帶料(UH)' OR ContractingModel = '代工(WH)')";

$mDB->query($Qry);


$select_case = "";
$select_case = "<select class=\"inline form-select form-select-sm\" name=\"case_list\" id=\"case_list\" style=\"width:auto;\">";
$select_case .= "<option></option>";

if ($mDB->rowCount() > 0) {
	while ($row = $mDB->fetchRow(2)) {
		$ch_case_id = $row['case_id'];
		$ch_case_name = $row['construction_id'];
		$select_case .= "<option value='$ch_case_id' " . mySelect($ch_case_id, $case_id) . "><strong>$ch_case_id</strong> $ch_case_name </option>";
	}
}
$select_case .= "</select>";






$show_disabled = "style=\"pointer-events: none;\"";

$show_inquiry = "";


//取得案件資料
$Qry = "SELECT 
    a.*,  -- 選取 CaseManagement 表中的所有欄位
    b.builder_name,  -- 上包公司名稱
    SUM(c.standard_manpower) AS total_standard_manpower,  -- 總標準人力數量 (計算 overview_manpower_sub 表中的標準人力)
    g.employee_name AS maincontractor_pricing_staff,  -- 上包計價人員名稱
    COALESCE(g.mobile_no, g.member_no) AS maincontractor_pricing_staff_mobile, -- 選擇有值的欄位作為上包計價人員的聯絡方式 (手機號碼或會員號碼)
    h.employee_name AS subcontractor_pricing_staff,  -- 下包計價人員名稱
    COALESCE(h.mobile_no, h.member_no) AS subcontractor_pricing_staff_mobile, -- 選擇有值的欄位作為下包計價人員的聯絡方式 (手機號碼或會員號碼)
    i.employee_name AS responsible_name,  -- 工務人員名稱
    COALESCE(i.member_no, i.mobile_no) AS responsible_mobile,  -- 工務人員聯絡方式 (會員號碼或手機號碼)
    j.employee_name AS handler_name,  -- 設計人員名稱
    COALESCE(j.member_no, j.mobile_no) AS handler_mobile, -- 選擇有值的欄位作為設計人員的聯絡方式 (會員號碼或手機號碼)
	k.contractor_name  -- 營造商名稱


FROM CaseManagement a
LEFT JOIN builder b ON b.builder_id = a.builder_id
LEFT JOIN overview_manpower_sub c ON c.case_id = a.case_id
LEFT JOIN overview_sub d ON d.case_id = a.case_id  
LEFT JOIN subcontractor e ON e.subcontractor_id = d.layout 
LEFT JOIN subcontractor f ON f.subcontractor_id = d.builder_id
LEFT JOIN employee g ON g.employee_id = d.maincontractor_pricing_staff
LEFT JOIN employee h ON h.employee_id = d.subcontractor_pricing_staff
LEFT JOIN employee i ON i.employee_id = d.responsible
LEFT JOIN employee j ON j.employee_id = a.Handler
LEFT JOIN contractor k ON k.contractor_id = a.contractor_id

WHERE status1 = '已簽約' AND a.case_id = '$case_id'
GROUP BY a.case_id";

//echo $Qry;
//exit; 

$mDB->query($Qry);

$Qry2 ="SELECT 
		f.subcontractor_name AS subcontractor_name,  -- 建協力下包商名稱
		d.workers,  -- 協力下包人數
		f.contact AS subcontractor_contact,  -- 協力下包商聯絡人
		f.tel AS subcontractor_tel  -- 協力下包商電話
	FROM CaseManagement a
	LEFT JOIN overview_sub d ON d.case_id = a.case_id  
	LEFT JOIN subcontractor e ON e.subcontractor_id = d.layout 
	LEFT JOIN subcontractor f ON f.subcontractor_id = d.builder_id
	WHERE a.status1 = '已簽約' AND a.case_id = '$case_id'
	GROUP BY a.case_id, f.subcontractor_name, d.workers, f.contact, f.tel, e.subcontractor_name, d.layout_number, e.contact, e.tel";

$mDB2->query($Qry2);
$show_subcontractor = "";
$count = 0;

while($row2 = $mDB2->fetchRow(2)){
	$subcontractor_name = $row2['subcontractor_name'];
	$workers = $row2['workers'];
	$subcontractor_contact = $row2['subcontractor_contact'];
	$subcontractor_tel = $row2['subcontractor_tel'];

	// 第一筆資料時加上 rowspan 和 "下包"
	if ($count === 0) {
		$show_subcontractor .= "<tr class=\"text-center\" style=\"border-bottom: 1px solid #000;\">";
		$show_subcontractor .= "<td rowspan=\"{$mDB2->rowCount()}\" class=\"size12\" style=\"padding: 10px;\">下包</td>";
	} else {
		$show_subcontractor .= "<tr class=\"text-center\" style=\"border-bottom: 1px solid #000;\">";
	}

	$show_subcontractor .= "
		<td class=\"size12\" style=\"padding: 10px;\">$subcontractor_name</td>
		<td class=\"size12\" style=\"padding: 10px;\">$workers</td>
		<td class=\"size12\" style=\"padding: 10px;\">$subcontractor_contact</td>
		<td class=\"size12\" style=\"padding: 10px;\">$subcontractor_tel</td>
	</tr>";

	$count++;
}

$Qry3 ="SELECT 
    e.subcontractor_name AS layout_name,
    d.layout_number,
    e.contact AS layout_contact,
    e.tel AS layout_tel
FROM CaseManagement a
LEFT JOIN overview_sub d ON d.case_id = a.case_id  
LEFT JOIN subcontractor e ON e.subcontractor_id = d.layout 
WHERE a.status1 = '已簽約' AND a.case_id = '$case_id'
GROUP BY e.subcontractor_name, d.layout_number, e.contact, e.tel;
";

$show_layout = "";
$count_layout = 0;
$mDB3->query($Qry3);

while($row3 = $mDB3->fetchRow(2)){
	$layout_name = $row3['layout_name'];
	$layout_number = $row3['layout_number'];
	if (empty($layout_number)) {
    	$layout_number = "";
	}
	$layout_contact = $row3['layout_contact'];
	$layout_tel = $row3['layout_tel'];
// 第一筆資料時加上 rowspan 和 "放樣"
	if ($count_layout === 0) {
		$show_layout .= "<tr class=\"text-center\" style=\"border-bottom: 1px solid #000;\">";
		$show_layout .= "<td rowspan=\"{$mDB3->rowCount()}\" class=\"size12\" style=\"padding: 10px;\">放樣</td>";
	} else {
		$show_layout .= "<tr class=\"text-center\" style=\"border-bottom: 1px solid #000;\">";
	}

	$show_layout .= "
		<td class=\"size12\" style=\"padding: 10px;\">$layout_name </td>
		<td class=\"size12\" style=\"padding: 10px;\">$layout_number</td>
		<td class=\"size12\" style=\"padding: 10px;\">$layout_contact</td>
		<td class=\"size12\" style=\"padding: 10px;\">$layout_tel</td>
		</tr>";
		$count_layout++;
}




if ($mDB->rowCount() > 0) {
	while ($row = $mDB->fetchRow(2)) {
		$case_id = $row['case_id'];
		$builder_name = $row['builder_name'];
		$construction_id = $row['construction_id'];
		$contractor_name = $row['contractor_name'];
		$architect_office = $row['architect_office'];
		
		$title = $row['title'];
		$contractor_tel = $row['contractor_tel'];
		$ContractingModel = $row['ContractingModel'];
		$buildings=$row['buildings'];
		$estimated_arrival_date = $row['estimated_arrival_date'];
		$completion_date = $row['completion_date'];
		$engineering_qty = '<td class="size12" rowspan="1" style="padding: 10px;">' . (!empty($row['engineering_qty']) ? $row['engineering_qty'] . ' m²' : '') . '</td>';
		$std_layer_template_qty = '<td class="size12" rowspan="2" style="padding: 10px;">' . (!empty($row['std_layer_template_qty']) ? $row['std_layer_template_qty'] . ' m²' : '') . '</td>';
		$total_standard_manpower = $row['total_standard_manpower'];
		$maincontractor_pricing_staff = $row['maincontractor_pricing_staff'];
		$maincontractor_pricing_staff_mobile = $row['maincontractor_pricing_staff_mobile'];
		$subcontractor_pricing_staff = $row['subcontractor_pricing_staff'];
		$subcontractor_pricing_staff_mobile = $row['subcontractor_pricing_staff_mobile'];
		$responsible_name = $row['responsible_name'];
		$responsible_mobile = $row['responsible_mobile'];
		$handler_name = $row['handler_name'];
		$handler_mobile = $row['handler_mobile'];
		$location_URL = $row['location_URL'];
		$contractor_name = $row['contractor_name'];

		$builder_rows = "";
		$Qry_builder = "SELECT * FROM case_contacts WHERE organization_type = 'builder' AND case_id = '$case_id'";
		$mDB4->query($Qry_builder);
		$b_data = [];
		while ($row = $mDB4->fetchRow(2)) { $b_data[] = $row; }

		$b_count = count($b_data);
		if ($b_count > 0) {
			foreach ($b_data as $i => $row) {
				$builder_rows .= '<tr class="text-center" style="border-bottom: 1px solid #000;">';
				if ($i === 0) {
					$builder_rows .= '<td class="size12" style="padding: 10px;" rowspan="'.$b_count.'">建設公司</td>';
					$builder_rows .= '<td class="size12" style="padding: 10px;" rowspan="'.$b_count.'">'.$builder_name.'</td>';
				}
				$builder_rows .= '<td class="size12" style="padding: 10px;">'.$row['contact_name'].'</td>';
				$builder_rows .= '<td class="size12" style="padding: 10px;">'.$row['contact_tel'].'</td>';
				$builder_rows .= '<td class="size12" style="padding: 10px;">'.$row['remark'].'</td></tr>';
			}
		}else{
				$builder_rows .= '<tr class="text-center" style="border-bottom: 1px solid #000;">';
				$builder_rows .= '<td class="size12" style="padding: 10px;">建設公司</td>';
				$builder_rows .= '<td class="size12" style="padding: 10px;">'.$builder_name.'</td>';
				$builder_rows .= '<td class="size12" style="padding: 10px;"></td>'; // 空的聯絡人
				$builder_rows .= '<td class="size12" style="padding: 10px;"></td>'; // 空的電話
				$builder_rows .= '<td class="size12" style="padding: 10px;"></td></tr>'; // 空的備註
		}

		// --- 處理建築師事務所資料 (architect_office) ---
		$architect_office_rows = "";
		$Qry_architect_office = "SELECT a.*
									FROM case_contacts a
									INNER JOIN CaseManagement b 
										ON b.architect_office = a.organization_id
									WHERE a.organization_type = 'architect_office'
									AND a.case_id = '$case_id'";
		$mDB4->query($Qry_architect_office);
		$a_data = [];
		while ($row = $mDB4->fetchRow(2)) { $a_data[] = $row; }

		$a_count = count($a_data);
		if ($a_count > 0) {
			foreach ($a_data as $i => $row) {
				$architect_office_rows .= '<tr class="text-center" style="border-bottom: 1px solid #000;">';
				if ($i === 0) {
					$architect_office_rows .= '<td class="size12" style="padding: 10px;" rowspan="'.$a_count.'">建築師事務所</td>';
					$architect_office_rows .= '<td class="size12" style="padding: 10px;" rowspan="'.$a_count.'">'.$architect_office.'</td>';
				}
				$architect_office_rows .= '<td class="size12" style="padding: 10px;">'.$row['contact_name'].'</td>';
				$architect_office_rows .= '<td class="size12" style="padding: 10px;">'.$row['contact_tel'].'</td>';
				$architect_office_rows .= '<td class="size12" style="padding: 10px;">'.$row['remark'].'</td></tr>';
			}
		}else{
				$architect_office_rows .= '<tr class="text-center" style="border-bottom: 1px solid #000;">';
				$architect_office_rows .= '<td class="size12" style="padding: 10px;">建築師事務所</td>';
				$architect_office_rows .= '<td class="size12" style="padding: 10px;">'.$architect_office.'</td>';
				$architect_office_rows .= '<td class="size12" style="padding: 10px;"></td>'; // 聯絡人
				$architect_office_rows .= '<td class="size12" style="padding: 10px;"></td>'; // 電話
				$architect_office_rows .= '<td class="size12" style="padding: 10px;"></td></tr>'; // 備註
		}

		// --- 處理營造公司資料 (Contractor) ---
		$contractor_rows = "";
		$Qry_contractor = "SELECT * FROM case_contacts WHERE organization_type = 'contractor' AND case_id = '$case_id'";
		$mDB4->query($Qry_contractor);
		$c_data = [];
		while ($row = $mDB4->fetchRow(2)) { $c_data[] = $row; }

		$c_count = count($c_data);
		if ($c_count > 0) {
			foreach ($c_data as $i => $row) {
				$contractor_rows .= '<tr class="text-center" style="border-bottom: 1px solid #000;">';
				if ($i === 0) {
					$contractor_rows .= '<td class="size12" style="padding: 10px;" rowspan="'.$c_count.'">營造公司</td>';
					$contractor_rows .= '<td class="size12" style="padding: 10px;" rowspan="'.$c_count.'">'.$contractor_name.'</td>';
				}
				$contractor_rows .= '<td class="size12" style="padding: 10px;">'.$row['contact_name'].'</td>';
				$contractor_rows .= '<td class="size12" style="padding: 10px;">'.$row['contact_tel'].'</td>';
				$contractor_rows .= '<td class="size12" style="padding: 10px;">'.$row['remark'].'</td></tr>';
			}
		}else{
				$contractor_rows .= '<tr class="text-center" style="border-bottom: 1px solid #000;">';
				$contractor_rows .= '<td class="size12" style="padding: 10px;">營造公司</td>';
				$contractor_rows .= '<td class="size12" style="padding: 10px;">'.$contractor_name.'</td>';
				$contractor_rows .= '<td class="size12" style="padding: 10px;"></td>';
				$contractor_rows .= '<td class="size12" style="padding: 10px;"></td>';
				$contractor_rows .= '<td class="size12" style="padding: 10px;"></td></tr>';
		}
		



		
		$show_disabled = "";

		//案件聯絡方式
		$show_inquiry .= <<<EOT
	<table class="table table-bordered" style="border: 2px solid #000; background-color: #FFFFFF; margin: 0 auto; width: 80%; max-width: 800px;">
		<thead>
			<tr class="text-center" style="border-bottom: 2px solid #000;">
				<th scope="col" class="size12 bg-aqua text-nowrap" style="padding: 10px;width:2%;background-color: #FCE4D6;"><b>單位</b></th>
				<th scope="col" class="size12 bg-aqua text-nowrap" style="padding: 10px;width:5%;background-color: #FCE4D6;"><b>公司名稱</b></th>
				<th scope="col" class="size12 bg-aqua text-nowrap" style="padding: 10px;width:5%;background-color: #FCE4D6;"><b>聯絡人</b></th>
				<th scope="col" class="size12 bg-aqua text-nowrap" style="padding: 10px;width:5%;background-color: #FCE4D6;"><b>連絡電話</b></th>
				<th scope="col" class="size12 bg-aqua text-nowrap" style="padding: 10px;width:5%;background-color: #FCE4D6;"><b>備註</b></th>
			</tr>
			</thead>
			<tbody>
EOT;
		$show_inquiry .= <<<EOT
			$builder_rows
			$architect_office_rows
    		$contractor_rows
			
			<tr class="text-center" style="border-bottom: 1px solid #000;">
			<td class="size12" style="padding: 10px;background-color: #FCE4D6;">承攬模式</td>
				<td class="size12" style="padding: 10px;">$ContractingModel</td>
				<td class="size12" rowspan="4" style="padding: 10px ;background-color: #D0CECE;">工程敘述</td>
				<td class="size12" style="padding: 10px;background-color: #FCE4D6;">建物棟數/層數</td>
				<td class="size12" style="padding: 10px; white-space: normal;">$buildings</td>
			</tr>
			<tr class="text-center" style="border-bottom: 1px solid #000;">
				<td class="size12" style="padding: 10px;background-color: #FCE4D6;">預定開工</td>
				<td class="size12" style="padding: 10px;">$estimated_arrival_date</td>
				<td class="size12" style="padding: 10px;background-color: #FCE4D6;">承攬總面積(m²)</td>
				$engineering_qty
			</tr>
			<tr class="text-center" style="border-bottom: 1px solid #000;">
				<td class="size12" style="padding: 10px;background-color: #FCE4D6;">預定完工</td>
				<td class="size12" style="padding: 10px;">$completion_date</td>
				<td class="size12" rowspan="2" style="padding: 10px;background-color: #FCE4D6;">標準層面積(m²)</td>
				$std_layer_template_qty                   
			</tr>
			<tr class="text-center" style="border-bottom: 1px solid #000;">
				<td class="size12" style="padding: 10px;background-color: #FCE4D6;">預估人力需求</td>
				<td class="size12" style="padding: 10px;">$total_standard_manpower</td>
			</tr>
			<tr class="text-center" style="border-bottom: 1px solid #000;">
				<th scope="col" class="size12 bg-aqua text-nowrap" colspan="2" style="padding: 10px;width:2%;background-color: #FCE4D6;"><b>協力廠商</b></th>
				<th scope="col" class="size12 bg-aqua text-nowrap" style="padding: 10px;width:5%;background-color: #FCE4D6;"><b>人數</b></th>
				<th scope="col" class="size12 bg-aqua text-nowrap" style="padding: 10px;width:5%;background-color: #FCE4D6;"><b>聯絡人</b></th>
				<th scope="col" class="size12 bg-aqua text-nowrap" style="padding: 10px;width:5%;background-color: #FCE4D6;"><b>連絡電話</b></th>
			</tr>
				<tr class="text-center" style="border-bottom: 1px solid #000;">
					$show_subcontractor
				</tr>
			

			<tr class="text-center" style="border-bottom: 1px solid #000;">
				$show_layout
			</tr>

			
			<tr class="text-center" style="border-bottom: 1px solid #000;">
			<td class="size12" style="padding: 10px;background-color: #FCE4D6;">設計人員</td>
			<td class="size12" style="padding: 10px;">$handler_name-$handler_mobile</td>
			<td class="size12" rowspan="4" style="padding: 10px ;background-color: #D0CECE;">工地地址/<br>計價資料親送地址</td>
			<td class="size12" rowspan="4" colspan="2"  style="padding: 10px ;background-color: #FFF2CC;"><a style="color: blue;" href="$location_URL">$location_URL</a></td>
			
		</tr>
		<tr class="text-center" style="border-bottom: 1px solid #000;">
			<td class="size12" style="padding: 10px;background-color: #FCE4D6;">工務人員</td>
			<td class="size12" style="padding: 10px;">$responsible_name-$responsible_mobile</td>
		</tr>
		<tr class="text-center" style="border-bottom: 1px solid #000;">
			<td class="size12" style="padding: 10px;background-color: #FCE4D6;">上包計價人員</td>
			<td class="size12" style="padding: 10px;">$maincontractor_pricing_staff-$maincontractor_pricing_staff_mobile</td>          
		</tr>
		<tr class="text-center" style="border-bottom: 1px solid #000;">
			<td class="size12" style="padding: 10px;background-color: #FCE4D6;">下包計價人員</td>
			<td class="size12" style="padding: 10px;">$subcontractor_pricing_staff-$subcontractor_pricing_staff_mobile</td>
		</tr>

		</tbody>
	</table>
EOT;

	}

} else {

	$show_inquiry = "<div class=\"size16 weight text-center m-3\">查無任何資料！</div>";

}

$mDB3->remove();
$mDB2->remove();
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
			<h3>案件基本資料表</h3>
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
	<div class="inline size12 weight text-nowrap vtop mb-2 me-2">案件 : $select_case <button type="button" class="btn btn-success btn-sm" onclick="caseselect();"><i class="fas fa-check"></i>&nbsp;查詢</button></div>
	</div>
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
		var case_id = $('#case_list').val();

		var status_list = [];
    	$('input[name="status_list[]"]:checked').each(function() {
        status_list.push($(this).val());
    });

		window.location = '/index.php?ch=$ch&case_id='+case_id+'&fm=$fm';
		return false;

	}	

$(function(){ 
    $("#case_list").change(function(){ 
        getSelectVal(); 
    }); 
});

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