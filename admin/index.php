<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-04-15 04:28:00 KST  */

include_once __DIR__ . '/_admin_common.php';

$auth = mjtto_require_admin();
$g5['title'] = '매주또 관리자 홈';

if (!function_exists('mjtto_sms_payment_text')) {
    function mjtto_sms_payment_text($payment)
    {
        $payment = strtoupper(trim((string)$payment));
        if ($payment === 'C') return '정액제';
        if ($payment === 'A') return '충전제';
        return '미확인';
    }
}

$sms_point_info = mjtto_sms_get_point_info();
$sms_sender_number = mjtto_sms_get_sender_number();
$sms_account_id = trim((string)($config['cf_icode_id'] ?? ''));
$sms_server_host = trim((string)($config['cf_icode_server_ip'] ?? ''));
$contract_prize_options = $auth['role'] === 'SUPER_ADMIN' ? mjtto_get_accessible_contracts($auth) : array();

include_once __DIR__ . '/_admin_head.php';
?>
<style>
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;}
.status-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;margin-bottom:18px;}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.03);} 
.card h2{margin:0 0 10px;font-size:18px;} 
.card p{margin:0 0 16px;color:#666;line-height:1.6;min-height:48px;}
.status-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:20px 22px;box-shadow:0 2px 10px rgba(0,0,0,0.03);}
.status-label{font-size:13px;color:#6b7280;font-weight:300;margin-bottom:8px;}
.btn{display:inline-block;height:35px;line-height:35px;padding:0 16px;border-radius:8px;text-decoration:none;font-size:14px;margin-right:8px;margin-bottom:8px;} 
.btn-primary{background:#111827;color:#fff;} 
.btn-light{background:#e5e7eb;color:#111827;} 
.btn-row{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
.btn-row .btn{margin-right:0;margin-bottom:0;}
.page-title{margin:0 0 24px;font-size:28px;}
.notice{margin-bottom:18px;padding:14px 16px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;color:#4b5563;line-height:1.7;}
.quick-badges{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;}
.quick-badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;line-height:1.2;background:#f3f4f6;color:#374151;}
.quick-badge.ok{background:#ecfdf5;color:#047857;}
.quick-badge.warn{background:#fff7ed;color:#c2410c;}
.quick-badge.fail{background:#fef2f2;color:#b91c1c;}
.inline-form{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
.inline-form select{height:35px;padding:0 12px;border:1px solid #d1d5db;border-radius:8px;min-width:220px;box-sizing:border-box;background:#fff;}
</style>
<h1 class="page-title">관리자 홈</h1>
<div class="notice">
    현재 운영 단계는 <strong>권한별 조회 범위</strong>와 <strong>경품 지급 처리</strong>를 함께 묶는 구조로 정리되어 있습니다.
    지급 이력 테이블 적용 후에는 발권 상세와 지급 목록에서 같은 규칙으로 처리됩니다.
</div>
<div class="status-label">문자 포인트 : <strong><?php echo get_text((string)$sms_point_info['display_point']); ?> </strong>point [<?php echo $sms_sender_number !== '' ? get_text($sms_sender_number) : '미설정'; ?> ]</div>
<div class="grid">
<?php if ($auth['role'] === 'SUPER_ADMIN') { ?>
    <div class="card"><h2>제휴사 관리</h2><p>제휴사 등록, 수정, 관리자 계정 연결을 처리합니다.</p><div class="btn-row"><a href="./company_list.php" class="btn btn-primary">제휴사목록</a><a href="./company_form.php" class="btn btn-light">제휴사등록</a></div></div>
    <div class="card"><h2>전체 발권 현황</h2><p>전체 제휴사·지점 발권 내역과 상태를 조회합니다.</p><div class="btn-row"><a href="./issue_list.php" class="btn btn-primary">발권리스트</a></div></div>
    <div class="card"><h2>전체 경품 지급</h2><p>모든 회차의 지급 요청, 승인, 완료 상태를 통합 관리합니다.</p><div class="btn-row"><a href="./claim_list.php" class="btn btn-primary">지급목록</a><a href="./prize_form.php" class="btn btn-light">기본 경품설정</a></div></div>
    <div class="card"><h2>제휴사별 경품 설정</h2><p>제휴사별 3~5등 경품을 바로 열어 등록·수정합니다.</p><form method="get" action="./prize_form.php" class="inline-form"><select name="company_id" required><option value="">제휴사를 선택하세요</option><?php foreach ($contract_prize_options as $contract_row) { ?><option value="<?php echo (int)$contract_row['company_id']; ?>"><?php echo get_text($contract_row['company_name']); ?></option><?php } ?></select><button type="submit" class="btn btn-light">경품설정 열기</button></form></div>
    <div class="card"><h2>월별 정산</h2><p>월 기준 발권 현황과 지급 요청 현황을 정리합니다.</p><div class="btn-row"><a href="./settlement_month.php" class="btn btn-primary">월정산 보기</a></div></div>
    <div class="card"><h2>운영 보조</h2><p>추첨 데이터 동기화 등 운영 보조 기능입니다.</p><div class="btn-row"><a href="./lotto_sync.php" class="btn btn-light">동행복권 동기화</a></div></div>
<?php } ?>
<?php if ($auth['role'] === 'COMPANY_ADMIN') { ?>
    <div class="card"><h2>지점 관리</h2><p>소속 지점 등록, 수정, 관리자 연결을 처리합니다.</p><div class="btn-row"><a href="./branch_list.php" class="btn btn-primary">지점 목록</a><a href="./branch_form.php" class="btn btn-light">지점 등록</a></div></div>
    <div class="card"><h2>소속 발권 현황</h2><p>소속 지점 전체 발권 현황과 당첨 티켓을 추적합니다.</p><div class="btn-row"><a href="./issue_list.php" class="btn btn-primary">발권리스트</a></div></div>
    <div class="card"><h2>제휴사 경품 지급</h2><p>3~5등 지급 요청을 승인·보류·완료 처리합니다.</p><div class="btn-row"><a href="./claim_list.php" class="btn btn-primary">지급목록</a><a href="./prize_form.php" class="btn btn-light">경품설정</a></div></div>
    <div class="card"><h2>월별 정산</h2><p>소속 지점 발권과 지급 요청 현황을 월 기준으로 확인합니다.</p><div class="btn-row"><a href="./settlement_month.php" class="btn btn-primary">월정산 보기</a></div></div>
<?php } ?>
<?php if ($auth['role'] === 'BRANCH_ADMIN' || $auth['role'] === 'ISSUER') { ?>
    <div class="card"><h2>발권 관리</h2><p>지점 기준 발권 실행, 발권 내역 조회, 당첨 티켓 확인.</p><div class="btn-row"><?php if ($auth['role'] !== 'ISSUER') { ?><a href="./issue_form.php" class="btn btn-primary">발권하기</a><?php } ?><a href="./issue_list.php" class="btn btn-light">발권리스트</a></div></div>
    <?php if ($auth['role'] === 'BRANCH_ADMIN') { ?>
    <div class="card"><h2>지급 요청 / 확인</h2><p>당첨 티켓의 지급 요청 등록과 현재 처리 상태를 확인합니다.</p><div class="btn-row"><a href="./claim_list.php" class="btn btn-primary">지급목록</a></div></div>
    <div class="card"><h2>월별 정산</h2><p>내 지점의 월별 발권 현황과 지급 요청 현황을 확인합니다.</p><div class="btn-row"><a href="./settlement_month.php" class="btn btn-primary">월정산 보기</a></div></div>
    <?php } ?>
<?php } ?>
</div>
</div>
</body>
</html>
