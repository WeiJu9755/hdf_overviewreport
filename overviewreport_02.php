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

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$region = $_GET['region'] ?? '';
$builder_name = $_GET['builder'] ?? '';
$contractor_name = $_GET['contractor'] ?? '';
$subcontractor_name = $_GET['subcontractor_name'] ?? '';
$manpwer_status = $_GET['manpwer_status'] ?? '';

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

$select_region = "<select class=\"inline form-select form-select-sm\" name=\"region\" id=\"region\" style=\"width:auto;\">";
$select_region .= "<option></option>";

if ($mDB->rowCount() > 0) {
    while ($row = $mDB->fetchRow(2)) {
        $region = $row['region'];
        $selected = ($region == $selected_region) ? "selected" : "";
        $select_region .= "<option value='$region' $selected>$region</option>";
    }
}
$select_region .= "</select>";

// //載入建商
// $Qry = "SELECT b.builder_name FROM CaseManagement a
// LEFT JOIN builder b ON b.builder_id = a.builder_id
// GROUP BY b.builder_name";

// $selected_builder = $_GET['builder'] ?? '';
// $select_builder_name = "<select class=\"inline form-select form-select-sm\" name=\"builder_name\" id=\"builder_name\" style=\"width:auto;\">";
// $select_builder_name .= "<option value=''></option>";

// if ($mDB->rowCount() > 0) {
//     while ($row = $mDB->fetchRow(2)) {
//         $builder_name = $row['builder_name'];
//         $selected = ($builder_name == $selected_builder) ? "selected" : "";
//         $select_builder_name .= "<option value='$builder_name' $selected>$builder_name</option>";
//     }
// }
// $select_builder_name .= "</select>";

// //載入營造商
// $Qry = "SELECT DISTINCT 
//     k.contractor_name  -- 營造商名稱
// FROM CaseManagement a
// LEFT JOIN contractor k ON k.contractor_id = a.contractor_id
// WHERE k.contractor_name IS NOT NULL 
// AND k.contractor_name <> '';";

// $mDB->query($Qry);
// $selected_contractor = $_GET['contractor'] ?? '';
// $select_contractor_name = "<select class=\"inline form-select form-select-sm\" name=\"contractor_name\" id=\"contractor_name\" style=\"width:auto;\">";
// $select_contractor_name .= "<option value=''></option>";

// if ($mDB->rowCount() > 0) {
//     while ($row = $mDB->fetchRow(2)) {
//         $contractor_name = $row['contractor_name'];
//         $selected = ($contractor_name == $selected_contractor) ? "selected" : "";
//         $select_contractor_name .= "<option value='$contractor_name' $selected>$contractor_name</option>";
//     }
// }
$select_contractor_name .= "</select>";

//載入下包商
$Qry = "SELECT DISTINCT 
    subcontractor_name -- 協力下包商名稱
FROM subcontractor a

WHERE subcontractor_name IS NOT NULL AND subcontractor_name != '';";

$mDB->query($Qry);
$selected_subcontractor = $_GET['subcontractor_name'] ?? '';
$select_subcontractor_name = "<select class=\"inline form-select form-select-sm\" name=\"subcontractor_name\" id=\"subcontractor_name\" style=\"width:auto;\">";
$select_subcontractor_name .= "<option value=''></option>";

if ($mDB->rowCount() > 0) {
    while ($row = $mDB->fetchRow(2)) {
        $subcontractor_name = $row['subcontractor_name'];
        $selected = ($subcontractor_name == $selected_subcontractor) ? "selected" : "";
        $select_subcontractor_name .= "<option value='$subcontractor_name' $selected>$subcontractor_name</option>";
    }
}
$select_subcontractor_name .= "</select>";

// 載入人力狀況分類
$selected_manpower = $_GET['manpwer_status'] ?? '';
$select_manpwer_status = "<select class=\"inline form-select form-select-sm\" name=\"manpwer_status\" id=\"manpwer_status\" style=\"width:auto;\">";
$select_manpwer_status .= "<option value=''></option>";
$options = [
    '1' => '標準人力',
    '2' => '自派人力',
    '3' => '實際人力',
    '4' => '人力差額'
];
foreach ($options as $value => $label) {
    $selected = ($value == $selected_manpower) ? "selected" : "";
    $select_manpwer_status .= "<option value='$value' $selected>$label</option>";
}
$select_manpwer_status .= "</select>";


$show_inquiry = "";

// 取得區域和下包商

$Qry = "SELECT 
                a.case_id,
                a.builder_id,
                b.region,
                c.subcontractor_name AS subcontractor_name
            FROM overview_sub a
            LEFT JOIN CaseManagement b ON b.case_id = a.case_id
            LEFT JOIN subcontractor c ON c.subcontractor_id = a.builder_id
            WHERE a.builder_id IS NOT NULL 
            AND c.subcontractor_name IS NOT NULL"; // 先過濾 NULL


if (!empty($_GET['region'])) {
    $Qry .= " AND b.region = '{$_GET['region']}'";
}
if (!empty($_GET['subcontractor_name'])) {
    $Qry .= " AND c.subcontractor_name = '{$_GET['subcontractor_name']}'";
}


// 排序條件
$Qry .= " ORDER BY 
            CASE 
                WHEN b.region = '北部' THEN 1 
                WHEN b.region = '中部' THEN 2 
                WHEN b.region = '南部' THEN 3 
                ELSE 4 
            END;";



$mDB->query($Qry);
$regions = [];
$subcontractor_names = [];
$unique_data = []; // 用來存儲唯一組合

if ($mDB->rowCount() > 0) {
    while ($row = $mDB->fetchRow(2)) {
        // 建立唯一的 key (可以用 JSON 或序列化方式)
        $key = $row['region'] . '_' . $row['subcontractor_name'];

        if (!isset($unique_data[$key])) {
            $unique_data[$key] = true; // 記錄這組 key 已經出現過
            $regions[] = $row['region'];
            $subcontractor_names[] = $row['subcontractor_name'];
        }
    }

$region_list = "'" . implode("','", $regions) . "'";
$subcontractor_list = "'" . implode("','", $subcontractor_names) . "'";

// 取得想同區域及下包商的人力資料
$Qry2 = "SELECT a.case_id,
       b.region,
       a.seq,
       c.builder_id,
       e.subcontractor_name,
       a.engineering_date,
       a.standard_manpower,
       a.available_manpower,
       a.manpower_gap,
       c.construction_days_per_floor
       
        FROM overview_manpower_sub a
        LEFT JOIN CaseManagement b ON b.case_id = a.case_id
        LEFT JOIN overview_building c ON c.case_id = a.case_id AND c.seq = a.seq
        LEFT JOIN subcontractor e ON e.subcontractor_id = c.builder_id
WHERE c.builder_id IS NOT NULL 
AND b.region IN ($region_list)
AND e.subcontractor_name IN ($subcontractor_list)
ORDER BY 
    CASE 
        WHEN b.region = '北部' THEN 1 
        WHEN b.region = '中部' THEN 2 
        WHEN b.region = '南部' THEN 3 
        ELSE 4 
    END";
$month_per_manpower_rows = []; // 儲存每個月份的人力資料
$mDB2->query($Qry2);
if ($mDB2->rowCount() > 0) {
    while ($row = $mDB2->fetchRow(2)) {
        $month_per_manpower_rows[] = [
            'region' => $row['region'],
            'subcontractor_name' => $row['subcontractor_name'],
            'engineering_date' => $row['engineering_date'],
            'standard_manpower' => $row['standard_manpower'],
            'construction_days_per_floor' => $row['construction_days_per_floor'],
            'engineering_end_date' => date('Y-m-d', strtotime($row['engineering_date'] . " + " . $row['construction_days_per_floor'] . " days")),
            'available_manpower' => $row['available_manpower'],
            'manpower_gap' => $row['manpower_gap']

        ];
    }
}

$Qry3 = "SELECT 
    h.case_id,
    f.dispatch_id,
    i.region,
    c.builder_id,
    e.subcontractor_name,
    f.dispatch_date,
    g.manpower
FROM dispatch f
LEFT JOIN dispatch_construction g ON g.dispatch_id = f.dispatch_id
LEFT JOIN construction h ON h.construction_id = g.construction_id
LEFT JOIN CaseManagement i ON i.case_id = h.case_id
LEFT JOIN overview_building c ON c.case_id = i.case_id
LEFT JOIN subcontractor e ON e.subcontractor_id = c.builder_id 
WHERE c.builder_id IS NOT NULL 
AND i.region IN ($region_list)
AND e.subcontractor_name IN ($subcontractor_list)
GROUP BY f.dispatch_id

ORDER BY 
    CASE 
        WHEN i.region = '北部' THEN 1 
        WHEN i.region = '中部' THEN 2 
        WHEN i.region = '南部' THEN 3 
        ELSE 4 
    END";

$month_per_real_manpower_rows = []; // 儲存每個月份的人力資料
$mDB3->query($Qry3);
if ($mDB3->rowCount() > 0) {
    while ($row3 = $mDB3->fetchRow(2)) {
        $month_per_real_manpower_rows[] = [
            'region' => $row3['region'],
            'subcontractor_name' => $row3['subcontractor_name'],
            'dispatch_date' => $row3['dispatch_date'],
            'real_manpower' => $row3['manpower'],
            
            
        ];
    }
}



$manpowerByMonth = [];
$show_inquiry = '';

$tmp_start_date = $_GET['start_date'] . "-01";
$tmp_end_date = $_GET['end_date'] . "-01";

// 計算月份差距
$diff = date_diff(date_create($tmp_start_date), date_create($tmp_end_date));
$month_count = ($diff->y * 12) + $diff->m + ($diff->d > 0 ? 1 : 0);

// 生成所有月份陣列
$allMonths = [];
for ($i = 0; $i <= $month_count; $i++) {
    $month = date('Y-m', strtotime($tmp_start_date . " +$i months"));
    $allMonths[$month] = 0;
}


$manpowerByMonth = [];


// 整理標準人力數據
foreach ($month_per_manpower_rows as $month_per_manpower_row) {
    $start = new DateTime($month_per_manpower_row['engineering_date']);
    $end = new DateTime($month_per_manpower_row['engineering_end_date']);

    $regionKey = $month_per_manpower_row['region'];
    $subcontractor_name = $month_per_manpower_row['subcontractor_name'];

    while ($start <= $end) {
        $monthKey = $start->format('Y-m');

        if (!isset($manpowerByMonth[$regionKey])) {
            $manpowerByMonth[$regionKey] = [];
        }

        if (!isset($manpowerByMonth[$regionKey][$subcontractor_name])) {
            $manpowerByMonth[$regionKey][$subcontractor_name] = [];
        }

        if (!isset($manpowerByMonth[$regionKey][$subcontractor_name][$monthKey])) {
            $manpowerByMonth[$regionKey][$subcontractor_name][$monthKey] = 0;
        }


        $manpowerByMonth[$regionKey][$subcontractor_name][$monthKey] += (int) $month_per_manpower_row['standard_manpower'];

        $start->modify('first day of next month');
    }
}

// 計算 $total_rowspan (所有區域內的包商總數)
$total_rowspan = 0;
foreach ($manpowerByMonth as $region => $subcontractors) {
    $total_rowspan += count($subcontractors); // 只計算子承包商數量
}
$total_rowspan += 4;

// 生成表頭
$calendar_months = '';
foreach (array_keys($allMonths) as $month) {
    $calendar_months .= "<th scope='col' class='size12 bg-aqua text-nowrap' style='padding: 10px; background-color: #E2EFDA;'><b>$month</b></th>";
}

$show_inquiry = <<<EOT
<div class="table-responsive">
    <table class="table table-bordered" style="border: 2px solid #000; background-color: #FFFFFF; margin: 0 auto; width: 80%; max-width: 800px;">
        <thead>
            <tr class="text-center" style="border-bottom: 2px solid #000;">
                <th scope="col" colspan="3" class="size12 bg-aqua text-nowrap" style="padding: 10px; background-color: #E2EFDA;">
                    <b>月份</b>
                </th>
                $calendar_months
            </tr>
        </thead>
        <tbody>
EOT;

$first_row = true; // 用來確保 "項目總計" 只出現在第一行
$second_row = true; // 用來確保 "項目總計" 只出現在第二行
$third_row = true; // 用來確保 "項目總計" 只出現在第三行
$real_row = true; // 用來確保 "項目總計" 只出現在第三行




if (in_array($manpwer_status, ['1', '', '3', '4'])) {
    // 生成表格數據
    foreach ($manpowerByMonth as $region => $subcontractors) {
        $rowspan = count($subcontractors); // 計算該區域內的包商數量
        $first_subcontractor = true; // 標記是否為該區域的第一個包商

        foreach ($subcontractors as $subcontractor => $months) {
            $show_manpower_low = '';

            // 顯示每個月的數據
            foreach (array_keys($allMonths) as $month) {
                $show_manpower = isset($months[$month]) ? $months[$month] : 0;
                $show_manpower_low .= "<td class='size12 bg-aqua text-nowrap' style='padding: 10px;'><b>$show_manpower</b></td>";
            }

            // 開始新增表格行
            $show_inquiry .= "<tr>";


            if ($first_row) {
                $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' rowspan='$total_rowspan' style='padding: 10px; background-color: #FFE7BA;border-bottom : 3px solid black;'><b>標準人力</b></td>";
                $first_row = false; // 之後的行不再添加這個欄位
            }

            // 只在該區域的第一個包商列出 `rowspan`
            if ($first_subcontractor) {
                $region_colors = [
                    '北部' => 'rgb(183, 215, 233)',
                    '中部' => '#D6C3E5',
                    '南部' => 'rgb(124, 235, 220)'
                ];

                $color = $region_colors[$region] ?? 'default_color'; // 如果 $region 不在陣列中，可設定預設顏色
                $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' rowspan='$rowspan' style='padding: 10px; background-color:$color;'><b>$region</b></td>";
                $first_subcontractor = false; // 之後的包商不再顯示該區域名稱
            }

            // 加入包商名稱及數據
            $show_inquiry .= "
            <td class='size12 bg-aqua text-nowrap' style='padding: 10px;'><b>$subcontractor</b></td>
            $show_manpower_low
        </tr>";
        }
    }
    // 首先計算每個區域每個月的人力總和，以及所有區域的每月總計
    $region_totals = [
        '北部' => [],
        '中部' => [],
        '南部' => []
    ];
    $grand_totals = []; // 新增用於儲存所有區域每月總計的陣列

    foreach ($manpowerByMonth as $region => $subcontractors) {
        foreach ($subcontractors as $subcontractor => $months) {
            foreach (array_keys($allMonths) as $month) {
                if (!isset($region_totals[$region][$month])) {
                    $region_totals[$region][$month] = 0;
                }
                if (!isset($grand_totals[$month])) {
                    $grand_totals[$month] = 0;
                }
                $value = isset($months[$month]) ? $months[$month] : 0;
                $region_totals[$region][$month] += $value;
                $grand_totals[$month] += $value; // 計算所有區域的總計
            }
        }
    }

    $show_inquiry .= "<tr style='border-top: 2px solid #000;'>"; // 添加分隔線

    // 添加北部總計
    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: rgb(183, 215, 233);'><b>北部</b></td>";
    foreach (array_keys($allMonths) as $month) {
        $north_total = isset($region_totals['北部'][$month]) ? $region_totals['北部'][$month] : 0;
        $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px;'><b>$north_total</b></td>";
    }
    $show_inquiry .= "</tr>";

    // 添加中部總計
    $show_inquiry .= "<tr>";

    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: #D6C3E5;'><b>中部</b></td>";
    foreach (array_keys($allMonths) as $month) {
        $central_total = isset($region_totals['中部'][$month]) ? $region_totals['中部'][$month] : 0;
        $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px;'><b>$central_total</b></td>";
    }
    $show_inquiry .= "</tr>";

    // 添加南部總計
    $show_inquiry .= "<tr>";

    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: rgb(124, 235, 220);'><b>南部</b></td>";
    foreach (array_keys($allMonths) as $month) {
        $south_total = isset($region_totals['南部'][$month]) ? $region_totals['南部'][$month] : 0;
        $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px;'><b>$south_total</b></td>";
    }
    $show_inquiry .= "</tr>";

    // 添加所有區域的每月總計
    $show_inquiry .= "<tr style='border-top: 2px solid #000;'>"; // 添加分隔線
    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: #FFD700;border-bottom : 3px solid black;'><b>小計</b></td>";
    foreach (array_keys($allMonths) as $month) {
        $grand_total = isset($grand_totals[$month]) ? $grand_totals[$month] : 0;
        $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px; background-color: #FFFFE0;border-bottom : 3px solid black;'><b>$grand_total</b></td>";
    }
    $show_inquiry .= "</tr>";
}




// 自派人力表格
if (in_array($manpwer_status, ['2', '', '3', '4'])) {
    $manpowerByMonth = [];

    foreach ($month_per_manpower_rows as $month_per_manpower_row) {
        $start = new DateTime($month_per_manpower_row['engineering_date']);
        $end = new DateTime($month_per_manpower_row['engineering_end_date']);

        $regionKey = $month_per_manpower_row['region'];
        $subcontractor_name = $month_per_manpower_row['subcontractor_name'];

        while ($start <= $end) {
            $monthKey = $start->format('Y-m');

            if (!isset($manpowerByMonth[$regionKey])) {
                $manpowerByMonth[$regionKey] = [];
            }

            if (!isset($manpowerByMonth[$regionKey][$subcontractor_name])) {
                $manpowerByMonth[$regionKey][$subcontractor_name] = [];
            }

            if (!isset($manpowerByMonth[$regionKey][$subcontractor_name][$monthKey])) {
                $manpowerByMonth[$regionKey][$subcontractor_name][$monthKey] = 0;
            }


            $manpowerByMonth[$regionKey][$subcontractor_name][$monthKey] += (int) $month_per_manpower_row['available_manpower'];

            $start->modify('first day of next month');
        }
    }

    

    // 生成表格數據
    foreach ($manpowerByMonth as $region => $subcontractors) {
        $rowspan = count($subcontractors); // 計算該區域內的包商數量
        $first_subcontractor = true; // 標記是否為該區域的第一個包商

        foreach ($subcontractors as $subcontractor => $months) {
            $show_manpower_low = '';

            // 顯示每個月的數據
            foreach (array_keys($allMonths) as $month) {
                $show_manpower = isset($months[$month]) ? $months[$month] : 0;
                $show_manpower_low .= "<td class='size12 bg-aqua text-nowrap' style='padding: 10px;'><b>$show_manpower</b></td>";
            }

            // 開始新增表格行
            $show_inquiry .= "<tr>";


            if ($second_row) {
                $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' rowspan='$total_rowspan' style='padding: 10px; background-color: rgb(139, 201, 196);border-bottom : 3px solid black;'><b>自派人力</b></td>";
                $second_row = false; // 之後的行不再添加這個欄位
            }

            // 只在該區域的第一個包商列出 `rowspan`
            if ($first_subcontractor) {
                $region_colors = [
                    '北部' => 'rgb(183, 215, 233)',
                    '中部' => '#D6C3E5',
                    '南部' => 'rgb(124, 235, 220)'
                ];

                $color = $region_colors[$region] ?? 'default_color'; // 如果 $region 不在陣列中，可設定預設顏色
                $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' rowspan='$rowspan' style='padding: 10px; background-color:$color;'><b>$region</b></td>";
                $first_subcontractor = false; // 之後的包商不再顯示該區域名稱
            }

            // 加入包商名稱及數據
            $show_inquiry .= "
            <td class='size12 bg-aqua text-nowrap' style='padding: 10px;'><b>$subcontractor</b></td>
            $show_manpower_low
        </tr>";
        }
    }
    // 首先計算每個區域每個月的人力總和，以及所有區域的每月總計
    $region_totals = [
        '北部' => [],
        '中部' => [],
        '南部' => []
    ];
    $grand_totals = []; // 新增用於儲存所有區域每月總計的陣列

    foreach ($manpowerByMonth as $region => $subcontractors) {
        foreach ($subcontractors as $subcontractor => $months) {
            foreach (array_keys($allMonths) as $month) {
                if (!isset($region_totals[$region][$month])) {
                    $region_totals[$region][$month] = 0;
                }
                if (!isset($grand_totals[$month])) {
                    $grand_totals[$month] = 0;
                }
                $value = isset($months[$month]) ? $months[$month] : 0;
                $region_totals[$region][$month] += $value;
                $grand_totals[$month] += $value; // 計算所有區域的總計
            }
        }
    }

    // 在現有的表格生成程式碼之後，在關閉 tbody 之前加入以下內容：

    $show_inquiry .= "<tr style='border-top: 2px solid #000;'>"; // 添加分隔線

    // 添加北部總計
    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: rgb(183, 215, 233);'><b>北部</b></td>";
    foreach (array_keys($allMonths) as $month) {
        $north_total = isset($region_totals['北部'][$month]) ? $region_totals['北部'][$month] : 0;
        $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px;'><b>$north_total</b></td>";
    }
    $show_inquiry .= "</tr>";

    // 添加中部總計
    $show_inquiry .= "<tr>";

    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: #D6C3E5;'><b>中部</b></td>";
    foreach (array_keys($allMonths) as $month) {
        $central_total = isset($region_totals['中部'][$month]) ? $region_totals['中部'][$month] : 0;
        $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px;'><b>$central_total</b></td>";
    }
    $show_inquiry .= "</tr>";

    // 添加南部總計
    $show_inquiry .= "<tr>";

    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: rgb(124, 235, 220);'><b>南部</b></td>";
    foreach (array_keys($allMonths) as $month) {
        $south_total = isset($region_totals['南部'][$month]) ? $region_totals['南部'][$month] : 0;
        $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px;'><b>$south_total</b></td>";
    }
    $show_inquiry .= "</tr>";

    // 添加所有區域的每月總計
    $show_inquiry .= "<tr style='border-top: 2px solid #000;'>"; // 添加分隔線
    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: #FFD700;border-bottom : 3px solid black;'><b>小計</b></td>";
    foreach (array_keys($allMonths) as $month) {
        $grand_total = isset($grand_totals[$month]) ? $grand_totals[$month] : 0;
        $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px; background-color: #FFFFE0;border-bottom : 3px solid black;'><b>$grand_total</b></td>";
    }
    $show_inquiry .= "</tr>";
}

// 實際出工人數
if (in_array($manpwer_status, ['3', ''])) {
    $manpowerByMonth = [];

foreach ($month_per_real_manpower_rows as $month_per_real_manpower_row) {
    $date = new DateTime($month_per_real_manpower_row['dispatch_date']);
    $monthKey = $date->format('Y-m');

    $regionKey = $month_per_real_manpower_row['region'];
    $subcontractor_name = $month_per_real_manpower_row['subcontractor_name'];

    if (!isset($manpowerByMonth[$regionKey])) {
        $manpowerByMonth[$regionKey] = [];
    }

    if (!isset($manpowerByMonth[$regionKey][$subcontractor_name])) {
        $manpowerByMonth[$regionKey][$subcontractor_name] = [];
    }

    if (!isset($manpowerByMonth[$regionKey][$subcontractor_name][$monthKey])) {
        $manpowerByMonth[$regionKey][$subcontractor_name][$monthKey] = 0;
    }

    $manpowerByMonth[$regionKey][$subcontractor_name][$monthKey] += (int) $month_per_real_manpower_row['real_manpower'];
}

$total_rowspan2 = 0;
    foreach ($manpowerByMonth as $region => $subcontractors) {
        $total_rowspan2 += count($subcontractors); // 只計算子承包商數量
    }
    $total_rowspan2 += 4;


    // 生成表格數據
    foreach ($manpowerByMonth as $region => $subcontractors) {
        $rowspan = count($subcontractors); // 計算該區域內的包商數量
        $first_subcontractor = true; // 標記是否為該區域的第一個包商

        foreach ($subcontractors as $subcontractor => $months) {
            $show_manpower_low = '';

            // 顯示每個月的數據
            foreach (array_keys($allMonths) as $month) {
                $show_manpower = isset($months[$month]) ? $months[$month] : 0;
                $show_manpower_low .= "<td class='size12 bg-aqua text-nowrap' style='padding: 10px;'><b>$show_manpower</b></td>";
            }

            // 開始新增表格行
            $show_inquiry .= "<tr>";


            if ($real_row) {
                $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' rowspan='$total_rowspan2' style='padding: 10px; background-color: rgba(110, 241, 143, 1);border-bottom : 3px solid black;'><b>實際出工人力</b></td>";
                $real_row = false; // 之後的行不再添加這個欄位
            }

            // 只在該區域的第一個包商列出 `rowspan`
            if ($first_subcontractor) {
                $region_colors = [
                    '北部' => 'rgb(183, 215, 233)',
                    '中部' => '#D6C3E5',
                    '南部' => 'rgb(124, 235, 220)'
                ];

                $color = $region_colors[$region] ?? 'default_color'; // 如果 $region 不在陣列中，可設定預設顏色
                $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' rowspan='$rowspan' style='padding: 10px; background-color:$color;'><b>$region</b></td>";
                $first_subcontractor = false; // 之後的包商不再顯示該區域名稱
            }

            // 加入包商名稱及數據
            $show_inquiry .= "
            <td class='size12 bg-aqua text-nowrap' style='padding: 10px;'><b>$subcontractor</b></td>
            $show_manpower_low
        </tr>";
        }
    }
    // 首先計算每個區域每個月的人力總和，以及所有區域的每月總計
    $region_totals = [
        '北部' => [],
        '中部' => [],
        '南部' => []
    ];
    $grand_totals = []; // 新增用於儲存所有區域每月總計的陣列

    foreach ($manpowerByMonth as $region => $subcontractors) {
        foreach ($subcontractors as $subcontractor => $months) {
            foreach (array_keys($allMonths) as $month) {
                if (!isset($region_totals[$region][$month])) {
                    $region_totals[$region][$month] = 0;
                }
                if (!isset($grand_totals[$month])) {
                    $grand_totals[$month] = 0;
                }
                $value = isset($months[$month]) ? $months[$month] : 0;
                $region_totals[$region][$month] += $value;
                $grand_totals[$month] += $value; // 計算所有區域的總計
            }
        }
    }

    // 在現有的表格生成程式碼之後，在關閉 tbody 之前加入以下內容：

    $show_inquiry .= "<tr style='border-top: 2px solid #000;'>"; // 添加分隔線

    // 添加北部總計
    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: rgb(183, 215, 233);'><b>北部</b></td>";
    foreach (array_keys($allMonths) as $month) {
        $north_total = isset($region_totals['北部'][$month]) ? $region_totals['北部'][$month] : 0;
        $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px;'><b>$north_total</b></td>";
    }
    $show_inquiry .= "</tr>";

    // 添加中部總計
    $show_inquiry .= "<tr>";

    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: #D6C3E5;'><b>中部</b></td>";
    foreach (array_keys($allMonths) as $month) {
        $central_total = isset($region_totals['中部'][$month]) ? $region_totals['中部'][$month] : 0;
        $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px;'><b>$central_total</b></td>";
    }
    $show_inquiry .= "</tr>";

    // 添加南部總計
    $show_inquiry .= "<tr>";

    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: rgb(124, 235, 220);'><b>南部</b></td>";
    foreach (array_keys($allMonths) as $month) {
        $south_total = isset($region_totals['南部'][$month]) ? $region_totals['南部'][$month] : 0;
        $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px;'><b>$south_total</b></td>";
    }
    $show_inquiry .= "</tr>";

    // 添加所有區域的每月總計
    $show_inquiry .= "<tr style='border-top: 2px solid #000;'>"; // 添加分隔線
    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: #FFD700;border-bottom : 3px solid black;'><b>小計</b></td>";
    foreach (array_keys($allMonths) as $month) {
        $grand_total = isset($grand_totals[$month]) ? $grand_totals[$month] : 0;
        $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px; background-color: #FFFFE0;border-bottom : 3px solid black;'><b>$grand_total</b></td>";
    }
    $show_inquiry .= "</tr>";
}

// 人力差額
// 實際出工人數
if (in_array($manpwer_status, ['4', ''])) {

$manpowerByMonth = [];

foreach ($month_per_manpower_rows as $month_per_manpower_row) {
    $start = new DateTime($month_per_manpower_row['engineering_date']);
    $end = new DateTime($month_per_manpower_row['engineering_end_date']);

    $regionKey = $month_per_manpower_row['region'];
    $subcontractor_name = $month_per_manpower_row['subcontractor_name'];

    while ($start <= $end) {
        $monthKey = $start->format('Y-m');

        if (!isset($manpowerByMonth[$regionKey])) {
            $manpowerByMonth[$regionKey] = [];
        }

        if (!isset($manpowerByMonth[$regionKey][$subcontractor_name])) {
            $manpowerByMonth[$regionKey][$subcontractor_name] = [];
        }

        if (!isset($manpowerByMonth[$regionKey][$subcontractor_name][$monthKey])) {
            $manpowerByMonth[$regionKey][$subcontractor_name][$monthKey] = 0;
        }


        $manpowerByMonth[$regionKey][$subcontractor_name][$monthKey] += (int) $month_per_manpower_row['manpower_gap'];

        $start->modify('first day of next month');
    }
}

// 生成表格數據
foreach ($manpowerByMonth as $region => $subcontractors) {
    $rowspan = count($subcontractors); // 計算該區域內的包商數量
    $first_subcontractor = true; // 標記是否為該區域的第一個包商

    foreach ($subcontractors as $subcontractor => $months) {
        $show_manpower_low = '';

        // 顯示每個月的數據
        foreach (array_keys($allMonths) as $month) {
            $show_manpower = isset($months[$month]) ? $months[$month] : 0;
            $show_manpower_low .= "<td class='size12 bg-aqua text-nowrap' style='padding: 10px;'><b>$show_manpower</b></td>";
        }

        // 開始新增表格行
        $show_inquiry .= "<tr>";


        if ($third_row) {
            $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' rowspan='$total_rowspan' style='padding: 10px; background-color: #FFE4E1;border-bottom : 3px solid black;'><b>人力差額</b></td>";
            $third_row = false; // 之後的行不再添加這個欄位
        }

        // 只在該區域的第一個包商列出 `rowspan`
        if ($first_subcontractor) {
            $region_colors = [
                '北部' => 'rgb(183, 215, 233)',
                '中部' => '#D6C3E5',
                '南部' => 'rgb(124, 235, 220)'
            ];

            $color = $region_colors[$region] ?? 'default_color'; // 如果 $region 不在陣列中，可設定預設顏色
            $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' rowspan='$rowspan' style='padding: 10px; background-color:$color;'><b>$region</b></td>";
            $first_subcontractor = false; // 之後的包商不再顯示該區域名稱
        }

        // 加入包商名稱及數據
        $show_inquiry .= "
                <td class='size12 bg-aqua text-nowrap' style='padding: 10px;'><b>$subcontractor</b></td>
                $show_manpower_low
            </tr>";
    }
}
// 首先計算每個區域每個月的人力總和，以及所有區域的每月總計
$region_totals = [
    '北部' => [],
    '中部' => [],
    '南部' => []
];
$grand_totals = []; // 新增用於儲存所有區域每月總計的陣列

foreach ($manpowerByMonth as $region => $subcontractors) {
    foreach ($subcontractors as $subcontractor => $months) {
        foreach (array_keys($allMonths) as $month) {
            if (!isset($region_totals[$region][$month])) {
                $region_totals[$region][$month] = 0;
            }
            if (!isset($grand_totals[$month])) {
                $grand_totals[$month] = 0;
            }
            $value = isset($months[$month]) ? $months[$month] : 0;
            $region_totals[$region][$month] += $value;
            $grand_totals[$month] += $value; // 計算所有區域的總計
        }
    }
}

// 在現有的表格生成程式碼之後，在關閉 tbody 之前加入以下內容：

$show_inquiry .= "<tr style='border-top: 2px solid #000;'>"; // 添加分隔線

// 添加北部總計
$show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: rgb(183, 215, 233);'><b>北部</b></td>";
foreach (array_keys($allMonths) as $month) {
    $north_total = isset($region_totals['北部'][$month]) ? $region_totals['北部'][$month] : 0;
    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px;'><b>$north_total</b></td>";
}
$show_inquiry .= "</tr>";

// 添加中部總計
$show_inquiry .= "<tr>";

$show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: #D6C3E5;'><b>中部</b></td>";
foreach (array_keys($allMonths) as $month) {
    $central_total = isset($region_totals['中部'][$month]) ? $region_totals['中部'][$month] : 0;
    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px;'><b>$central_total</b></td>";
}
$show_inquiry .= "</tr>";

// 添加南部總計
$show_inquiry .= "<tr>";

$show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: rgb(124, 235, 220);'><b>南部</b></td>";
foreach (array_keys($allMonths) as $month) {
    $south_total = isset($region_totals['南部'][$month]) ? $region_totals['南部'][$month] : 0;
    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px;'><b>$south_total</b></td>";
}
$show_inquiry .= "</tr>";

// 添加所有區域的每月總計
$show_inquiry .= "<tr style='border-top: 2px solid #000;'>"; // 添加分隔線
$show_inquiry .= "<td class='size12 bg-aqua text-nowrap' colspan='2' style='padding: 2px; background-color: #FFD700; border-bottom : 3px solid black;'><b>小計</b></td>";
foreach (array_keys($allMonths) as $month) {
    $grand_total = isset($grand_totals[$month]) ? $grand_totals[$month] : 0;
    $show_inquiry .= "<td class='size12 bg-aqua text-nowrap' style='padding: 2px; background-color: #FFFFE0;border-bottom : 3px solid black;'><b>$grand_total</b></td>";
}
$show_inquiry .= "</tr>";
}

$show_inquiry .= '</tbody></table></div>';


}else{
    $show_inquiry.= <<<EOT
	<div class="size16 weight p-5 text-center">無任何符合查詢的資料</div>
EOT;
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
			<h3>人力計算報表</h3>
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
   <div class="inline size12 weight text-nowrap pt-2 vtop mb-2">日期區間: </div>
	<div class="inline mb-2">
		<div class="input-group" id="startdate" style="width:100%;max-width:180px;">
			<input type="text" class="form-control" id="start_date" name="start_date" placeholder="請輸入起始日期" aria-describedby="start_date" value="$start_date">
			<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#startdate" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
		</div>
		<script type="text/javascript">
			$(function () {
				$('#startdate').datetimepicker({
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
	<div class="inline mb-2">
		<div class="input-group" id="enddate" style="width:100%;max-width:180px;">
			<input type="text" class="form-control" id="end_date" name="end_date" placeholder="請輸入迄止日期" aria-describedby="end_date" value="$end_date">
			<button class="btn btn-outline-secondary input-group-append input-group-addon" type="button" data-target="#enddate" data-toggle="datetimepicker"><i class="bi bi-calendar"></i></button>
		</div>
		<script type="text/javascript">
			$(function () {
				$('#enddate').datetimepicker({
					locale: 'zh-tw'
					,format:"YYYY-MM"
					,allowInputToggle: true
				});
			});
		</script>
	</div>
	 區域: $select_region 下包商:$select_subcontractor_name 人力狀況:$select_manpwer_status <button type="button" class="btn btn-success btn-sm" onclick="caseselect();"><i class="fas fa-check"></i>&nbsp;查詢</button></div>
	</div>
</div>
<div class="w-100 px-3 mb-5">
    	<div class="text-center"style="font-size: 14px; font-weight: bold;">
			預計人力統整(不含未報價)
		</div>
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
    var start_date = $('#start_date').val();
    var end_date = $('#end_date').val();
    var region = $('#region').val();
    var builder_name = $('#builder_name').val();
    var contractor_name = $('#contractor_name').val();
    var subcontractor_name = $('#subcontractor_name').val();
    var manpwer_status = $('#manpwer_status').val();

    var status_list = [];
    $('input[name="status_list[]"]:checked').each(function() {
        status_list.push($(this).val());
    });

    window.location = '/index.php?ch=$ch&fm=$fm'
                      + '&start_date=' + start_date 
                      + '&end_date=' + end_date 
                      + '&region=' + region 
                      + '&builder=' + builder_name 
                      + '&contractor=' + contractor_name 
                      + '&subcontractor_name=' + subcontractor_name 
                      + '&manpwer_status=' + manpwer_status;
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