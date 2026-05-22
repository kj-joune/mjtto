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

        if (!empty($params['page'])) $query[] = 'page=' . (int)$params['page'];
        if (!empty($params['stx'])) $query[] = 'stx=' . urlencode((string)$params['stx']);
        if (!empty($params['sdate'])) $query[] = 'sdate=' . urlencode((string)$params['sdate']);
        if (!empty($params['edate'])) $query[] = 'edate=' . urlencode((string)$params['edate']);
        if (!empty($params['status'])) $query[] = 'status=' . urlencode((string)$params['status']);

        return implode('&', $query);
    }
}

list($board, $write_table, $columns) = mjtto_inquiry_get_board();
if (!$board || $write_table === '') {
    alert('문의 게시판 설정을 찾을 수 없습니다.', './index.php');
}

$read_flag_column = isset($columns['wr_6']) ? 'wr_6' : '';
$read_at_column = isset($columns['wr_7']) ? 'wr_7' : '';
$read_admin_column = isset($columns['wr_8']) ? 'wr_8' : '';

$wr_id = isset($_GET['wr_id']) ? (int)$_GET['wr_id'] : 0;
if ($wr_id < 1) {
    alert('잘못된 문의 번호입니다.', './inquiry_list.php');
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$stx = trim((string)($_GET['stx'] ?? ''));
$sdate = trim((string)($_GET['sdate'] ?? ''));
$edate = trim((string)($_GET['edate'] ?? ''));
$status = trim((string)($_GET['status'] ?? 'all'));
if (!in_array($status, array('all', 'unread', 'read'), true)) {
    $status = 'all';
}

$row = sql_fetch(" SELECT * FROM {$write_table} WHERE wr_id = '{$wr_id}' AND (wr_parent = wr_id OR wr_parent = 0) ");
if (!$row || empty($row['wr_id'])) {
    alert('문의 내역을 찾을 수 없습니다.', './inquiry_list.php');
}

if ($read_flag_column !== '' && trim((string)($row[$read_flag_column] ?? '')) !== 'Y') {
    $set_parts = array("{$read_flag_column} = 'Y'");
    if ($read_at_column !== '') $set_parts[] = "{$read_at_column} = '" . G5_TIME_YMDHIS . "'";
    if ($read_admin_column !== '') $set_parts[] = "{$read_admin_column} = '" . sql_real_escape_string($member['mb_id']) . "'";
    sql_query(" UPDATE {$write_table} SET " . implode(', ', $set_parts) . " WHERE wr_id = '{$wr_id}' ");

    $row[$read_flag_column] = 'Y';
    if ($read_at_column !== '') $row[$read_at_column] = G5_TIME_YMDHIS;
    if ($read_admin_column !== '') $row[$read_admin_column] = $member['mb_id'];
}

$list_query = mjtto_inquiry_get_return_query(array(
    'page' => $page,
    'stx' => $stx,
    'sdate' => $sdate,
    'edate' => $edate,
    'status' => $status
));
$list_href = './inquiry_list.php' . ($list_query !== '' ? '?' . $list_query : '');
$g5['title'] = '웹 문의 상세';
include_once __DIR__ . '/_admin_head.php';
?>
<style>
.top{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap;}
.top h1{margin:0;font-size:28px;}
.btn{display:inline-flex;align-items:center;justify-content:center;height:38px;padding:0 16px;border-radius:8px;text-decoration:none;font-size:14px;border:0;cursor:pointer;}
.btn-primary{background:#111827;color:#fff;}
.btn-light{background:#f3f4f6;color:#111827;}
.box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.03);margin-bottom:20px;}
.info-table{width:100%;border-collapse:collapse;}
.info-table th,.info-table td{padding:14px 10px;border-top:1px solid #f0f0f0;text-align:left;font-size:14px;vertical-align:top;}
.info-table th{width:180px;background:#fafafa;}
.content-box{white-space:pre-wrap;line-height:1.8;color:#1f2937;}
.badge{display:inline-flex;align-items:center;justify-content:center;min-width:58px;height:28px;padding:0 10px;border-radius:999px;font-size:12px;font-weight:800;line-height:1;}
.badge-read{background:#ecfdf5;color:#047857;}
.badge-unread{background:#fff7ed;color:#c2410c;}
.subtext{font-size:12px;color:#6b7280;line-height:1.6;margin-top:6px;}
</style>

<div class="top">
    <h1>웹 문의 상세</h1>
    <div>
        <a href="<?php echo $list_href; ?>" class="btn btn-primary">문의목록</a>
        <a href="<?php echo G5_BBS_URL . '/board.php?bo_table=inquiry&wr_id=' . (int)$row['wr_id']; ?>" target="_blank" class="btn btn-light">게시판 원문</a>
    </div>
</div>

<div class="box">
    <table class="info-table">
        <tr>
            <th>상태</th>
            <td>
                <?php if ($read_flag_column !== '' && trim((string)($row[$read_flag_column] ?? '')) === 'Y') { ?>
                    <span class="badge badge-read">읽음</span>
                    <?php
                    $read_at_text = ($read_at_column !== '' && !empty($row[$read_at_column])) ? (string)$row[$read_at_column] : '';
                    $read_admin_text = ($read_admin_column !== '' && !empty($row[$read_admin_column])) ? (string)$row[$read_admin_column] : '';
                    if ($read_at_text !== '' || $read_admin_text !== '') {
                    ?>
                    <div class="subtext">처리시각: <?php echo get_text($read_at_text); ?><?php if ($read_admin_text !== '') { ?> / <?php echo get_text($read_admin_text); ?><?php } ?></div>
                    <?php } ?>
                <?php } else { ?>
                    <span class="badge badge-unread">안읽음</span>
                <?php } ?>
            </td>
        </tr>
        <tr><th>제목</th><td><?php echo get_text((string)$row['wr_subject']); ?></td></tr>
        <tr><th>회사명</th><td><?php echo get_text((string)($row['wr_1'] ?? '-')); ?></td></tr>
        <tr><th>담당자</th><td><?php echo get_text((string)($row['wr_2'] ?: $row['wr_name'])); ?></td></tr>
        <tr><th>연락처</th><td><?php echo get_text((string)($row['wr_3'] ?? '-')); ?></td></tr>
        <tr><th>이메일</th><td><?php echo get_text((string)(($row['wr_5'] ?? '') !== '' ? $row['wr_5'] : $row['wr_email'])); ?></td></tr>
        <tr><th>업종</th><td><?php echo get_text((string)($row['wr_4'] ?? '-')); ?></td></tr>
        <tr><th>접수일시</th><td><?php echo get_text((string)$row['wr_datetime']); ?></td></tr>
        <tr><th>문의내용</th><td><div class="content-box"><?php echo get_text((string)$row['wr_content']); ?></div></td></tr>
    </table>
</div>
</div>
</body>
</html>
