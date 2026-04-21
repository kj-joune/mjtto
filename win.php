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
}

$company_line_1 = trim((string)$base_item['branch_print_name_1']) !== '' ? trim((string)$base_item['branch_print_name_1']) : trim((string)$base_item['contract_print_name_1']);
if ($company_line_1 === '') {
    $company_line_1 = trim((string)$base_item['contract_name']);
}
$company_line_2 = trim((string)$base_item['branch_print_name_2']) !== '' ? trim((string)$base_item['branch_print_name_2']) : trim((string)$base_item['branch_name']);
$company_tel = trim((string)$base_item['branch_tel_no']) !== '' ? trim((string)$base_item['branch_tel_no']) : trim((string)$base_item['contract_tel_no']);
?>
