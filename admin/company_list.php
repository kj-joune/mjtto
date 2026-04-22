<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-04-10 13:08:00 KST  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();
if ($auth['role'] !== 'SUPER_ADMIN') {
    alert('최고관리자만 접근할 수 있습니다.', './index.php');
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$rows = 20;
$stx  = trim((string)($_GET['stx'] ?? ''));
$from_record = ($page - 1) * $rows;

$sql_search = " WHERE c.company_type = 'CONTRACT' ";
if ($stx !== '') {
    $stx_sql = sql_real_escape_string($stx);
    $sql_search .= "\n        AND (\n            c.company_name LIKE '%{$stx_sql}%'\n            OR c.company_code LIKE '%{$stx_sql}%'\n            OR m.mb_id LIKE '%{$stx_sql}%'\n            OR m.mb_name LIKE '%{$stx_sql}%'\n        )\n    ";
}

$sql_common = "\n    FROM mz_company c\n    LEFT JOIN mz_company_user cu\n        ON c.company_id = cu.company_id\n       AND cu.role_code = 'COMPANY_ADMIN'\n       AND cu.status = 1\n    LEFT JOIN g5_member m\n        ON cu.mb_id = m.mb_id\n    {$sql_search}\n";

$total_row = sql_fetch(" SELECT COUNT(*) AS cnt {$sql_common} ");
$total_count = (int)($total_row['cnt'] ?? 0);
$total_page  = max(1, (int)ceil($total_count / $rows));

$result = sql_query("\n    SELECT\n        c.company_id,\n        c.company_name,\n        c.company_code,\n        c.status,\n        c.created_at,\n        cu.mb_id AS admin_mb_id,\n        m.mb_name AS admin_name,\n        (\n            SELECT COUNT(*)\n              FROM mz_company b\n             WHERE b.parent_company_id = c.company_id\n               AND b.company_type = 'BRANCH'\n        ) AS branch_count\n    {$sql_common}\n    ORDER BY c.company_id DESC\n    LIMIT {$from_record}, {$rows}\n");

$g5['title'] = '제휴사 목록';
include_once __DIR__ . '/_admin_head.php';
?>
<style>
.top{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:20px;} .top h1{margin:0;font-size:28px;}
.box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.03);} .search-form{margin-bottom:18px;display:flex;gap:8px;}
.search-form input{width:320px;height:42px;padding:0 12px;border:1px solid #d1d5db;border-radius:8px;} .search-form button{height:42px;padding:0 16px;border:0;border-radius:8px;background:#111827;color:#fff;cursor:pointer;}
.btn{display:inline-block;height:42px;line-height:42px;padding:0 16px;border-radius:8px;text-decoration:none;font-size:14px;} .btn-primary{background:#111827;color:#fff;}
.count{margin-bottom:12px;color:#666;font-size:14px;} .table{width:100%;border-collapse:collapse;} .table th,.table td{padding:14px 10px;border-top:1px solid #f0f0f0;text-align:left;font-size:14px;} .table th{background:#fafafa;}
.badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;} .badge-on{background:#dcfce7;color:#166534;} .badge-off{background:#fee2e2;color:#991b1b;} .empty{padding:30px 10px;text-align:center;color:#777;} .paging{margin-top:20px;text-align:center;}
.paging a,.paging strong{display:inline-block;min-width:36px;height:36px;line-height:36px;margin:0 3px;padding:0 8px;border-radius:8px;text-decoration:none;font-size:14px;} .paging a{background:#f3f4f6;color:#111827;} .paging strong{background:#111827;color:#fff;}
.action-links a{display:inline-block;margin-right:10px;color:#2563eb;text-decoration:none;} .action-links a:last-child{margin-right:0;}
</style>
<div class="top"><h1>제휴사 목록</h1><div><a href="./company_form.php" class="btn btn-primary">제휴사 등록</a></div></div>
<div class="box">
    <form method="get" class="search-form"><input type="text" name="stx" value="<?php echo get_text($stx); ?>" placeholder="제휴사명, 제휴사코드, 관리자아이디, 관리자명"><button type="submit">검색</button></form>
    <div class="count">총 <?php echo number_format($total_count); ?>건</div>
    <table class="table">
        <thead>
            <tr>
                <th width="40">번호</th>
                <th>제휴사명</th>
                <th width="120">제휴사코드</th>
                <th width="60">하위지점</th>
                <th width="120">관리자아이디</th>
                <th width="120">관리자명</th>
                <th width="100">상태</th>
                <th width="150">등록일</th>
                <th width="150">관리</th>
            </tr>
        </thead>
        <tbody>
        <?php $number = $total_count - $from_record; $i = 0; while ($row = sql_fetch_array($result)) { $i++; $branch_count = (int)($row['branch_count'] ?? 0); ?>
            <tr>
                <td><?php echo $number; ?></td>
                <td><?php echo get_text($row['company_name']); ?></td>
                <td><?php echo get_text($row['company_code']); ?></td>
                <td><?php echo number_format($branch_count); ?>개</td>
                <td><?php echo get_text($row['admin_mb_id']); ?></td>
                <td><?php echo get_text($row['admin_name']); ?></td>
                <td><?php if ((string)$row['status'] === '1') { ?><span class="badge badge-on">사용</span><?php } else { ?><span class="badge badge-off">중지</span><?php } ?></td>
                <td><?php echo $row['created_at']; ?></td>
                <td class="action-links">
                    <a class="btn" href="./company_form.php?company_id=<?php echo (int)$row['company_id']; ?>">수정</a>
                    <a href="./branch_list.php?contract_company_id=<?php echo (int)$row['company_id']; ?>">지점목록</a>
                </td>
            </tr>
        <?php $number--; } if ($i === 0) echo '<tr><td colspan="9" class="empty">등록된 제휴사가 없습니다.</td></tr>'; ?>
        </tbody>
    </table>
    <div class="paging"><?php $qstr = ($stx !== '') ? '&stx=' . urlencode($stx) : ''; for ($p = 1; $p <= $total_page; $p++) { if ($p == $page) echo '<strong>'.$p.'</strong>'; else echo '<a href="./company_list.php?page='.$p.$qstr.'">'.$p.'</a>'; } ?></div>
</div>
</div></body></html>
