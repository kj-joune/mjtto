<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-04-13 14:45:00 KST  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();
if (!in_array($auth['role'], array('SUPER_ADMIN', 'COMPANY_ADMIN'), true)) {
    alert('제휴사 관리자만 접근할 수 있습니다.', './index.php');
}

$contract_company_id = 0;
$contract_company_name = '';
$contract_company_code = '';
$is_super_admin_view = ($auth['role'] === 'SUPER_ADMIN');
$has_prize_default_column = mjtto_company_column_exists('is_prize_issue_default');
$select_prize_default_sql = $has_prize_default_column ? ",\n        c.is_prize_issue_default" : "";

if ($is_super_admin_view) {
    $contract_company_id = isset($_GET['contract_company_id']) ? (int)$_GET['contract_company_id'] : 0;
    if ($contract_company_id < 1) {
        alert('제휴사를 먼저 선택하세요.', './company_list.php');
    }

    $contract = sql_fetch("\n        SELECT company_id, company_name, company_code\n          FROM mz_company\n         WHERE company_id = '{$contract_company_id}'\n           AND company_type = 'CONTRACT'\n         LIMIT 1\n    ");

    if (!$contract || empty($contract['company_id'])) {
        alert('존재하지 않는 제휴사입니다.', './company_list.php');
    }

    $contract_company_name = $contract['company_name'];
    $contract_company_code = $contract['company_code'];
} else {
    $contract_company_id = (int)$auth['company_id'];
    $contract_company_name = $auth['company_name'];
    $contract_company_code = $auth['company_code'];
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$rows = 20;
$stx  = trim((string)($_GET['stx'] ?? ''));
$from_record = ($page - 1) * $rows;

$sql_search = "\n    WHERE c.parent_company_id = '{$contract_company_id}'\n      AND c.company_type = 'BRANCH'\n";
if ($stx !== '') {
    $stx_sql = sql_real_escape_string($stx);
    $sql_search .= "\n      AND (\n          c.company_name LIKE '%{$stx_sql}%'\n          OR c.company_code LIKE '%{$stx_sql}%'\n          OR c.coupon_prefix LIKE '%{$stx_sql}%'\n          OR m.mb_id LIKE '%{$stx_sql}%'\n          OR m.mb_name LIKE '%{$stx_sql}%'\n      )\n    ";
}

$sql_common = "\n    FROM mz_company c\n    LEFT JOIN mz_company_user cu\n      ON c.company_id = cu.company_id\n     AND cu.role_code = 'BRANCH_ADMIN'\n     AND cu.status = 1\n    LEFT JOIN g5_member m\n      ON cu.mb_id = m.mb_id\n    {$sql_search}\n";

$total_row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common}");
$total_count = (int)($total_row['cnt'] ?? 0);
$total_page = max(1, (int)ceil($total_count / $rows));

$result = sql_query("\n    SELECT\n        c.company_id,\n        c.company_name,\n        c.company_code,\n        c.coupon_prefix,\n        c.print_name_1,\n        c.print_name_2,\n        c.tel_no,\n        c.issue_game_count{$select_prize_default_sql},\n        c.status,\n        c.created_at,\n        cu.mb_id AS admin_mb_id,\n        m.mb_name AS admin_name\n    {$sql_common}\n    ORDER BY c.company_id DESC\n    LIMIT {$from_record}, {$rows}\n");

$list_href = $is_super_admin_view ? './company_list.php' : './branch_list.php';
$register_href = $is_super_admin_view ? './branch_form.php?parent_company_id=' . $contract_company_id : './branch_form.php';
$page_qstr = ($stx !== '') ? '&stx=' . urlencode($stx) : '';
if ($is_super_admin_view) {
    $page_qstr .= '&contract_company_id=' . $contract_company_id;
}

$g5['title'] = '지점 목록';
include_once __DIR__ . '/_admin_head.php';
?>
<style>
.top{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:20px;} .top h1{margin:0;font-size:28px;} .top p{margin:6px 0 0;color:#666;}
.box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.03);} .search-form{margin-bottom:18px;display:flex;gap:8px;}
.search-form input{width:320px;height:42px;padding:0 12px;border:1px solid #d1d5db;border-radius:8px;} .search-form button{height:42px;padding:0 16px;border:0;border-radius:8px;background:#111827;color:#fff;cursor:pointer;}
.btn{display:inline-block;height:42px;line-height:42px;padding:0 16px;border-radius:8px;text-decoration:none;font-size:14px;} .btn-primary{background:#111827;color:#fff;} .btn-light{background:#e5e7eb;color:#111827;}
.count{margin-bottom:12px;color:#666;font-size:14px;} .table{width:100%;border-collapse:collapse;} .table th,.table td{padding:14px 10px;border-top:1px solid #f0f0f0;text-align:left;font-size:14px;} .table th{background:#fafafa;}
.badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;} .badge-on{background:#dcfce7;color:#166534;} .badge-off{background:#fee2e2;color:#991b1b;} .empty{padding:30px 10px;text-align:center;color:#777;} .paging{margin-top:20px;text-align:center;}
.paging a,.paging strong{display:inline-block;min-width:36px;height:36px;line-height:36px;margin:0 3px;padding:0 8px;border-radius:8px;text-decoration:none;font-size:14px;} .paging a{background:#f3f4f6;color:#111827;} .paging strong{background:#111827;color:#fff;} .edit-link{color:#2563eb;text-decoration:none;}
</style>
<div class="top">
    <div>
        <h1>지점 목록</h1>
        <p><?php echo get_text($contract_company_name); ?> 소속 지점 관리</p>
    </div>
    <div>
        <a href="<?php echo $list_href; ?>" class="btn btn-light">목록</a>
        <a href="<?php echo $register_href; ?>" class="btn btn-primary">지점 등록</a>
    </div>
</div>
<div class="box">
    <form method="get" class="search-form">
        <?php if ($is_super_admin_view) { ?><input type="hidden" name="contract_company_id" value="<?php echo $contract_company_id; ?>"><?php } ?>
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" placeholder="지점명, 업체코드, 구분자, 관리자아이디, 관리자명"><button type="submit">검색</button>
    </form>
    <div class="count">총 <?php echo number_format($total_count); ?>건</div>
    <table class="table">
		<thead>
			<tr>
				<th width="40">번호</th>
				<th>지점명</th>
				<th width="130">복권표기명</th>
				<th width="80">장당게임수</th>
				<th width="80">지점코드</th>
				<th width="80">구분자</th>
				<?php if ($has_prize_default_column) { ?><th width="70">기본발권</th><?php } ?>
				<th width="100">관리자아이디</th>
				<th width="100">관리자명</th>
				<th width="80">상태</th>
				<th width="120">등록일</th>
				<th width="80">관리</th>
			</tr>
		</thead>
		<tbody>
		<?php $number = $total_count - $from_record; $i = 0; while ($row = sql_fetch_array($result)) { $i++; $edit_href = './branch_form.php?company_id='.(int)$row['company_id']; if ($is_super_admin_view) $edit_href .= '&parent_company_id='.$contract_company_id; ?>
        <tr>
			<td><?php echo $number; ?></td>
			<td><?php echo nl2br(get_text($row['company_name'])); ?></td>
			<td><?php echo get_text(trim($row['print_name_1'])); ?><?php if (trim($row['print_name_2']) !== '') echo '<br>'.get_text(trim($row['print_name_2'])); ?></td>
			<td><?php echo (int)$row['issue_game_count']; ?>게임</td>
			<td><?php echo get_text($row['company_code']); ?></td>
			<td><?php echo get_text($row['coupon_prefix']); ?></td>
		<?php if ($has_prize_default_column) { ?>
			<td>
				<?php if ((int)($row['is_prize_issue_default'] ?? 0) === 1) { ?>
					<span class="badge badge-on">기본지점</span>
				<?php } else { ?>
					<span class="badge" style="background:#f3f4f6;color:#6b7280;">-</span>
				<?php } ?>
			</td>
		<?php } ?>
			<td><?php echo get_text($row['admin_mb_id']); ?></td>
			<td><?php echo get_text($row['admin_name']); ?></td>
			<td><?php if ((string)$row['status'] === '1') { ?><span class="badge badge-on">사용</span><?php } else { ?><span class="badge badge-off">중지</span><?php } ?></td><td><?php echo $row['created_at']; ?></td><td><a href="<?php echo $edit_href; ?>" class="edit-link">수정</a></td>
		</tr>
    <?php $number--; } if ($i === 0) echo '<tr><td colspan="'.($has_prize_default_column ? '12' : '11').'" class="empty">등록된 지점이 없습니다.</td></tr>'; ?>
    </tbody></table>
    <div class="paging"><?php for ($p = 1; $p <= $total_page; $p++) { if ($p == $page) echo '<strong>'.$p.'</strong>'; else echo '<a href="./branch_list.php?page='.$p.$page_qstr.'">'.$p.'</a>'; } ?></div>
</div>
</div></body></html>
