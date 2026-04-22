<?php
/*  chat-GPT ERP sign: sysempire@gmail.com | datetime: 2026-04-09 10:25:00 KST  */

if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

$root_path = '/home/mjtto/www';

if (!isset($_SERVER['SERVER_PORT'])) $_SERVER['SERVER_PORT'] = '443';
if (!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'mjtto.com';
if (!isset($_SERVER['HTTP_HOST'])) $_SERVER['HTTP_HOST'] = 'mjtto.com';
if (!isset($_SERVER['REQUEST_URI'])) $_SERVER['REQUEST_URI'] = '/admin/lotto_sync_cli.php';
if (!isset($_SERVER['REMOTE_ADDR'])) $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
if (!isset($_SERVER['HTTPS'])) $_SERVER['HTTPS'] = 'on';
if (!isset($_SERVER['REQUEST_METHOD'])) $_SERVER['REQUEST_METHOD'] = 'GET';
if (!isset($_SERVER['DOCUMENT_ROOT'])) $_SERVER['DOCUMENT_ROOT'] = $root_path;

include_once $root_path . '/common.php';

@set_time_limit(0);

function mjtto_cli_log($msg)
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
}

function mjtto_http_get($url)
{
    if (!function_exists('curl_init')) {
        return array(
            'ok' => false,
            'error' => 'curl_not_available',
            'url' => $url
        );
    }

    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING => '',
        CURLOPT_NOSIGNAL => true,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Connection: close'
        )
    ));

    $body = curl_exec($ch);
    $info = curl_getinfo($ch);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);

    curl_close($ch);

    if ($body === false || $body === '') {
        return array(
            'ok' => false,
            'error' => 'http_fail',
            'url' => $url,
            'curl_errno' => $curl_errno,
            'curl_error' => $curl_error,
            'info' => $info
        );
    }

    return array(
        'ok' => true,
        'url' => $url,
        'body' => $body,
        'info' => $info
    );
}

function mjtto_text($html)
{
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

function mjtto_get_latest_round_no()
{
    $res = mjtto_http_get('https://pyony.com/lotto/');

    if (!$res['ok']) {
        return $res;
    }

    $text = mjtto_text($res['body']);

    if (preg_match('/로또\s+(\d+)\s*회\s+당첨번호/u', $text, $m)) {
        return array(
            'ok' => true,
            'round_no' => (int)$m[1],
            'source_url' => 'https://pyony.com/lotto/'
        );
    }

    if (preg_match('/(\d+)\s*회\s*\(\d{4}년\s*\d{1,2}월\s*\d{1,2}일\s*추첨\)/u', $text, $m)) {
        return array(
            'ok' => true,
            'round_no' => (int)$m[1],
            'source_url' => 'https://pyony.com/lotto/'
        );
    }

    return array(
        'ok' => false,
        'error' => 'latest_round_parse_fail',
        'source_url' => 'https://pyony.com/lotto/',
        'raw' => mb_substr($text, 0, 1000)
    );
}

function mjtto_fetch_round_detail($round_no)
{
    $url = 'https://pyony.com/lotto/rounds/' . (int)$round_no . '/';
    $res = mjtto_http_get($url);

    if (!$res['ok']) {
        return $res;
    }

    $text = mjtto_text($res['body']);

    if (!preg_match('/' . preg_quote((string)$round_no, '/') . '회\s*\((\d{4})년\s*(\d{1,2})월\s*(\d{1,2})일\s*추첨\)/u', $text, $md)) {
        return array(
            'ok' => false,
            'error' => 'date_parse_fail',
            'round_no' => (int)$round_no,
            'source_url' => $url,
            'raw' => mb_substr($text, 0, 1000)
        );
    }

    $draw_date = sprintf('%04d-%02d-%02d', (int)$md[1], (int)$md[2], (int)$md[3]);

    $parts = preg_split('/' . preg_quote((string)$round_no, '/') . '회\s*\(\d{4}년\s*\d{1,2}월\s*\d{1,2}일\s*추첨\)/u', $text, 2);

    if (count($parts) < 2) {
        return array(
            'ok' => false,
            'error' => 'title_split_fail',
            'round_no' => (int)$round_no,
            'source_url' => $url,
            'raw' => mb_substr($text, 0, 1000)
        );
    }

    preg_match_all('/\b([0-9]{1,2})\b/u', $parts[1], $mn);

    if (count($mn[1]) < 7) {
        return array(
            'ok' => false,
            'error' => 'number_parse_fail',
            'round_no' => (int)$round_no,
            'source_url' => $url,
            'raw' => mb_substr($parts[1], 0, 1000)
        );
    }

    return array(
        'ok' => true,
        'round_no' => (int)$round_no,
        'draw_date' => $draw_date,
        'win_a' => (int)$mn[1][0],
        'win_b' => (int)$mn[1][1],
        'win_c' => (int)$mn[1][2],
        'win_d' => (int)$mn[1][3],
        'win_e' => (int)$mn[1][4],
        'win_f' => (int)$mn[1][5],
        'bonus_no' => (int)$mn[1][6],
        'source_url' => $url
    );
}


function mjtto_fetch_db_round_detail($round_no)
{
    $round_no = (int)$round_no;
    if ($round_no < 1) {
        return array();
    }

    return sql_fetch("
        SELECT
            r.round_id,
            r.round_no,
            r.draw_date,
            r.payout_deadline,
            r.status AS round_status,
            dr.win_a, dr.win_b, dr.win_c, dr.win_d, dr.win_e, dr.win_f, dr.bonus_no
        FROM mz_round r
        LEFT JOIN mz_draw_result dr
          ON r.round_id = dr.round_id
        WHERE r.round_no = '{$round_no}'
        LIMIT 1
    ");
}

function mjtto_calc_rank_by_draw($item_row, $draw_row)
{
    if (!$item_row || !$draw_row || empty($draw_row['win_a'])) {
        return 0;
    }

    $my = array(
        (int)$item_row['num_a'],
        (int)$item_row['num_b'],
        (int)$item_row['num_c'],
        (int)$item_row['num_d'],
        (int)$item_row['num_e'],
        (int)$item_row['num_f']
    );

    $win = array(
        (int)$draw_row['win_a'],
        (int)$draw_row['win_b'],
        (int)$draw_row['win_c'],
        (int)$draw_row['win_d'],
        (int)$draw_row['win_e'],
        (int)$draw_row['win_f']
    );

    $match_count = 0;
    foreach ($my as $n) {
        if (in_array($n, $win, true)) {
            $match_count++;
        }
    }

    $bonus_match = in_array((int)$draw_row['bonus_no'], $my, true);

    if ($match_count === 6) return 1;
    if ($match_count === 5 && $bonus_match) return 2;
    if ($match_count === 5) return 3;
    if ($match_count === 4) return 4;
    if ($match_count === 3) return 5;
    return 0;
}

function mjtto_apply_round_result($round_no)
{
    $round_no = (int)$round_no;
    $draw = mjtto_fetch_db_round_detail($round_no);

    if (!$draw || empty($draw['win_a'])) {
        return array(
            'ok' => false,
            'error' => 'draw_result_missing',
            'round_no' => $round_no
        );
    }

    $claim_table_ready = false;
    $claim_table = sql_fetch("SHOW TABLES LIKE 'mz_prize_claim'");
    if ($claim_table) {
        $claim_table_ready = true;
    }

    $claim_map = array();
    if ($claim_table_ready) {
        $claim_res = sql_query("
            SELECT issue_item_id, claim_status
              FROM mz_prize_claim
             WHERE round_no = '{$round_no}'
        ", false);

        if ($claim_res) {
            while ($claim_row = sql_fetch_array($claim_res)) {
                $claim_map[(int)$claim_row['issue_item_id']] = strtoupper(trim((string)$claim_row['claim_status']));
            }
        }
    }

    $res = sql_query("
        SELECT issue_item_id, num_a, num_b, num_c, num_d, num_e, num_f, item_status
          FROM mz_issue_item
         WHERE round_no = '{$round_no}'
    ", false);

    if ($res === false) {
        return array(
            'ok' => false,
            'error' => 'issue_item_select_fail',
            'round_no' => $round_no
        );
    }

    $processed = 0;
    $updated = 0;
    $winner_count = 0;
    $lose_count = 0;
    $skip_claim_count = 0;

    while ($row = sql_fetch_array($res)) {
        $processed++;
        $issue_item_id = (int)$row['issue_item_id'];
        $result_rank = mjtto_calc_rank_by_draw($row, $draw);
        $current_status = strtoupper(trim((string)$row['item_status']));

        if (isset($claim_map[$issue_item_id]) || strpos($current_status, 'CLAIM_') === 0) {
            $skip_claim_count++;
            continue;
        }

        $target_status = $result_rank > 0 ? 'DRAW_WIN' : 'DRAW_LOSE';

        if ($result_rank > 0) {
            $winner_count++;
        } else {
            $lose_count++;
        }

        if ($current_status === $target_status) {
            continue;
        }

        $ok = sql_query("
            UPDATE mz_issue_item
               SET item_status = '{$target_status}'
             WHERE issue_item_id = '{$issue_item_id}'
        ", false);

        if (!$ok) {
            return array(
                'ok' => false,
                'error' => 'issue_item_update_fail',
                'round_no' => $round_no,
                'issue_item_id' => $issue_item_id
            );
        }

        $updated++;
    }

    return array(
        'ok' => true,
        'round_no' => $round_no,
        'processed' => $processed,
        'updated' => $updated,
        'winner_count' => $winner_count,
        'lose_count' => $lose_count,
        'skip_claim_count' => $skip_claim_count
    );
}

function mjtto_finalize_synced_round($drawn_round_no, $draw_date)
{
    $drawn_round_no = (int)$drawn_round_no;
    $next_round_no = $drawn_round_no + 1;
    $next_draw_date = date('Y-m-d', strtotime($draw_date . ' +7 days'));

    if ($drawn_round_no < 1 || !$draw_date || $next_round_no < 1 || !$next_draw_date) {
        return array(
            'ok' => false,
            'error' => 'invalid_round_finalize',
            'drawn_round_no' => $drawn_round_no,
            'draw_date' => $draw_date
        );
    }

    $ok = sql_query("
        UPDATE mz_round
           SET draw_date = '{$draw_date}',
               payout_deadline = DATE_ADD('{$draw_date}', INTERVAL 6 MONTH),
               status = 'CLOSE'
         WHERE round_no = '{$drawn_round_no}'
    ", false);

    if (!$ok) {
        return array(
            'ok' => false,
            'error' => 'drawn_round_update_fail',
            'drawn_round_no' => $drawn_round_no
        );
    }

    $next = sql_fetch("
        SELECT round_id
          FROM mz_round
         WHERE round_no = '{$next_round_no}'
         LIMIT 1
    " );

    if (!empty($next['round_id'])) {
        $next_round_id = (int)$next['round_id'];

        $ok = sql_query("
            UPDATE mz_round
               SET draw_date = '{$next_draw_date}',
                   payout_deadline = DATE_ADD('{$next_draw_date}', INTERVAL 6 MONTH)
             WHERE round_id = '{$next_round_id}'
        ", false);
    } else {
        $ok = sql_query("
            INSERT INTO mz_round
                SET round_no  = '{$next_round_no}',
                    draw_date = '{$next_draw_date}',
                    payout_deadline = DATE_ADD('{$next_draw_date}', INTERVAL 6 MONTH),
                    status    = 'OPEN'
        ", false);
    }

    if (!$ok) {
        return array(
            'ok' => false,
            'error' => 'next_round_upsert_fail',
            'next_round_no' => $next_round_no
        );
    }

    $ok = sql_query("UPDATE mz_round SET status = 'CLOSE'", false);
    if (!$ok) {
        return array(
            'ok' => false,
            'error' => 'round_status_close_fail'
        );
    }

    $ok = sql_query("
        UPDATE mz_round
           SET status = 'OPEN'
         WHERE round_no = '{$next_round_no}'
    ", false);

    if (!$ok) {
        return array(
            'ok' => false,
            'error' => 'next_round_open_fail',
            'next_round_no' => $next_round_no
        );
    }

    $open_row = sql_fetch("
        SELECT round_id, round_no, draw_date, payout_deadline, status
          FROM mz_round
         WHERE round_no = '{$next_round_no}'
           AND status = 'OPEN'
         LIMIT 1
    ");

    if (empty($open_row['round_id'])) {
        return array(
            'ok' => false,
            'error' => 'next_round_open_missing',
            'next_round_no' => $next_round_no
        );
    }

    return array(
        'ok' => true,
        'next_round_no' => $next_round_no,
        'next_draw_date' => $next_draw_date
    );
}

$latest = mjtto_get_latest_round_no();

if (empty($latest['ok'])) {
    mjtto_cli_log('최신 회차 확인 실패');
    print_r($latest);
    exit(1);
}

$latest_round_no = (int)$latest['round_no'];

$last = sql_fetch("
    SELECT MAX(r.round_no) AS max_round
      FROM mz_round r
      INNER JOIN mz_draw_result d
              ON r.round_id = d.round_id
");

$start_round = (int)($last['max_round'] ? $last['max_round'] : 0);
$start_round = $start_round > 0 ? $start_round + 1 : 1;

mjtto_cli_log('최신 회차: ' . $latest_round_no . '회');
mjtto_cli_log('시작 회차: ' . $start_round . '회');

if ($start_round > $latest_round_no) {
    $last_synced = sql_fetch("
        SELECT r.round_no, r.draw_date
          FROM mz_round r
          INNER JOIN mz_draw_result d
                  ON r.round_id = d.round_id
         ORDER BY r.round_no DESC
         LIMIT 1
    ");

    if (!empty($last_synced['round_no']) && !empty($last_synced['draw_date'])) {
        sql_query('START TRANSACTION', false);

        $settled = mjtto_apply_round_result((int)$last_synced['round_no']);
        if (empty($settled['ok'])) {
            sql_query('ROLLBACK', false);
            mjtto_cli_log('당첨반영 실패');
            print_r($settled);
            exit(1);
        }

        $finalized = mjtto_finalize_synced_round((int)$last_synced['round_no'], $last_synced['draw_date']);
        if (empty($finalized['ok'])) {
            sql_query('ROLLBACK', false);
            mjtto_cli_log('다음 회차 준비 실패');
            print_r($finalized);
            exit(1);
        }

        sql_query('COMMIT', false);
        mjtto_cli_log('신규 동기화 대상은 없지만 마지막 회차 후처리/다음 회차 보정을 완료했습니다.');
        mjtto_cli_log('기준 회차: ' . (int)$last_synced['round_no'] . '회 / next=' . $finalized['next_round_no'] . '회');
    } else {
        mjtto_cli_log('신규 동기화 대상이 없습니다.');
    }
    exit(0);
}

$inserted = 0;
$updated = 0;
$failed = 0;
$last_success_round = 0;

for ($round_no = $start_round; $round_no <= $latest_round_no; $round_no++) {
    $data = mjtto_fetch_round_detail($round_no);

    if (empty($data['ok'])) {
        $failed++;
        mjtto_cli_log('실패: ' . $round_no . '회');
        print_r($data);
        continue;
    }

    $drw_no = (int)$data['round_no'];
    $draw_date = sql_real_escape_string($data['draw_date']);
    $win_a = (int)$data['win_a'];
    $win_b = (int)$data['win_b'];
    $win_c = (int)$data['win_c'];
    $win_d = (int)$data['win_d'];
    $win_e = (int)$data['win_e'];
    $win_f = (int)$data['win_f'];
    $bonus_no = (int)$data['bonus_no'];

    sql_query('START TRANSACTION', false);

    $round = sql_fetch("
        SELECT round_id
          FROM mz_round
         WHERE round_no = '{$drw_no}'
         LIMIT 1
    ");

    if (!empty($round['round_id'])) {
        $round_id = (int)$round['round_id'];

        sql_query("
            UPDATE mz_round
               SET draw_date = '{$draw_date}'
             WHERE round_id = '{$round_id}'
        ", false);

        $updated++;
        $save_type = 'UPDATE';
    } else {
        sql_query("
            INSERT INTO mz_round
                SET round_no  = '{$drw_no}',
                    draw_date = '{$draw_date}',
                    payout_deadline = DATE_ADD('{$draw_date}', INTERVAL 6 MONTH),
                    status    = 'CLOSE'
        ", false);

        $round_id = sql_insert_id();
        $inserted++;
        $save_type = 'INSERT';
    }

    $draw = sql_fetch("
        SELECT draw_id
          FROM mz_draw_result
         WHERE round_id = '{$round_id}'
         LIMIT 1
    ");

    if (!empty($draw['draw_id'])) {
        sql_query("
            UPDATE mz_draw_result
               SET win_a    = '{$win_a}',
                   win_b    = '{$win_b}',
                   win_c    = '{$win_c}',
                   win_d    = '{$win_d}',
                   win_e    = '{$win_e}',
                   win_f    = '{$win_f}',
                   bonus_no = '{$bonus_no}'
             WHERE round_id = '{$round_id}'
        ", false);
    } else {
        sql_query("
            INSERT INTO mz_draw_result
                SET round_id = '{$round_id}',
                    win_a    = '{$win_a}',
                    win_b    = '{$win_b}',
                    win_c    = '{$win_c}',
                    win_d    = '{$win_d}',
                    win_e    = '{$win_e}',
                    win_f    = '{$win_f}',
                    bonus_no = '{$bonus_no}'
        ", false);
    }

    $settled = mjtto_apply_round_result($drw_no);
    if (empty($settled['ok'])) {
        sql_query('ROLLBACK', false);
        $failed++;
        mjtto_cli_log('당첨반영 실패: ' . $drw_no . '회');
        print_r($settled);
        continue;
    }

    $finalized = mjtto_finalize_synced_round($drw_no, $data['draw_date']);
    if (empty($finalized['ok'])) {
        sql_query('ROLLBACK', false);
        $failed++;
        mjtto_cli_log('다음 회차 준비 실패: ' . $drw_no . '회');
        print_r($finalized);
        continue;
    }

    sql_query('COMMIT', false);

    $last_success_round = $drw_no;
    mjtto_cli_log($save_type . ': ' . $drw_no . '회 / ' . $draw_date . ' / ' . $win_a . ',' . $win_b . ',' . $win_c . ',' . $win_d . ',' . $win_e . ',' . $win_f . ' + ' . $bonus_no . ' / next=' . $finalized['next_round_no'] . '회 / winners=' . $settled['winner_count'] . ' / losers=' . $settled['lose_count']);
}

mjtto_cli_log('완료');
mjtto_cli_log('신규 저장: ' . $inserted . '건');
mjtto_cli_log('기존 갱신: ' . $updated . '건');
mjtto_cli_log('실패: ' . $failed . '건');
mjtto_cli_log('최종 저장 회차: ' . ($last_success_round ? $last_success_round . '회' : '없음'));

exit(0);