<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-04-15 05:02:00 KST  */

include_once __DIR__ . '/admin/_admin_common.php';

function mjtto_h($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mjtto_normalize_claim_hp($value){
    return preg_replace('/[^0-9]/', '', (string)$value);
}

function mjtto_is_valid_claim_hp($value){
    return preg_match('/^01[0-9]{8,9}$/', (string)$value) === 1;
}

$ticket_no = isset($_GET['no']) ? trim((string)$_GET['no']) : '';
if ($ticket_no === '') {
    die('잘못된 접근입니다.');
}

$base_item = sql_fetch("
    SELECT
        ii.issue_item_id,
        ii.issue_id,
        ii.ticket_no,
        ii.round_no,
        ii.item_status,
        i.issue_no,
        i.issue_qty,
        i.issue_game_count,
        i.company_id AS contract_company_id,
        i.branch_id,
        i.created_at AS issue_created_at,
        bc.company_name AS branch_name,
        bc.print_name_1 AS branch_print_name_1,
        bc.print_name_2 AS branch_print_name_2,
        bc.tel_no AS branch_tel_no,
        cc.company_name AS contract_name,
        cc.print_name_1 AS contract_print_name_1,
        cc.print_name_2 AS contract_print_name_2,
        cc.tel_no AS contract_tel_no
    FROM mz_issue_item ii
    INNER JOIN mz_issue i
            ON ii.issue_id = i.issue_id
    LEFT JOIN mz_company bc
           ON i.branch_id = bc.company_id
    LEFT JOIN mz_company cc
           ON i.company_id = cc.company_id
    WHERE ii.ticket_no = '".sql_real_escape_string($ticket_no)."'
    LIMIT 1
");

if (!$base_item || empty($base_item['issue_item_id'])) {
    die('쿠폰을 찾을 수 없습니다.');
}

$issue_id = (int)$base_item['issue_id'];
$issue_game_count = (int)$base_item['issue_game_count'];
if ($issue_game_count < 1 || $issue_game_count > 5) {
    $issue_game_count = 5;
}

$win_auth = array(
    'role' => '',
    'company_id' => 0,
    'company_name' => '',
    'company_code' => ''
);

if (!empty($member['mb_id'])) {
    $tmp_auth = mjtto_get_admin_role($member['mb_id']);
    if (!empty($tmp_auth['role'])) {
        $win_auth = $tmp_auth;
    }
}

$claim_map = array();
if (mjtto_claim_table_exists()) {
    $claim_res = sql_query("
        SELECT *
          FROM mz_prize_claim
         WHERE issue_id = '{$issue_id}'
    ", false);

    if ($claim_res) {
        while ($claim_row = sql_fetch_array($claim_res)) {
            $claim_map[(int)$claim_row['issue_item_id']] = $claim_row;
        }
    }
}

$position_row = sql_fetch("
    SELECT COUNT(*) AS seq_no
      FROM mz_issue_item
     WHERE issue_id = '{$issue_id}'
       AND issue_item_id <= '".(int)$base_item['issue_item_id']."'
");
$seq_no = (int)($position_row['seq_no'] ?? 0);
if ($seq_no < 1) {
    $seq_no = 1;
}

$page_index = (int)floor(($seq_no - 1) / $issue_game_count);
$page_start_offset = $page_index * $issue_game_count;
$page_no = $page_index + 1;
$total_page = (int)ceil(((int)$base_item['issue_qty']) / max(1, $issue_game_count));
if ($total_page < 1) {
    $total_page = 1;
}

$page_items_res = sql_query("
    SELECT
        issue_item_id,
        ticket_no,
        round_no,
        num_a,
        num_b,
        num_c,
        num_d,
        num_e,
        num_f,
        item_status,
        customer_name,
        customer_hp,
        sent_at,
        created_at
    FROM mz_issue_item
    WHERE issue_id = '{$issue_id}'
    ORDER BY issue_item_id ASC
    LIMIT {$page_start_offset}, {$issue_game_count}
");

$page_items = array();
while ($row = sql_fetch_array($page_items_res)) {
    $page_items[] = $row;
}

if (!count($page_items)) {
    die('해당 쿠폰 페이지 정보를 찾을 수 없습니다.');
}

$round = sql_fetch("
    SELECT round_id, round_no, draw_date, payout_deadline, status
      FROM mz_round
     WHERE round_no = '".(int)$base_item['round_no']."'
     LIMIT 1
");

$draw = array();
if (!empty($round['round_id'])) {
    $draw = sql_fetch("
        SELECT round_id, win_a, win_b, win_c, win_d, win_e, win_f, bonus_no
          FROM mz_draw_result
         WHERE round_id = '".(int)$round['round_id']."'
         LIMIT 1
    ");
}

$prize_map = mjtto_get_prize_map(
    (int)$base_item['round_no'],
    (int)$base_item['contract_company_id'],
    (int)$base_item['branch_id']
);

$draw_ready = false;
$win_numbers = array();
$bonus_no = 0;
$deadline_expired = mjtto_is_payout_deadline_expired($round['payout_deadline'] ?? '');
$deadline_message = mjtto_claim_deadline_message();
if ($draw && !empty($draw['round_id'])) {
    $draw_ready = true;
    $win_numbers = array(
        (int)$draw['win_a'],
        (int)$draw['win_b'],
        (int)$draw['win_c'],
        (int)$draw['win_d'],
        (int)$draw['win_e'],
        (int)$draw['win_f']
    );
    $bonus_no = (int)$draw['bonus_no'];
}

$labels = array('A', 'B', 'C', 'D', 'E');
$rows_view = array();
$request_item_ids = array();
$request_rank_map = array();
$request_item_meta_map = array();
$request_claim_name = '';
$request_claim_hp = '';
$issue_scope_row = array(
    'issue_id' => $issue_id,
    'company_id' => (int)$base_item['contract_company_id'],
    'branch_id' => (int)$base_item['branch_id']
);

foreach ($page_items as $idx => $row) {
    $numbers = array(
        (int)$row['num_a'],
        (int)$row['num_b'],
        (int)$row['num_c'],
        (int)$row['num_d'],
        (int)$row['num_e'],
        (int)$row['num_f']
    );

    $match_count = 0;
    $bonus_match = false;
    $rank = 0;
    $result_text = '추첨 대기';

    if ($draw_ready) {
        foreach ($numbers as $n) {
            if (in_array($n, $win_numbers, true)) {
                $match_count++;
            }
        }
        $bonus_match = in_array($bonus_no, $numbers, true);

        if ($match_count === 6) {
            $rank = 1;
        } elseif ($match_count === 5 && $bonus_match) {
            $rank = 2;
        } elseif ($match_count === 5) {
            $rank = 3;
        } elseif ($match_count === 4) {
            $rank = 4;
        } elseif ($match_count === 3) {
            $rank = 5;
        }

        $result_text = $rank > 0 ? $rank.'등 당첨' : '낙첨';
    }

    $matched_numbers = array();
    $matched_number_map = array();
    if ($draw_ready) {
        foreach ($numbers as $n) {
            if (in_array($n, $win_numbers, true)) {
                $matched_numbers[] = $n;
                $matched_number_map[(int)$n] = true;
            }
        }
    }

    $prize_text = '';
    if ($rank > 0 && isset($prize_map[$rank])) {
        $prize_text = trim((string)$prize_map[$rank]['prize_name']);
        $prize_desc = trim((string)$prize_map[$rank]['prize_desc']);
        if ($prize_desc !== '') {
            $prize_text .= ' ('.$prize_desc.')';
        }
    }

    $claim = isset($claim_map[(int)$row['issue_item_id']]) ? $claim_map[(int)$row['issue_item_id']] : null;
    $is_rank5 = ($rank === 5);
    $is_claim_done = strtoupper(trim((string)$row['item_status'])) === 'CLAIM_DONE';
    $can_request_info = false;
    $request_block_reason = '';
    $item_status_upper = strtoupper(trim((string)$row['item_status']));
    $claim_status_upper = $claim ? strtoupper(trim((string)$claim['claim_status'])) : '';

    if ($rank > 0) {
        if ($claim_status_upper === 'CLAIM_DONE') {
            $is_claim_done = true;
        }

        $can_request_info = !$claim && !$deadline_expired && !in_array($item_status_upper, array('CLAIM_WAIT', 'CLAIM_REQUEST', 'CLAIM_APPROVED', 'CLAIM_DONE', 'CLAIM_HOLD', 'CLAIM_REJECT'), true);
        if ($can_request_info) {
            $request_item_id = (int)$row['issue_item_id'];
            $request_item_ids[] = $request_item_id;
            $request_rank_map[$request_item_id] = $rank;

            $request_prize = isset($prize_map[$rank]) ? $prize_map[$rank] : array(
                'owner_type' => 'SYSTEM',
                'owner_company_id' => 0,
                'prize_name' => $rank . '등 경품',
                'prize_desc' => ''
            );

            $request_item_meta_map[$request_item_id] = array(
                'ticket_no' => (string)$row['ticket_no'],
                'result_rank' => $rank,
                'owner_type' => (string)($request_prize['owner_type'] ?? 'SYSTEM'),
                'owner_company_id' => (int)($request_prize['owner_company_id'] ?? 0),
                'prize_name' => (string)($request_prize['prize_name'] ?? ''),
                'prize_desc' => (string)($request_prize['prize_desc'] ?? '')
            );

            if ($request_claim_name === '' && trim((string)$row['customer_name']) !== '') {
                $request_claim_name = trim((string)$row['customer_name']);
            }
            if ($request_claim_hp === '' && trim((string)$row['customer_hp']) !== '') {
                $request_claim_hp = trim((string)$row['customer_hp']);
            }
        } elseif (!$claim && $deadline_expired) {
            $request_block_reason = $deadline_message;
        }
    }

    $claim_status_text = '';
    if ($claim && !empty($claim['claim_status'])) {
        $claim_status_text = mjtto_claim_status_name($claim['claim_status']);
    } elseif ($is_claim_done) {
        $claim_status_text = '지급완료';
    }

    $rows_view[] = array(
        'label'              => $labels[$idx] ?? (string)($idx + 1),
        'issue_item_id'      => (int)$row['issue_item_id'],
        'ticket_no'          => $row['ticket_no'],
        'numbers'            => $numbers,
        'matched_numbers'    => $matched_numbers,
        'matched_number_map' => $matched_number_map,
        'match_count'        => $match_count,
        'bonus_match'        => $bonus_match,
        'rank'               => $rank,
        'result_text'        => $result_text,
        'prize_text'         => $prize_text,
        'item_status'        => $row['item_status'],
        'item_status_text'   => mjtto_item_status_name($row['item_status']),
        'customer_name'      => $row['customer_name'],
        'customer_hp'        => $row['customer_hp'],
        'claim'              => $claim,
        'claim_status_text'  => $claim_status_text,
        'can_request_info'   => $can_request_info,
        'request_block_reason'=> $request_block_reason,
        'is_rank5'           => $is_rank5
    );
}

$has_winner = false;
$has_request_winner = count($request_item_ids) > 0;
$has_claimed_winner = false;
foreach ($rows_view as $tmp_row_view) {
    if ((int)$tmp_row_view['rank'] > 0) {
        $has_winner = true;
    }
    if (trim((string)$tmp_row_view['claim_status_text']) !== '') {
        $has_claimed_winner = true;
    }
}
$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = trim((string)($_POST['action'] ?? 'save_claimant'));

    if ($post_action === 'save_claimant') {
        if (!$draw_ready) {
            $error_msg = '  추첨 전 입니다. 
이번주 토요일 TV로또방송 이후 확인가능합니다.';
        } elseif (!$has_request_winner) {
            $error_msg = ($has_winner && !$has_claimed_winner && $deadline_expired) ? $deadline_message : '이 페이지에는 접수할 당첨 게임이 없습니다.';
        } else {
            $customer_name = trim((string)($_POST['customer_name'] ?? ''));
            $customer_hp = mjtto_normalize_claim_hp($_POST['customer_hp'] ?? '');

            if ($customer_name === '') {
                $error_msg = '당첨자 이름을 입력하세요.';
            } elseif ($customer_hp === '') {
                $error_msg = '휴대폰번호를 입력하세요.';
            } elseif (!mjtto_is_valid_claim_hp($customer_hp)) {
                $error_msg = '휴대폰번호 형식이 올바르지 않습니다.';
            } else {
                $customer_name_sql = sql_real_escape_string($customer_name);
                $customer_hp_sql = sql_real_escape_string($customer_hp);
                $request_ids_sql = implode(',', array_map('intval', $request_item_ids));
                $request_by = !empty($member['mb_id']) ? trim((string)$member['mb_id']) : '';
                $request_by_sql = sql_real_escape_string($request_by);
                $request_error_msg = '';

                sql_query('START TRANSACTION', false);

                $request_ok = !mjtto_claim_table_exists();

                $update_ok = sql_query("\n                    UPDATE mz_issue_item\n                       SET customer_name = '{$customer_name_sql}',\n                           customer_hp   = '{$customer_hp_sql}',\n                           item_status   = 'CLAIM_REQUEST'\n                     WHERE issue_item_id IN ({$request_ids_sql})\n                ", false);

                if ($update_ok && mjtto_claim_table_exists()) {
                    $request_ok = true;
                    foreach ($request_item_ids as $request_item_id) {
                        $request_item_id = (int)$request_item_id;
                        $request_meta = isset($request_item_meta_map[$request_item_id]) ? $request_item_meta_map[$request_item_id] : array();
                        $ticket_no_value = trim((string)($request_meta['ticket_no'] ?? ''));
                        $result_rank_value = (int)($request_meta['result_rank'] ?? ($request_rank_map[$request_item_id] ?? 0));
                        $owner_type_value = trim((string)($request_meta['owner_type'] ?? 'SYSTEM'));
                        $owner_company_id_value = (int)($request_meta['owner_company_id'] ?? 0);
                        $prize_name_value = trim((string)($request_meta['prize_name'] ?? ($result_rank_value > 0 ? $result_rank_value . '등 경품' : '')));
                        $prize_desc_value = trim((string)($request_meta['prize_desc'] ?? ''));

                        $ticket_no_sql = sql_real_escape_string($ticket_no_value);
                        $owner_type_sql = sql_real_escape_string($owner_type_value);
                        $prize_name_sql = sql_real_escape_string($prize_name_value);
                        $prize_desc_sql = sql_real_escape_string($prize_desc_value);

                        $exists_claim = sql_fetch("\n                            SELECT *\n                              FROM mz_prize_claim\n                             WHERE issue_item_id = '{$request_item_id}'\n                             LIMIT 1\n                        ");

                        if (!empty($exists_claim['claim_id']) && strtoupper(trim((string)$exists_claim['claim_status'])) === 'CLAIM_DONE') {
                            $request_ok = false;
                            $request_error_msg = '이미 지급완료 처리된 게임이 포함되어 있습니다.';
                            break;
                        }

                        if (!empty($exists_claim['claim_id'])) {
                            $claim_sql = "\n                                UPDATE mz_prize_claim\n                                   SET claim_status = 'CLAIM_REQUEST',\n                                       request_name = '{$customer_name_sql}',\n                                       request_hp = '{$customer_hp_sql}',\n                                       request_memo = '',\n                                       request_by = '{$request_by_sql}',\n                                       requested_at = NOW(),\n                                       approve_by = '',\n                                       approved_at = NULL,\n                                       paid_by = '',\n                                       paid_at = NULL,\n                                       reject_by = '',\n                                       rejected_at = NULL,\n                                       reject_reason = '',\n                                       admin_memo = '',\n                                       prize_owner_type = '{$owner_type_sql}',\n                                       prize_owner_company_id = " . ($owner_company_id_value > 0 ? "'{$owner_company_id_value}'" : 'NULL') . ",\n                                       prize_name = '{$prize_name_sql}',\n                                       prize_desc = '{$prize_desc_sql}'\n                                 WHERE claim_id = '".(int)$exists_claim['claim_id']."'\n                            ";
                        } else {
                            $claim_sql = "\n                                INSERT INTO mz_prize_claim\n                                    SET issue_item_id = '{$request_item_id}',\n                                        issue_id = '{$issue_id}',\n                                        company_id = '".(int)$base_item['contract_company_id']."',\n                                        branch_id = '".(int)$base_item['branch_id']."',\n                                        round_no = '".(int)$base_item['round_no']."',\n                                        ticket_no = '{$ticket_no_sql}',\n                                        result_rank = '{$result_rank_value}',\n                                        prize_owner_type = '{$owner_type_sql}',\n                                        prize_owner_company_id = " . ($owner_company_id_value > 0 ? "'{$owner_company_id_value}'" : 'NULL') . ",\n                                        prize_name = '{$prize_name_sql}',\n                                        prize_desc = '{$prize_desc_sql}',\n                                        claim_status = 'CLAIM_REQUEST',\n                                        request_name = '{$customer_name_sql}',\n                                        request_hp = '{$customer_hp_sql}',\n                                        request_memo = '',\n                                        request_by = '{$request_by_sql}',\n                                        requested_at = NOW()\n                            ";
                        }

                        if (!sql_query($claim_sql, false)) {
                            $request_ok = false;
                            $request_error_msg = '지급요청 저장 중 오류가 발생했습니다.';
                            break;
                        }
                    }
                }

                if ($update_ok && $request_ok) {
                    sql_query('COMMIT', false);
                    goto_url('./win.php?no='.urlencode($ticket_no).'&saved=1');
                } else {
                    sql_query('ROLLBACK', false);
                    $error_msg = $request_error_msg !== '' ? $request_error_msg : '지급요청을 저장하지 못했습니다.';
                }
            }
        }
    }
}

if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $success_msg = '지급요청이 접수되었습니다.';
    if ($request_claim_name === '' && $has_request_winner) {
        $refreshed = sql_query("
            SELECT issue_item_id, customer_name, customer_hp
              FROM mz_issue_item
             WHERE issue_item_id IN (".implode(',', array_map('intval', $request_item_ids)).")
             ORDER BY issue_item_id ASC
        ");
        while ($rr = sql_fetch_array($refreshed)) {
            if ($request_claim_name === '' && trim((string)$rr['customer_name']) !== '') {
                $request_claim_name = trim((string)$rr['customer_name']);
            }
            if ($request_claim_hp === '' && trim((string)$rr['customer_hp']) !== '') {
                $request_claim_hp = trim((string)$rr['customer_hp']);
            }
        }
    }
}

$company_line_1 = trim((string)$base_item['branch_print_name_1']) !== '' ? trim((string)$base_item['branch_print_name_1']) : trim((string)$base_item['contract_print_name_1']);
if ($company_line_1 === '') {
    $company_line_1 = trim((string)$base_item['contract_name']);
}
$company_line_2 = trim((string)$base_item['branch_print_name_2']) !== '' ? trim((string)$base_item['branch_print_name_2']) : trim((string)$base_item['branch_name']);
$company_tel = trim((string)$base_item['branch_tel_no']) !== '' ? trim((string)$base_item['branch_tel_no']) : trim((string)$base_item['contract_tel_no']);
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>당첨조회</title>
<style>
body{font-family:Arial,Apple SD Gothic Neo,Malgun Gothic,sans-serif;background:#f5f7fb;margin:0;padding:0;color:#111827;}
.wrap{max-width:760px;margin:24px auto;background:#fff;padding:24px;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,0.08);}
.title{text-align:center;font-size:26px;font-weight:700;margin-bottom:8px;}
.sub{text-align:center;color:#6b7280;font-size:14px;margin-bottom:20px;}
.box{border:1px solid #e5e7eb;border-radius:14px;padding:18px;margin-bottom:16px;background:#fff;}
.company{text-align:center;line-height:1.6;font-size:15px;margin-bottom:8px;}
.notice-ok,.notice-error{padding:12px 14px;border-radius:10px;font-size:14px;margin-bottom:14px;}
.notice-ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;}
.notice-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;}
.table{width:100%;border-collapse:collapse;}
.table th,.table td{padding:12px 10px;border-top:1px solid #f0f0f0;text-align:left;font-size:14px;vertical-align:top;}
.table th{width:150px;background:#fafafa;}
.grid{display:grid;grid-template-columns:repeat(6,minmax(35px,1fr));gap:8px;margin:10px 0;}
.ball{height:35px;line-height:35px;text-align:center;border-radius:999px;border:2px solid #111827;font-size:17px;font-weight:700;background:#fff;}
.ball.win{background:#111827;color:#fff;}
.game-card{border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px;margin-top:10px;}
.game-head{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:8px;}
.game-label{font-weight:700;font-size:15px;}
.game-result{font-weight:700;}
.game-result.win{color:#b91c1c;}
.game-result.lose{color:#374151;}
.nums-balls{display:grid;grid-template-columns:repeat(6,minmax(32px,1fr));gap:6px;margin:8px 0 10px;}
.num-ball{height:32px;line-height:32px;text-align:center;border-radius:999px;border:1px solid #d1d5db;font-size:15px;font-weight:700;background:#fff;color:#111827;}
.num-ball.match{background:#111827;border-color:#111827;color:#fff;}
.num-ball.bonus{background:#fff7ed;border-color:#fb923c;color:#c2410c;}
.match-text{font-size:13px;color:#4b5563;line-height:1.5;margin-top:2px;}
.meta{font-size:12px;color:#6b7280;line-height:1.6;margin-top:6px;}
.meta-line{display:block;margin-top:2px;}
.prize-line{word-break:keep-all;}
.jump-link{display:inline-block;height:34px;line-height:34px;padding:0 12px;border-radius:8px;background:#fff7ed;border:1px solid #fdba74;color:#c2410c;font-size:13px;font-weight:700;text-decoration:none;}
.jump-link:hover{text-decoration:none;background:#ffedd5;}
.input{width:100%;height:44px;padding:0 12px;border:1px solid #d1d5db;border-radius:10px;box-sizing:border-box;font-size:15px;}
.form-row{margin-bottom:12px;}
.btn{display:inline-block;height:46px;line-height:46px;padding:0 18px;border:0;border-radius:10px;background:#111827;color:#fff;font-size:15px;cursor:pointer;text-decoration:none;}
.btn.small{height:38px;line-height:38px;padding:0 14px;font-size:14px;}
.action-row{margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
.prize-list{line-height:1.8;font-size:14px;}
.center{text-align:center;}
.muted{color:#3300cc;}
</style>
</head>
<body>
<div class="wrap">
    <div class="title">당첨조회</div>
    <div class="sub">QR 또는 쿠폰번호로 연결된 발권 1장 기준 조회입니다.</div>

    <?php if ($success_msg !== '') { ?>
        <div class="notice-ok"><?php echo mjtto_h($success_msg); ?></div>
    <?php } ?>
    <?php if ($error_msg !== '') { ?>
        <div class="notice-error"><?php echo mjtto_h($error_msg); ?></div>
    <?php } ?>

    <div class="box">
        <div class="company">
            <div><?php echo mjtto_h($company_line_1); ?></div>
            <div><?php echo $company_line_2 !== '' ? mjtto_h($company_line_2) : '&nbsp;'; ?></div>
            <div><?php echo $company_tel !== '' ? 'TEL. '.mjtto_h($company_tel) : '&nbsp;'; ?></div>
        </div>
        <table class="table">
            <tr><th>발권번호</th><td><?php echo mjtto_h($base_item['issue_no']); ?></td></tr>
            <tr><th>조회 쿠폰번호</th><td><?php echo mjtto_h($ticket_no); ?></td></tr>
            <tr><th>회차</th><td><?php echo (int)$base_item['round_no']; ?>회</td></tr>
            <tr><th>페이지</th><td><?php echo (int)$page_no; ?> / <?php echo (int)$total_page; ?> 장</td></tr>
            <tr><th>추첨일</th><td><?php echo !empty($round['draw_date']) ? mjtto_h($round['draw_date']) : '-'; ?></td></tr>
            <tr><th>지급기한</th><td><?php echo !empty($round['payout_deadline']) ? mjtto_h($round['payout_deadline']) : '-'; ?></td></tr>
        </table>
    </div>

    <div class="box">
        <div style="font-weight:700;margin-bottom:8px;">당첨번호</div>
        <?php if ($draw_ready) { ?>
            <div class="grid">
                <?php foreach ($win_numbers as $n) { ?>
                    <div class="ball win"><?php echo sprintf('%02d', $n); ?></div>
                <?php } ?>
            </div>
            <div class="center muted">보너스 번호 : <?php echo sprintf('%02d', $bonus_no); ?></div>
        <?php } else { ?>
            <div class="center muted">
			<strong>추첨 전 입니다. <BR><BR>
			이번주 토요일 TV로또<BR>
			방송 이후 확인가능합니다.</strong></div>
        <?php } ?>
    </div>

    <div class="box">
        <div style="font-weight:700;margin-bottom:8px;">이 장의 게임 결과</div>
        <?php foreach ($rows_view as $rowv) { ?>
            <div class="game-card">
                <div class="game-head">
                    <div class="game-label"><?php echo mjtto_h($rowv['label']); ?> 게임</div>
                    <div class="game-result <?php echo $rowv['rank'] > 0 ? 'win' : 'lose'; ?>"><?php echo mjtto_h($rowv['result_text']); ?></div>
                </div>
                <div class="nums-balls">
                    <?php foreach ($rowv['numbers'] as $game_no) {
                        $num_class = 'num-ball';
                        if ($draw_ready && !empty($rowv['matched_number_map'][(int)$game_no])) {
                            $num_class .= ' match';
                        } elseif ($draw_ready && $rowv['bonus_match'] && (int)$game_no === (int)$bonus_no) {
                            $num_class .= ' bonus';
                        }
                    ?>
                        <div class="<?php echo $num_class; ?>"><?php echo sprintf('%02d', $game_no); ?></div>
                    <?php } ?>
                </div>
                <?php if ($draw_ready) { ?>
                <div class="match-text">
                    당첨번호 : <?php echo count($rowv['matched_numbers']) ? mjtto_h(implode(', ', array_map(function($n){ return sprintf('%02d', $n); }, $rowv['matched_numbers']))) : '없음'; ?>
                    <?php if ($rowv['bonus_match']) { ?> / 보너스 : <?php echo sprintf('%02d', $bonus_no); ?><?php } ?>
                </div>
                <?php } ?>
                <div class="meta">
                    <div class="meta-line">티켓번호 : <?php echo mjtto_h($rowv['ticket_no']); ?></div>
                    <div class="meta-line">상태 : <?php echo mjtto_h($rowv['item_status_text']); ?></div>
                    <?php if ($rowv['claim_status_text'] !== '') { ?>
                        <div class="meta-line">지급상태 : <?php echo mjtto_h($rowv['claim_status_text']); ?></div>
                    <?php } ?>
                    <?php if ($draw_ready) { ?>
                        <div class="meta-line">일치수 : <?php echo (int)$rowv['match_count']; ?>개<?php if ($rowv['bonus_match']) { ?> / 보너스 일치<?php } ?></div>
                    <?php } ?>
                    <?php if ($rowv['prize_text'] !== '') { ?>
                        <div class="meta-line prize-line">경품 : <?php echo mjtto_h($rowv['prize_text']); ?></div>
                    <?php } ?>
                </div>
                <?php if ($rowv['can_request_info']) { ?>
                <div class="action-row">
                    <a href="#claim-request-form" class="jump-link">눌러서 정보입력 하세요</a>
                </div>
                <?php } elseif ($rowv['request_block_reason'] !== '') { ?>
                <div class="action-row">
                    <span class="muted"><?php echo mjtto_h($rowv['request_block_reason']); ?></span>
                </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <div class="box">
        <div style="font-weight:700;margin-bottom:8px;">등수별 경품</div>
        <div class="prize-list">
            <?php for ($rank = 1; $rank <= 5; $rank++) {
                $line = '준비중';
                if (isset($prize_map[$rank])) {
                    $line = trim((string)$prize_map[$rank]['prize_name']);
                    $desc = trim((string)$prize_map[$rank]['prize_desc']);
                    if ($desc !== '') {
                        $line .= ' ('.$desc.')';
                    }
                }
            ?>
                <?php echo $rank; ?>등 : <?php echo mjtto_h($line); ?><br>
            <?php } ?>
        </div>
    </div>

    <?php if ($draw_ready && $has_request_winner) { ?>
    <div class="box" id="claim-request-form">
        <div style="font-weight:700;margin-bottom:8px;">당첨자 정보 접수</div>
        <?php if ($request_claim_name !== '' || $request_claim_hp !== '') { ?>
            <table class="table" style="margin-bottom:16px;">
                <tr><th>당첨자 이름</th><td><?php echo mjtto_h($request_claim_name); ?></td></tr>
                <tr><th>휴대폰번호</th><td><?php echo mjtto_h($request_claim_hp); ?></td></tr>
            </table>
        <?php } ?>
        <form method="post" action="./win.php?no=<?php echo urlencode($ticket_no); ?>">
            <input type="hidden" name="action" value="save_claimant">
            <div class="form-row">
                <input type="text" name="customer_name" class="input" placeholder="당첨자 이름" value="<?php echo mjtto_h($request_claim_name); ?>" maxlength="100" required>
            </div>
            <div class="form-row">
                <input type="text" name="customer_hp" class="input" placeholder="휴대폰번호" value="<?php echo mjtto_h($request_claim_hp); ?>" maxlength="20" required>
            </div>
            <button type="submit" class="btn">지급요청 접수</button>
        </form>
        <div class="meta" style="margin-top:10px;">
            이 장의 1~5등 당첨 게임은 모두 이름과 휴대폰번호를 입력해 지급요청할 수 있습니다.
        </div>
    </div>
    <?php } elseif ($draw_ready && $has_winner) { ?>
    <div class="box">
        <div class="meta"><?php echo ($deadline_expired && !$has_claimed_winner) ? mjtto_h($deadline_message) : '이 장의 당첨 게임은 이미 지급요청이 접수되었거나 처리 상태입니다.'; ?></div>
    </div>
    <?php } ?>
</div>
</body>
</html>
