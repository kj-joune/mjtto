<?php
/*  chat-GPT ERP sign: sysempire@gmail.com | datetime: 2026-04-09 10:25:00 KST  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();

if ($auth['role'] !== 'SUPER_ADMIN') {
    alert('최고관리자만 접근할 수 있습니다.');
}

@set_time_limit(0);

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
    $g5['title'] = '로또 회차 동기화';
    include_once __DIR__ . '/_admin_head.php';
    ?>
    <style>
    .box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.03);}
    pre{white-space:pre-wrap;word-break:break-all;}
    </style>
    <div class="box">
        <h1>최신 회차 확인 실패</h1>
        <pre><?php print_r($latest); ?></pre>
    </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$latest_round_no = (int)$latest['round_no'];

$last = sql_fetch("
    SELECT MAX(r.round_no) AS max_round
      FROM mz_round r
      INNER JOIN mz_draw_result d
              ON r.round_id = d.round_id
");

$db_next_round = (int)($last['max_round'] ?? 0);
$db_next_round = $db_next_round > 0 ? $db_next_round + 1 : 1;

$round_no = isset($_GET['round_no']) ? (int)$_GET['round_no'] : $db_next_round;
if ($round_no < 1) $round_no = 1;

$is_done = false;
$result = array();

if ($round_no > $latest_round_no) {
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
        if (!empty($settled['ok'])) {
            $finalized = mjtto_finalize_synced_round((int)$last_synced['round_no'], $last_synced['draw_date']);
        } else {
            $finalized = array('ok' => false, 'error' => 'settle_fail', 'detail' => $settled);
        }

        if (!empty($settled['ok']) && !empty($finalized['ok'])) {
            sql_query('COMMIT', false);
            $result = array(
                'ok' => true,
                'mode' => 'repair',
                'round_no' => (int)$last_synced['round_no'],
                'next_round_no' => $finalized['next_round_no'],
                'next_draw_date' => $finalized['next_draw_date'],
                'winner_count' => $settled['winner_count'],
                'lose_count' => $settled['lose_count'],
                'message' => '최신 회차 동기화 후처리와 다음 회차 OPEN 보정을 완료했습니다.'
            );
        } else {
            sql_query('ROLLBACK', false);
            $result = array(
                'ok' => false,
                'mode' => 'repair',
                'round_no' => (int)$last_synced['round_no'],
                'settled' => $settled,
                'finalized' => $finalized
            );
        }
    }

    $is_done = true;
} else {
    $data = mjtto_fetch_round_detail($round_no);

    if (empty($data['ok'])) {
        $result = array(
            'ok' => false,
            'round_no' => $round_no,
            'detail' => $data
        );
    } else {
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
                   SET draw_date = '{$draw_date}',
                       payout_deadline = DATE_ADD('{$draw_date}', INTERVAL 6 MONTH),
                       status = 'CLOSE'
                 WHERE round_id = '{$round_id}'
            ", false);

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
            $result = array(
                'ok' => false,
                'save_type' => $save_type,
                'round_no' => $drw_no,
                'detail' => $settled
            );
        } else {
            $finalized = mjtto_finalize_synced_round($drw_no, $data['draw_date']);
            if (empty($finalized['ok'])) {
                sql_query('ROLLBACK', false);
                $result = array(
                    'ok' => false,
                    'save_type' => $save_type,
                    'round_no' => $drw_no,
                    'detail' => $finalized
                );
            } else {
                sql_query('COMMIT', false);

                $result = array(
                    'ok' => true,
                    'save_type' => $save_type,
                    'round_no' => $drw_no,
                    'draw_date' => $data['draw_date'],
                    'win_a' => $data['win_a'],
                    'win_b' => $data['win_b'],
                    'win_c' => $data['win_c'],
                    'win_d' => $data['win_d'],
                    'win_e' => $data['win_e'],
                    'win_f' => $data['win_f'],
                    'bonus_no' => $data['bonus_no'],
                    'source_url' => $data['source_url'],
                    'next_round_no' => $finalized['next_round_no'],
                    'next_draw_date' => $finalized['next_draw_date'],
                    'winner_count' => $settled['winner_count'],
                    'lose_count' => $settled['lose_count'],
                    'skip_claim_count' => $settled['skip_claim_count']
                );
            }
        }
    }
}

$next_round_no = $round_no + 1;
$auto_next = (!$is_done && !empty($result['ok']) && $next_round_no <= $latest_round_no);

$g5['title'] = '로또 회차 동기화';
include_once __DIR__ . '/_admin_head.php';

if ($auto_next) {
    echo '<meta http-equiv="refresh" content="0;url=./lotto_sync.php?round_no=' . $next_round_no . '">';
}
?>
<style>
.box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.03);}
h1{margin:0 0 20px;font-size:28px;}
ul{margin:0;padding-left:18px;line-height:1.9;}
pre{white-space:pre-wrap;word-break:break-all;font-size:12px;line-height:1.6;margin:0;}
.log-box{margin-top:24px;padding:16px;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa;}
.btns{margin-top:24px;}
.btns a{display:inline-block;min-width:110px;height:42px;line-height:42px;padding:0 18px;border-radius:8px;text-decoration:none;font-size:14px;background:#111827;color:#fff;margin-right:8px;}
.desc{margin:0 0 16px;color:#4b5563;line-height:1.7;}
.ok{color:#065f46;font-weight:700;}
.bad{color:#b91c1c;font-weight:700;}
</style>

<div class="box">
    <h1>로또 회차 동기화</h1>
    <p class="desc">한 번에 1회차만 처리하고, 성공하면 다음 회차로 자동 이동합니다.</p>

    <ul>
        <li>최신 회차: <?php echo number_format($latest_round_no); ?>회</li>
        <li>DB 기준 다음 회차: <?php echo number_format($db_next_round); ?>회</li>
        <li>현재 처리 회차: <?php echo $is_done ? '완료' : number_format($round_no) . '회'; ?></li>
    </ul>

    <div class="log-box" style="margin-top:16px;">
        <pre><?php
if ($is_done) {
    print_r(array(
        'ok' => true,
        'message' => '모든 회차 처리가 끝났습니다.',
        'latest_round_no' => $latest_round_no
    ));
} else {
    print_r($result);
}
?></pre>
    </div>

    <div class="btns">
        <?php if (!$is_done && !$auto_next) { ?>
        <a href="./lotto_sync.php?round_no=<?php echo $next_round_no; ?>">다음 회차</a>
        <?php } ?>
        <a href="./lotto_sync.php?round_no=1">1회부터 시작</a>
        <a href="./lotto_sync.php">DB 다음 회차부터</a>
        <a href="./index.php">관리자 홈</a>
    </div>
</div>

</div>
</body>
</html>