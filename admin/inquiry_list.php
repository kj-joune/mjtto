<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-21 14:42:00 KST  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();
if ($auth['role'] !== 'SUPER_ADMIN') {
    alert('최고관리자만 접근할 수 있습니다.', './index.php');
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

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$rows = 20;
$from_record = ($page - 1) * $rows;
$stx = trim((string)($_GET['stx'] ?? ''));
$sdate = trim((string)($_GET['sdate'] ?? ''));
$edate = trim((string)($_GET['edate'] ?? ''));

if ($sdate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sdate)) {
    $sdate = '';
}

if ($edate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $edate)) {
    $edate = '';
}

$bo_table = 'inquiry';
$board = sql_fetch(" SELECT bo_table, bo_subject FROM {$g5['board_table']} WHERE bo_table = '{$bo_table}' ");
if (!$board || empty($board['bo_table'])) {
    alert('문의 게시판 설정을 찾을 수 없습니다.', './index.php');
}

$write_table = $g5['write_prefix'] . $bo_table;
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

$sql_search .= " AND (wr_parent = wr_id OR wr_parent = 0) ";

$total_row = sql_fetch(" SELECT COUNT(*) AS cnt FROM {$write_table} {$sql_search} ");
$total_count = (int)($total_row['cnt'] ?? 0);
$total_page = max(1, (int)ceil($total_count / $rows));

$summary = sql_fetch("
    SELECT
        COUNT(*) AS total_count,
        COALESCE(SUM(CASE WHEN wr_datetime >= '" . date('Y-m-d') . " 00:00:00' THEN 1 ELSE 0 END), 0) AS today_count,
        COALESCE(SUM(CASE WHEN wr_datetime >= '" . date('Y-m-01') . " 00:00:00' THEN 1 ELSE 0 END), 0) AS month_count
    FROM {$write_table}
    {$sql_search}
");

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
    FROM {$write_table}
    {$sql_search}
    ORDER BY wr_id DESC
    LIMIT {$from_record}, {$rows}
");

$has_filter = ($stx !== '' || $sdate !== '' || $edate !== '');
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
.search-form{display:grid;grid-template-columns:minmax(220px,1.4fr) minmax(140px,.7fr) minmax(140px,.7fr) auto auto;gap:8px;margin-bottom:18px;}
.search-form input{height:42px;padding:0 12px;border:1px solid #d1d5db;border-radius:8px;box-sizing:border-box;}
.search-form button,.search-form a{height:42px;line-height:42px;padding:0 16px;border-radius:8px;border:0;text-decoration:none;font-size:14px;box-sizing:border-box;text-align:center;}
.btn-primary{background:#111827;color:#fff;cursor:pointer;}
.btn-light{background:#f3f4f6;color:#111827;}
.count{margin-bottom:12px;color:#666;font-size:14px;}
.table-wrap{overflow-x:auto;}
.table{width:100%;border-collapse:collapse;min-width:1080px;}
.table th,.table td{padding:14px 10px;border-top:1px solid #f0f0f0;text-align:left;font-size:14px;vertical-align:top;}
.table th{background:#fafafa;white-space:nowrap;}
.company{font-weight:700;color:#111827;}
.meta{display:block;margin-top:4px;font-size:12px;color:#6b7280;}
.excerpt{color:#374151;line-height:1.6;white-space:normal;}
.subject{font-weight:700;color:#111827;line-height:1.5;}
.link-inline{display:inline-block;margin-top:8px;color:#2563eb;text-decoration:none;font-size:13px;}
.empty{padding:36px 10px;text-align:center;color:#777;}
.paging{margin-top:20px;text-align:center;}
.paging a,.paging strong{display:inline-block;min-width:36px;height:36px;line-height:36px;margin:0 3px;padding:0 8px;border-radius:8px;text-decoration:none;font-size:14px;}
.paging a{background:#f3f4f6;color:#111827;}
.paging strong{background:#111827;color:#fff;}
@media (max-width: 900px){
    .search-form{grid-template-columns:1fr 1fr; }
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
</div>

<div class="box">
    <form method="get" class="search-form">
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" placeholder="회사명, 담당자, 연락처, 이메일, 제목, 내용 검색">
        <input type="date" name="sdate" value="<?php echo get_text($sdate); ?>">
        <input type="date" name="edate" value="<?php echo get_text($edate); ?>">
        <button type="submit" class="btn-primary">검색</button>
        <a href="./inquiry_list.php" class="btn-light">초기화</a>
    </form>

    <div class="count">총 <?php echo number_format($total_count); ?>건<?php if ($has_filter) { ?> · 검색조건 적용<?php } ?></div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th width="60">번호</th>
                    <th width="190">회사명 / 담당자</th>
                    <th width="170">연락처 / 이메일</th>
                    <th width="120">업종</th>
                    <th>문의내용</th>
                    <th width="150">접수일시</th>
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
                $view_href = G5_BBS_URL . '/board.php?bo_table=' . urlencode($bo_table) . '&wr_id=' . (int)$row['wr_id'];
            ?>
                <tr>
                    <td><?php echo $number; ?></td>
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
                        <a href="<?php echo $view_href; ?>" target="_blank" class="link-inline">게시판 원문 열기</a>
                    </td>
                    <td><?php echo get_text((string)$row['wr_datetime']); ?></td>
                </tr>
            <?php
                $number--;
            }

            if ($i === 0) {
                echo '<tr><td colspan="6" class="empty">접수된 웹 문의가 없습니다.</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>

    <div class="paging">
        <?php
        $qstr = '';
        if ($stx !== '') $qstr .= '&stx=' . urlencode($stx);
        if ($sdate !== '') $qstr .= '&sdate=' . urlencode($sdate);
        if ($edate !== '') $qstr .= '&edate=' . urlencode($edate);

        for ($p = 1; $p <= $total_page; $p++) {
            if ($p == $page) {
                echo '<strong>' . $p . '</strong>';
            } else {
                echo '<a href="./inquiry_list.php?page=' . $p . $qstr . '">' . $p . '</a>';
            }
        }
        ?>
    </div>
</div>
</div>
</body>
</html>
