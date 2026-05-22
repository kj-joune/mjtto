<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-22 09:48:00 KST  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();
if ($auth['role'] !== 'SUPER_ADMIN') {
    alert('최고관리자만 접근할 수 있습니다.', './index.php');
}

if (!function_exists('mjtto_inquiry_get_board')) {
    function mjtto_inquiry_get_board()
    {
        global $g5;

        $bo_table = 'inquiry';
        $board = sql_fetch(" SELECT bo_table, bo_subject FROM {$g5['board_table']} WHERE bo_table = '{$bo_table}' ");
        if (!$board || empty($board['bo_table'])) {
            return array(null, '', array());
        }

        $write_table = $g5['write_prefix'] . $bo_table;
        $column_result = sql_query(" SHOW COLUMNS FROM `{$write_table}` ", false);
        $columns = array();

        if ($column_result) {
            while ($column_row = sql_fetch_array($column_result)) {
                $columns[$column_row['Field']] = true;
            }
        }

        return array($board, $write_table, $columns);
    }
}

if (!function_exists('mjtto_inquiry_get_return_query')) {
    function mjtto_inquiry_get_return_query($params = array())
    {
        $query = array();

        if (!empty($params['page'])) {
            $query[] = 'page=' . (int)$params['page'];
        }
        if (!empty($params['stx'])) {
            $query[] = 'stx=' . urlencode((string)$params['stx']);
        }
        if (!empty($params['sdate'])) {
            $query[] = 'sdate=' . urlencode((string)$params['sdate']);
        }
        if (!empty($params['edate'])) {
            $query[] = 'edate=' . urlencode((string)$params['edate']);
        }
        if (!empty($params['status'])) {
            $query[] = 'status=' . urlencode((string)$params['status']);
        }

        return implode('&', $query);
    }
}

if (!function_exists('mjtto_inquiry_excerpt')) {
    function mjtto_inquiry_excerpt($text, $length = 140)
    {
        $text = trim((string)$text);
        if ($text === '') {
            return '';
        }

        $text = preg_replace("/\r\n|\r|\n/u", ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($text, 0, $length, '...', 'UTF-8');
        }

        if (strlen($text) > $length) {
            return substr($text, 0, $length) . '...';
        }

        return $text;
    }
}

list($board, $write_table, $columns) = mjtto_inquiry_get_board();
if (!$board || $write_table === '') {
    alert('문의 게시판 설정을 찾을 수 없습니다.', './index.php');
}

$read_flag_column = isset($columns['wr_6']) ? 'wr_6' : '';
$read_at_column = isset($columns['wr_7']) ? 'wr_7' : '';
$read_admin_column = isset($columns['wr_8']) ? 'wr_8' : '';

$action_token = get_session('ss_mjtto_inquiry_admin_token');
if (!$action_token) {
    $action_token = md5(uniqid('', true));
    set_session('ss_mjtto_inquiry_admin_token', $action_token);
}

$page = isset($_REQUEST['page']) ? max(1, (int)$_REQUEST['page']) : 1;
$stx = trim((string)($_REQUEST['stx'] ?? ''));
$sdate = trim((string)($_REQUEST['sdate'] ?? ''));
$edate = trim((string)($_REQUEST['edate'] ?? ''));
$status = trim((string)($_REQUEST['status'] ?? 'all'));

if (!in_array($status, array('all', 'unread', 'read'), true)) {
    $status = 'all';
}

if ($sdate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sdate)) {
    $sdate = '';
}

if ($edate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $edate)) {
    $edate = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_token = trim((string)($_POST['token'] ?? ''));
    if ($posted_token === '' || !hash_equals($action_token, $posted_token)) {
        alert('잘못된 요청입니다.', './inquiry_list.php');
    }

    $action = trim((string)($_POST['action'] ?? ''));
    $wr_id = isset($_POST['wr_id']) ? (int)$_POST['wr_id'] : 0;
    $return_query = mjtto_inquiry_get_return_query(array(
        'page' => $page,
        'stx' => $stx,
        'sdate' => $sdate,
        'edate' => $edate,
        'status' => $status
    ));
    $return_url = './inquiry_list.php' . ($return_query !== '' ? '?' . $return_query : '');

    if ($wr_id < 1) {
        alert('잘못된 문의 번호입니다.', $return_url);
    }

    $row = sql_fetch(" SELECT wr_id, wr_parent FROM {$write_table} WHERE wr_id = '{$wr_id}' ");
    if (!$row || empty($row['wr_id'])) {
        alert('문의 내역을 찾을 수 없습니다.', $return_url);
    }

    if ($action === 'mark_read' || $action === 'mark_unread') {
        if ($read_flag_column === '') {
            alert('읽음 상태를 저장할 수 없는 게시판 구조입니다.', $return_url);
        }

        $set_parts = array();
        if ($action === 'mark_read') {
            $set_parts[] = "{$read_flag_column} = 'Y'";
            if ($read_at_column !== '') $set_parts[] = "{$read_at_column} = '" . G5_TIME_YMDHIS . "'";
            if ($read_admin_column !== '') $set_parts[] = "{$read_admin_column} = '" . sql_real_escape_string($member['mb_id']) . "'";
        } else {
            $set_parts[] = "{$read_flag_column} = ''";
            if ($read_at_column !== '') $set_parts[] = "{$read_at_column} = ''";
            if ($read_admin_column !== '') $set_parts[] = "{$read_admin_column} = ''";
        }

        sql_query(" UPDATE {$write_table} SET " . implode(', ', $set_parts) . " WHERE wr_id = '{$wr_id}' ");
        goto_url($return_url);
    }

    if ($action === 'delete') {
        sql_query(" DELETE FROM {$write_table} WHERE wr_id = '{$wr_id}' OR wr_parent = '{$wr_id}' ");
        sql_query(" DELETE FROM {$g5['board_new_table']} WHERE bo_table = 'inquiry' AND (wr_id = '{$wr_id}' OR wr_parent = '{$wr_id}') ");
        sql_query(" UPDATE {$g5['board_table']} SET bo_count_write = IF(bo_count_write > 0, bo_count_write - 1, 0) WHERE bo_table = 'inquiry' ");

        if (function_exists('delete_cache_latest')) {
            delete_cache_latest('inquiry');
        }

        goto_url($return_url);
    }

    alert('처리할 수 없는 요청입니다.', $return_url);
}

$rows = 20;
$from_record = ($page - 1) * $rows;

$sql_search = " WHERE 1=1 ";
if ($stx !== '') {
    $stx_sql = sql_real_escape_string($stx);
    $sql_search .= "
        AND (
            wr_subject LIKE '%{$stx_sql}%'
            OR wr_content LIKE '%{$stx_sql}%'
            OR wr_name LIKE '%{$stx_sql}%'
            OR wr_email LIKE '%{$stx_sql}%'
            OR wr_1 LIKE '%{$stx_sql}%'
            OR wr_2 LIKE '%{$stx_sql}%'
            OR wr_3 LIKE '%{$stx_sql}%'
            OR wr_4 LIKE '%{$stx_sql}%'
            OR wr_5 LIKE '%{$stx_sql}%'
        )
    ";
}

if ($sdate !== '') {
    $sdate_sql = sql_real_escape_string($sdate);
    $sql_search .= " AND wr_datetime >= '{$sdate_sql} 00:00:00' ";
}

if ($edate !== '') {
    $edate_sql = sql_real_escape_string($edate);
    $sql_search .= " AND wr_datetime <= '{$edate_sql} 23:59:59' ";
}

if ($read_flag_column !== '') {
    if ($status === 'unread') {
        $sql_search .= " AND ({$read_flag_column} = '' OR {$read_flag_column} IS NULL) ";
    } elseif ($status === 'read') {
        $sql_search .= " AND {$read_flag_column} = 'Y' ";
    }
}

$sql_search .= " AND (wr_parent = wr_id OR wr_parent = 0) ";

$total_row = sql_fetch(" SELECT COUNT(*) AS cnt FROM {$write_table} {$sql_search} ");
$total_count = (int)($total_row['cnt'] ?? 0);
$total_page = max(1, (int)ceil($total_count / $rows));

$summary = sql_fetch("
    SELECT
        COUNT(*) AS total_count,
        COALESCE(SUM(CASE WHEN wr_datetime >= '" . date('Y-m-d') . " 00:00:00' THEN 1 ELSE 0 END), 0) AS today_count,
        COALESCE(SUM(CASE WHEN wr_datetime >= '" . date('Y-m-01') . " 00:00:00' THEN 1 ELSE 0 END), 0) AS month_count" .
        ($read_flag_column !== '' ? ",
        COALESCE(SUM(CASE WHEN {$read_flag_column} = 'Y' THEN 1 ELSE 0 END), 0) AS read_count,
        COALESCE(SUM(CASE WHEN {$read_flag_column} = '' OR {$read_flag_column} IS NULL THEN 1 ELSE 0 END), 0) AS unread_count" : "") . "
    FROM {$write_table}
    {$sql_search}
");

$select_read_columns = '';
if ($read_flag_column !== '') {
    $select_read_columns .= ", {$read_flag_column} AS inquiry_read_flag";
}
if ($read_at_column !== '') {
    $select_read_columns .= ", {$read_at_column} AS inquiry_read_at";
}
if ($read_admin_column !== '') {
    $select_read_columns .= ", {$read_admin_column} AS inquiry_read_admin";
}

$result = sql_query("
    SELECT
        wr_id,
        wr_subject,
        wr_content,
        wr_name,
        wr_email,
        wr_datetime,
        wr_1,
        wr_2,
        wr_3,
        wr_4,
        wr_5
        {$select_read_columns}
    FROM {$write_table}
    {$sql_search}
    ORDER BY wr_id DESC
    LIMIT {$from_record}, {$rows}
");

$has_filter = ($stx !== '' || $sdate !== '' || $edate !== '' || $status !== 'all');
$return_query = mjtto_inquiry_get_return_query(array(
    'page' => $page,
    'stx' => $stx,
    'sdate' => $sdate,
    'edate' => $edate,
    'status' => $status
));
$g5['title'] = '웹 문의 목록';
include_once __DIR__ . '/_admin_head.php';
?>
<style>
.top{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:20px;}
.top h1{margin:0;font-size:28px;}
.summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:18px;}
.summary-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px 20px;box-shadow:0 2px 10px rgba(0,0,0,0.03);}
.summary-label{font-size:13px;color:#6b7280;margin-bottom:8px;}
.summary-value{font-size:28px;font-weight:800;color:#111827;line-height:1.1;}
.summary-desc{margin-top:8px;font-size:13px;color:#6b7280;line-height:1.5;}
.box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.03);}
.search-form{display:grid;grid-template-columns:minmax(220px,1.2fr) minmax(130px,.65fr) minmax(130px,.65fr) minmax(130px,.55fr) auto auto;gap:8px;margin-bottom:18px;}
.search-form input,.search-form select{height:42px;padding:0 12px;border:1px solid #d1d5db;border-radius:8px;box-sizing:border-box;background:#fff;}
.search-form button,.search-form a{height:42px;line-height:42px;padding:0 16px;border-radius:8px;border:0;text-decoration:none;font-size:14px;box-sizing:border-box;text-align:center;}
.btn-primary{background:#111827;color:#fff;cursor:pointer;}
.btn-light{background:#f3f4f6;color:#111827;}
.btn-danger{background:#fff1f2;color:#be123c;}
.btn-inline{display:inline-flex;align-items:center;justify-content:center;min-width:78px;height:34px;padding:0 12px;border-radius:8px;border:0;text-decoration:none;font-size:12px;font-weight:700;cursor:pointer;background:#f3f4f6;color:#111827;}
.btn-inline + .btn-inline{margin-left:6px;}
.count{margin-bottom:12px;color:#666;font-size:14px;}
.table-wrap{overflow-x:auto;}
.table{width:100%;border-collapse:collapse;min-width:1220px;}
.table th,.table td{padding:14px 10px;border-top:1px solid #f0f0f0;text-align:left;font-size:14px;vertical-align:top;}
.table th{background:#fafafa;white-space:nowrap;}
.company{font-weight:700;color:#111827;}
.meta{display:block;margin-top:4px;font-size:12px;color:#6b7280;}
.excerpt{color:#374151;line-height:1.6;white-space:normal;}
.subject{font-weight:700;color:#111827;line-height:1.5;}
.empty{padding:36px 10px;text-align:center;color:#777;}
.paging{margin-top:20px;text-align:center;}
.paging a,.paging strong{display:inline-block;min-width:36px;height:36px;line-height:36px;margin:0 3px;padding:0 8px;border-radius:8px;text-decoration:none;font-size:14px;}
.paging a{background:#f3f4f6;color:#111827;}
.paging strong{background:#111827;color:#fff;}
.badge{display:inline-flex;align-items:center;justify-content:center;min-width:58px;height:28px;padding:0 10px;border-radius:999px;font-size:12px;font-weight:800;line-height:1;}
.badge-read{background:#ecfdf5;color:#047857;}
.badge-unread{background:#fff7ed;color:#c2410c;}
.action-row{display:flex;flex-wrap:wrap;gap:6px;}
.action-form{display:inline;}
.subtext{font-size:12px;color:#6b7280;line-height:1.6;margin-top:6px;}
@media (max-width: 900px){
    .search-form{grid-template-columns:1fr 1fr;}
}
</style>
<div class="top">
    <h1>웹 문의 목록</h1>
</div>

<div class="summary-grid">
    <div class="summary-card">
        <div class="summary-label"><?php echo $has_filter ? '검색 문의건' : '전체 문의건'; ?></div>
        <div class="summary-value"><?php echo number_format((int)($summary['total_count'] ?? 0)); ?></div>
        <div class="summary-desc"><?php echo $has_filter ? '현재 검색조건에 맞는 문의 접수 건수' : '웹사이트를 통해 저장된 전체 문의 접수 건수'; ?></div>
    </div>
    <div class="summary-card">
        <div class="summary-label">오늘 접수</div>
        <div class="summary-value"><?php echo number_format((int)($summary['today_count'] ?? 0)); ?></div>
        <div class="summary-desc">오늘 00시 이후 접수된 웹 문의 건수</div>
    </div>
    <div class="summary-card">
        <div class="summary-label">이번 달 접수</div>
        <div class="summary-value"><?php echo number_format((int)($summary['month_count'] ?? 0)); ?></div>
        <div class="summary-desc"><?php echo date('Y-m'); ?> 기준 누적 문의 건수</div>
    </div>
    <?php if ($read_flag_column !== '') { ?>
    <div class="summary-card">
        <div class="summary-label">읽음 / 안읽음</div>
        <div class="summary-value"><?php echo number_format((int)($summary['unread_count'] ?? 0)); ?> / <?php echo number_format((int)($summary['read_count'] ?? 0)); ?></div>
        <div class="summary-desc">안읽음 문의를 먼저 확인해 빠르게 대응할 수 있습니다.</div>
    </div>
    <?php } ?>
</div>

<div class="box">
    <form method="get" class="search-form">
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" placeholder="회사명, 담당자, 연락처, 이메일, 제목, 내용 검색">
        <input type="date" name="sdate" value="<?php echo get_text($sdate); ?>">
        <input type="date" name="edate" value="<?php echo get_text($edate); ?>">
        <select name="status">
            <option value="all"<?php echo $status === 'all' ? ' selected' : ''; ?>>전체 상태</option>
            <option value="unread"<?php echo $status === 'unread' ? ' selected' : ''; ?>>안읽음</option>
            <option value="read"<?php echo $status === 'read' ? ' selected' : ''; ?>>읽음</option>
        </select>
        <button type="submit" class="btn-primary">검색</button>
        <a href="./inquiry_list.php" class="btn-light">초기화</a>
    </form>

    <div class="count">총 <?php echo number_format($total_count); ?>건<?php if ($has_filter) { ?> · 검색조건 적용<?php } ?></div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th width="60">번호</th>
                    <th width="90">상태</th>
                    <th width="180">회사명 / 담당자</th>
                    <th width="170">연락처 / 이메일</th>
                    <th width="120">업종</th>
                    <th>문의내용</th>
                    <th width="150">접수일시</th>
                    <th width="240">관리</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $number = $total_count - $from_record;
            $i = 0;
            while ($row = sql_fetch_array($result)) {
                $i++;
                $company_name = trim((string)($row['wr_1'] ?? ''));
                $manager_name = trim((string)($row['wr_2'] ?? ''));
                $phone = trim((string)($row['wr_3'] ?? ''));
                $industry = trim((string)($row['wr_4'] ?? ''));
                $email = trim((string)($row['wr_5'] ?? $row['wr_email'] ?? ''));
                $subject = trim((string)($row['wr_subject'] ?? ''));
                $content = trim((string)($row['wr_content'] ?? ''));
                $is_read = ($read_flag_column !== '' && trim((string)($row['inquiry_read_flag'] ?? '')) === 'Y');
                $view_href = './inquiry_view.php?wr_id=' . (int)$row['wr_id'];
                if ($return_query !== '') {
                    $view_href .= '&' . $return_query;
                }
            ?>
                <tr>
                    <td><?php echo $number; ?></td>
                    <td>
                        <?php if ($is_read) { ?>
                            <span class="badge badge-read">읽음</span>
                        <?php } else { ?>
                            <span class="badge badge-unread">안읽음</span>
                        <?php } ?>
                    </td>
                    <td>
                        <span class="company"><?php echo get_text($company_name !== '' ? $company_name : '-'); ?></span>
                        <span class="meta"><?php echo get_text($manager_name !== '' ? $manager_name : ($row['wr_name'] ?? '-')); ?></span>
                    </td>
                    <td>
                        <?php echo get_text($phone !== '' ? $phone : '-'); ?>
                        <span class="meta"><?php echo get_text($email !== '' ? $email : '-'); ?></span>
                    </td>
                    <td><?php echo get_text($industry !== '' ? $industry : '-'); ?></td>
                    <td>
                        <div class="subject"><?php echo get_text($subject !== '' ? $subject : '제목 없음'); ?></div>
                        <div class="excerpt"><?php echo nl2br(get_text(mjtto_inquiry_excerpt($content, 170))); ?></div>
                        <?php if (!empty($row['inquiry_read_at']) || !empty($row['inquiry_read_admin'])) { ?>
                        <div class="subtext">읽음 처리: <?php echo get_text((string)($row['inquiry_read_at'] ?? '')); ?> <?php if (!empty($row['inquiry_read_admin'])) { ?> / <?php echo get_text((string)$row['inquiry_read_admin']); ?><?php } ?></div>
                        <?php } ?>
                    </td>
                    <td><?php echo get_text((string)$row['wr_datetime']); ?></td>
                    <td>
                        <div class="action-row">
                            <a href="<?php echo $view_href; ?>" class="btn-inline">상세보기</a>
                            <form method="post" class="action-form" onsubmit="return confirm('<?php echo $is_read ? '안읽음 상태로 바꾸시겠습니까?' : '읽음 상태로 표시하시겠습니까?'; ?>');">
                                <input type="hidden" name="token" value="<?php echo get_text($action_token); ?>">
                                <input type="hidden" name="action" value="<?php echo $is_read ? 'mark_unread' : 'mark_read'; ?>">
                                <input type="hidden" name="wr_id" value="<?php echo (int)$row['wr_id']; ?>">
                                <input type="hidden" name="page" value="<?php echo $page; ?>">
                                <input type="hidden" name="stx" value="<?php echo get_text($stx); ?>">
                                <input type="hidden" name="sdate" value="<?php echo get_text($sdate); ?>">
                                <input type="hidden" name="edate" value="<?php echo get_text($edate); ?>">
                                <input type="hidden" name="status" value="<?php echo get_text($status); ?>">
                                <button type="submit" class="btn-inline"><?php echo $is_read ? '안읽음' : '읽음'; ?></button>
                            </form>
                            <form method="post" class="action-form" onsubmit="return confirm('이 문의를 삭제하시겠습니까? 삭제 후 복구할 수 없습니다.');">
                                <input type="hidden" name="token" value="<?php echo get_text($action_token); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="wr_id" value="<?php echo (int)$row['wr_id']; ?>">
                                <input type="hidden" name="page" value="<?php echo $page; ?>">
                                <input type="hidden" name="stx" value="<?php echo get_text($stx); ?>">
                                <input type="hidden" name="sdate" value="<?php echo get_text($sdate); ?>">
                                <input type="hidden" name="edate" value="<?php echo get_text($edate); ?>">
                                <input type="hidden" name="status" value="<?php echo get_text($status); ?>">
                                <button type="submit" class="btn-inline btn-danger">삭제</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php
                $number--;
            }

            if ($i === 0) {
                echo '<tr><td colspan="8" class="empty">접수된 웹 문의가 없습니다.</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>

    <div class="paging">
        <?php
        for ($p = 1; $p <= $total_page; $p++) {
            $paging_query = mjtto_inquiry_get_return_query(array(
                'page' => $p,
                'stx' => $stx,
                'sdate' => $sdate,
                'edate' => $edate,
                'status' => $status
            ));

            if ($p == $page) {
                echo '<strong>' . $p . '</strong>';
            } else {
                echo '<a href="./inquiry_list.php?' . $paging_query . '">' . $p . '</a>';
            }
        }
        ?>
    </div>
</div>
</div>
</body>
</html>
