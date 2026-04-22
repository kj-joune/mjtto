<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-04-10 13:55:00  */

include_once __DIR__ . '/_admin_common.php';

require_once G5_PATH.'/vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$auth = mjtto_require_admin();
$issue_id = isset($_GET['issue_id']) ? (int)$_GET['issue_id'] : 0;

if ($issue_id < 1) {
    die('잘못된 접근');
}

if (!function_exists('mjtto_print_weekday_kr')) {
    function mjtto_print_weekday_kr($date_string)
    {
        if (!$date_string || $date_string === '0000-00-00' || $date_string === '0000-00-00 00:00:00') {
            return '';
        }

        $ts = strtotime($date_string);
        if (!$ts) {
            return '';
        }

        $days = array('일', '월', '화', '수', '목', '금', '토');

        return $days[(int)date('w', $ts)];
    }
}

if (!function_exists('mjtto_print_datetime_kr')) {
    function mjtto_print_datetime_kr($date_string, $include_time = true)
    {
        if (!$date_string || $date_string === '0000-00-00' || $date_string === '0000-00-00 00:00:00') {
            return '-';
        }

        $ts = strtotime($date_string);
        if (!$ts) {
            return get_text($date_string);
        }

        $formatted = date('Y/m/d', $ts);
        $weekday = mjtto_print_weekday_kr($date_string);

        if ($weekday !== '') {
            $formatted .= ' ('.$weekday.')';
        }

        if ($include_time) {
            $formatted .= ' '.date('H:i:s', $ts);
        }

        return $formatted;
    }
}

if (!function_exists('mjtto_print_date_kr')) {
    function mjtto_print_date_kr($date_string)
    {
        return mjtto_print_datetime_kr($date_string, false);
    }
}

$issue = mjtto_get_issue_row($issue_id, $auth);

if (!$issue || empty($issue['issue_id'])) {
    die('발권 정보를 찾을 수 없습니다.');
}

$result = sql_query("
    SELECT issue_item_id, ticket_no, round_no, num_a, num_b, num_c, num_d, num_e, num_f
      FROM mz_issue_item
     WHERE issue_id = '{$issue_id}'
     ORDER BY issue_item_id ASC
");

$options = new QROptions(array(
    'outputType' => QRCode::OUTPUT_MARKUP_SVG,
    'eccLevel'   => QRCode::ECC_M,
    'scale'      => 4,
));

$items = array();
while ($row = sql_fetch_array($result)) {
    $row['win_url'] = 'https://adm.mjtto.com/win.php?no='.urlencode($row['ticket_no']);
    $items[] = $row;
}

$issue_game_count = (int)$issue['issue_game_count'];
if ($issue_game_count < 1 || $issue_game_count > 5) {
    $issue_game_count = 5;
}

$chunks = array_chunk($items, $issue_game_count);

$qr = new QRCode($options);

foreach ($chunks as $chunk_key => $chunk_rows) {
    if (!empty($chunk_rows[0]['win_url'])) {
        $chunks[$chunk_key][0]['qr_src'] = $qr->render($chunk_rows[0]['win_url']);
    }
}

$prize_map = mjtto_get_prize_map($issue['round_no'], $issue['company_id'], $issue['branch_id']);
$labels = array('A', 'B', 'C', 'D', 'E');

$company_line_1 = trim($issue['branch_print_name_1']) !== '' ? trim($issue['branch_print_name_1']) : trim($issue['contract_print_name_1']);
if ($company_line_1 === '') {
    $company_line_1 = trim($issue['contract_name']);
}
$company_line_2 = trim($issue['branch_print_name_2']) !== '' ? trim($issue['branch_print_name_2']) : trim($issue['branch_name']);
$company_tel = trim($issue['branch_tel_no']) !== '' ? trim($issue['branch_tel_no']) : trim($issue['contract_tel_no']);

$draw_text = $issue['draw_date'] ? mjtto_print_date_kr($issue['draw_date']) : '-';
$created_text = mjtto_print_datetime_kr($issue['created_at'], true);
$payout_text = $issue['payout_deadline'] ? mjtto_print_date_kr($issue['payout_deadline']) : '-';
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
@page{margin:0;}
html,body{margin:0;padding:0;background:#fff;color:#000;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
body{font-family:"Malgun Gothic","Apple SD Gothic Neo","Noto Sans KR",sans-serif;}
.ticket{
	width:80mm;
	min-height:118mm;
	padding:3.6mm 9.2mm 4.0mm 4.2mm;box-sizing:border-box;border-bottom:0.2mm solid #d7d7d7;
	position:relative;page-break-after:always;overflow:hidden;background:#fff;
}
.ticket:last-child{page-break-after:auto;}
.side{position:absolute;right:0;top:0;width:6mm;height:100%;background:#111;display:flex;flex-direction:column;justify-content:space-around;align-items:center;}
.side span{display:block;color:#fff;font-weight:700;font-size:3.4mm;line-height:0.98;letter-spacing:0.6mm;writing-mode:vertical-rl;text-orientation:mixed;opacity:0.96;}
.head{display:flex;justify-content:space-between;align-items:flex-start;gap:3.0mm;}
.company{flex:1;padding-top:2.2mm;min-height:22mm;display:flex;flex-direction:column;justify-content:flex-start;gap:0.45mm;}
.company .line1,.company .line2,.company .tel{font-size:4.5mm;line-height:1.25;font-weight:550;letter-spacing:0.05mm;word-break:keep-all;}
.company .tel{margin-top:0.1mm;white-space:nowrap;}
.qr{width:22mm;flex:0 0 22mm;display:flex;justify-content:flex-end;align-items:flex-start;}
.qr img{display:block;width:22mm;height:22mm;}
.title{margin:2.4mm 0 2.4mm;text-align:center;font-size:5.15mm;line-height:1.08;font-weight:900;letter-spacing:-0.10mm;word-break:keep-all;}
.meta{margin-top:0.2mm;font-size:3.0mm;line-height:1.38;font-weight:500;letter-spacing:0;}
.meta-row{display:flex;align-items:flex-start;white-space:nowrap;}
.meta-label{display:inline-block;min-width:15.8mm;padding-right:0.5mm;font-weight:600;}
.meta-value{display:inline-block;flex:1;font-weight:400;}
.serial-wrap{margin:3.0mm 0 2.6mm;min-height:10.4mm;}
.serial-main,.serial-sub{font-size:2.8mm;line-height:1.28;letter-spacing:0.06mm;word-break:break-all;font-weight:400;}
.serial-sub{margin-top:0.5mm;}
.line{border-top:0.34mm dashed #222;margin:2.4mm 0;}
.numbers{margin-top:0.2mm;}
.row{display:flex;align-items:baseline;gap:0.7mm;margin:0.82mm 0;}
.row-label{width:12.8mm;flex:0 0 12.8mm;font-size:4.0mm;line-height:1;font-weight:500;letter-spacing:0.00mm;}
.row-numbers{flex:1;font-size:5.2mm;line-height:1;font-weight:800;letter-spacing:0.06mm;word-spacing:0.28mm;white-space:nowrap;}
.notice{margin-top:1.4mm;font-size:2.55mm;line-height:1.32;font-weight:700;letter-spacing:-0.05mm;word-break:keep-all;}
.notice .bullet{display:flex;align-items:flex-start;gap:0.7mm;margin:0.45mm 0;}
.notice .dot{width:1.8mm;flex:0 0 1.8mm;text-align:center;}
.notice .txt{flex:1;}
.prize{margin-top:1.8mm;font-size:2.78mm;line-height:1.28;font-weight:800;letter-spacing:-0.03mm;word-break:keep-all;} 
.prize-row{margin:0.14mm 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
</style>
</head>
<body>
<?php foreach($chunks as $chunk_index => $set): ?>
<?php
    $first_ticket_no = isset($set[0]['ticket_no']) ? $set[0]['ticket_no'] : '';
    $last_ticket_no = isset($set[count($set) - 1]['ticket_no']) ? $set[count($set) - 1]['ticket_no'] : '';
    $serial_sub = $first_ticket_no;
    if ($last_ticket_no !== '' && $last_ticket_no !== $first_ticket_no) {
        $serial_sub .= ' ~ '.substr($last_ticket_no, 13,12);
    }
?>
<div class="ticket">
    <div class="side">
        <span>mjtto.com</span>
        <span>mjtto.com</span>
        <span>mjtto.com</span>
        <span>mjtto.com</span>
        <span>mjtto.com</span>
    </div>

    <div class="head">
        <div class="company">
            <div class="line1"><?php echo get_text($company_line_1); ?></div>
            <div class="line2"><?php echo get_text($company_line_2); ?></div>
            <div class="tel"><?php echo $company_tel !== '' ? 'TEL. '.get_text($company_tel) : 'TEL.'; ?></div>
        </div>
        <div class="qr"><img src="<?php echo $set[0]['qr_src']; ?>" alt="QR"></div>
    </div>

    <div class="title">매주또 매주행운 제 <?php echo (int)$issue['round_no']; ?> 회</div>

    <div class="meta">
        <div class="meta-row"><span class="meta-label">발 행 일</span><span class="meta-value">: <?php echo get_text($created_text); ?></span></div>
        <div class="meta-row"><span class="meta-label">추 첨 일</span><span class="meta-value">: <?php echo get_text($draw_text); ?></span></div>
        <div class="meta-row"><span class="meta-label">교환기한</span><span class="meta-value">: <?php echo get_text($payout_text); ?></span></div>
    </div>

    <div class="serial-wrap">
        <div class="serial-main"><?php echo get_text($issue['issue_no']); ?></div>
        <div class="serial-sub"><?php echo get_text($serial_sub); ?></div>
    </div>

    <div class="line"></div>

    <div class="numbers">
    <?php for($i = 0; $i < $issue_game_count; $i++):
        $row = isset($set[$i]) ? $set[$i] : null;
    ?>
        <div class="row">
            <div class="row-label"><?php echo $labels[$i]; ?> 자동</div>
            <div class="row-numbers"><?php
            if ($row) {
                echo sprintf('%02d %02d %02d %02d %02d %02d', $row['num_a'], $row['num_b'], $row['num_c'], $row['num_d'], $row['num_e'], $row['num_f']);
            } else {
                echo '-- -- -- -- -- --';
            }
            ?></div>
        </div>
    <?php endfor; ?>
    </div>

    <div class="line"></div>

    <div class="notice">
        <div class="bullet"><span class="dot">•</span><span class="txt">본 행운권은 무료제공 상품으로 당첨확인은 로또복권 매주 토요일 TV추첨방송 확인가능</span></div>
        <div class="bullet"><span class="dot">•</span><span class="txt">행운권 용지 분실, 오염, 훼손시 경품 수령 불가</span></div>
        <div class="bullet"><span class="dot">•</span><span class="txt">경품수령은 매장방문 / 제세공과금 본인부담</span></div>
    </div>

    <div class="prize">
    <?php for($rank = 1; $rank <= 5; $rank++):
        $prize_name = isset($prize_map[$rank]['prize_name']) ? trim($prize_map[$rank]['prize_name']) : '';
        if ($prize_name === '') {
            $prize_name = '준비중';
        }
    ?>
        <div class="prize-row"><?php echo $rank; ?>등 : <?php echo get_text($prize_name); ?></div>
    <?php endfor; ?>
    </div>
</div>
<?php endforeach; ?>
<script>
window.print();
</script>
</body>
</html>
