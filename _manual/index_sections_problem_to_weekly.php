<?php
/*  chat-GPT sign: aewha007@gmail.com | datetime: 2026-05-19 23:15:00 KST  */
/* index.php 교체용: Problem 시작부터 Weekly Prizes 끝까지 */

if (!function_exists('mjtto_lotto_ball_class')) {
    function mjtto_lotto_ball_class($number)
    {
        $number = (int)$number;
        if ($number >= 1 && $number <= 10) return 'ball-yellow';
        if ($number >= 11 && $number <= 20) return 'ball-blue';
        if ($number >= 21 && $number <= 30) return 'ball-red';
        if ($number >= 31 && $number <= 40) return 'ball-gray';
        return 'ball-green';
    }
}

$mjtto_draw_result_list = array();
$mjtto_draw_result_sql = "
    SELECT
        `mz_round`.`round_id`,
        `mz_round`.`round_no`,
        `mz_round`.`draw_date`,
        `mz_draw_result`.`win_a`,
        `mz_draw_result`.`win_b`,
        `mz_draw_result`.`win_c`,
        `mz_draw_result`.`win_d`,
        `mz_draw_result`.`win_e`,
        `mz_draw_result`.`win_f`,
        `mz_draw_result`.`bonus_no`
    FROM `mz_draw_result`
    INNER JOIN `mz_round`
        ON `mz_draw_result`.`round_id` = `mz_round`.`round_id`
    WHERE `mz_round`.`draw_date` <= CURDATE()
    ORDER BY `mz_round`.`round_no` DESC
    LIMIT 5
";
$mjtto_draw_result_query = sql_query($mjtto_draw_result_sql);
while ($mjtto_draw_result_row = sql_fetch_array($mjtto_draw_result_query)) {
    $mjtto_draw_result_list[] = $mjtto_draw_result_row;
}
?>

<section class="mjtto-section mjtto-section-light mjtto-origin-problem">
  <div class="mjtto-container">
    <div class="mjtto-origin-section-head" data-mjtto-reveal>
      <div class="mjtto-kicker">Problem</div>
      <h2>왜 많은 기업들이<br>이벤트 마케팅에 <span class="mobile-block">어려움을 겪을까요?</span></h2>
      <p>고객 참여를 유도하는 이벤트는 많지만 지속적인 방문과 신뢰를 동시에 만드는 구조는 쉽지 않습니다.</p>
    </div>
    <div class="mjtto-origin-three-grid">
      <article class="mjtto-origin-card" data-mjtto-reveal>
        <div class="mjtto-origin-card-thumb"><img src="<?php echo G5_THEME_URL; ?>/img/ba662a732c861.png" alt="참여율 문제"></div>
        <h3>참여율 문제</h3>
        <p>이벤트는 많지만 고객 참여는 낮습니다.<br>많은 기업들이 이벤트를 진행하지만<br>고객의 실제 참여율은 기대보다 낮은 경우가 많습니다.</p>
      </article>
      <article class="mjtto-origin-card" data-mjtto-reveal>
        <div class="mjtto-origin-card-thumb"><img src="<?php echo G5_THEME_URL; ?>/img/11628daad2b1b.png" alt="신뢰 문제"></div>
        <h3>신뢰 문제</h3>
        <p>추첨 방식에 대한 신뢰가 부족합니다<br>자체 추첨 방식은 고객에게<br>공정성에 대한 의문을 만들 수 있습니다.</p>
      </article>
      <article class="mjtto-origin-card" data-mjtto-reveal>
        <div class="mjtto-origin-card-thumb"><img src="<?php echo G5_THEME_URL; ?>/img/44f63ccf788f7.png" alt="지속성 문제"></div>
        <h3>지속성 문제</h3>
        <p>이벤트는 일회성으로 끝납니다<br>대부분의 이벤트는<br>한 번 참여 후 고객과의 연결이 끊어집니다.</p>
      </article>
    </div>
  </div>
</section>

<section class="mjtto-section mjtto-origin-sector">
  <div class="mjtto-container">
    <div class="mjtto-origin-section-head" data-mjtto-reveal>
      <div class="mjtto-kicker">Eligible Sectors</div>
      <h2>이런 업종 가능해요!!</h2>
      <p>고객 참여를 유도하는 이벤트로 다양한 업종에서 사용 가능합니다.</p>
    </div>
    <div class="mjtto-origin-wide-image" data-mjtto-reveal><img src="<?php echo G5_THEME_URL; ?>/img/098fb096fef34.png" alt="적용 가능 업종"></div>
    <div class="mjtto-origin-sector-grid">
      <article class="mjtto-origin-sector-card" data-mjtto-reveal><img src="<?php echo G5_THEME_URL; ?>/img/65725168a952e.jpg" alt="식자재마트"><strong>식자재마트 ㅣ 중,대형 마트 사은행사</strong></article>
      <article class="mjtto-origin-sector-card" data-mjtto-reveal><img src="<?php echo G5_THEME_URL; ?>/img/0dac5de9fdf02.jpg" alt="정육점"><strong>정육점 사은행사</strong></article>
      <article class="mjtto-origin-sector-card" data-mjtto-reveal><img src="<?php echo G5_THEME_URL; ?>/img/1ebad4a86bd31.jpg" alt="배달업종"><strong>배달업종 리뷰이벤트 활용</strong></article>
      <article class="mjtto-origin-sector-card" data-mjtto-reveal><img src="<?php echo G5_THEME_URL; ?>/img/0fac55644c399.jpg" alt="보험사 상담"><strong>보험사 ㅣ 사업단 ㅣ 지점 ㅣ 보험설계사 상담시 활용</strong></article>
      <article class="mjtto-origin-sector-card" data-mjtto-reveal><img src="<?php echo G5_THEME_URL; ?>/img/86fa99b030fa3.png" alt="기업 행사"><strong>각종 기업 행사 ㅣ 지역 행사 경품행사로 활용</strong></article>
      <article class="mjtto-origin-sector-card" data-mjtto-reveal><img src="<?php echo G5_THEME_URL; ?>/img/0ed673de4f74a.png" alt="골프장 미용실"><strong>골프장 ㅣ 미용실 등 다양한 업종에 행사로 활용</strong></article>
    </div>
  </div>
</section>

<section class="mjtto-section mjtto-section-soft mjtto-origin-prizes">
  <div class="mjtto-container">
    <div class="mjtto-origin-section-head" data-mjtto-reveal>
      <div class="mjtto-kicker">Prizes</div>
      <h2>특별한 경품 라인업</h2>
      <p>*본 제휴사별 경품이 상이할 수 있습니다</p>
    </div>
    <div class="mjtto-origin-prize-list">
      <?php
      $mjtto_prize_items = array(
          array('f561fddb59d23.png', '1등 상품', '레이/캐스퍼 택 1 선택가능', '고객 참여를 극대화하는 최고 가치 경품', array('약 1,400만원 상당의 고가 경품', '강력한 이벤트 참여 동기 제공', '높은 당첨 만족도', '이벤트 화제성 극대화', '브랜드 신뢰도 상승 효과')),
          array('23d5356fd9940.png', '2등 상품', '프리미엄 가전 패키지', '높은 상품 가치로 참여율 극대화', array('약 500만원 이상의 고가 상품', '가족 모두가 선호하는 가전', '당첨 시 높은 만족도', '2등 상품으로도 충분한 매력', '지속적인 이벤트 참여 동기 제공')),
          array('249ecf3698f72.png', '3등 상품', 'LG 퓨리케어 360 공기청정기', '건강과 웰빙 중심의 인기 상품', array('전 연령층이 선호하는 실용 가전', '일상 생활 건강 관리 필수품', '가족 구성원 모두 사용 가능', '계절 관계없이 활용 가능', '높은 상품 만족도')),
          array('3d5078c2cd9ba.png', '4등 상품', '동남아 3박5일 여행권 (2인)', '경험 중심의 특별한 리워드', array('물질적 상품을 넘어선 경험 가치', '고객 만족도와 행복감 극대화', 'SNS 공유를 통한 자연 홍보', '이벤트 화제성 증가', '브랜드 긍정 이미지 형성')),
          array('535b5c1bd8635.png', '5등 상품', '행운권 1장 추가 제공', '지속적인 고객 참여 구조', array('반복 참여로 충성도 향상', '행사 재방문 유도', '참여 고객 수 증가 효과', '입소문 마케팅 확대', '이벤트 활성화 효과'))
      );
      foreach ($mjtto_prize_items as $mjtto_prize_item) {
      ?>
        <article class="mjtto-origin-prize-card" data-mjtto-reveal>
          <div class="mjtto-origin-prize-image"><img src="<?php echo G5_THEME_URL; ?>/img/<?php echo $mjtto_prize_item[0]; ?>" alt="<?php echo $mjtto_prize_item[1]; ?>"></div>
          <div class="mjtto-origin-prize-text">
            <span><?php echo $mjtto_prize_item[1]; ?></span>
            <h3><?php echo $mjtto_prize_item[2]; ?></h3>
            <p><?php echo $mjtto_prize_item[3]; ?></p>
            <ul><?php foreach ($mjtto_prize_item[4] as $mjtto_prize_text) { ?><li><?php echo $mjtto_prize_text; ?></li><?php } ?></ul>
          </div>
        </article>
      <?php } ?>
    </div>
  </div>
</section>

<section id="mjtto-strength" class="mjtto-section mjtto-origin-features">
  <div class="mjtto-container">
    <div class="mjtto-origin-section-head" data-mjtto-reveal>
      <div class="mjtto-kicker">Key Features</div>
      <h2>매주또만의 3대 핵심 강점</h2>
      <p>신뢰, 반복, 합법 - 세 가지가 완벽하게 갖춰진 리워드 마케팅 솔루션!</p>
    </div>
    <div class="mjtto-origin-feature-grid">
      <article class="mjtto-origin-feature-card" data-mjtto-reveal><span>Trust</span><h3>절대적 공정성</h3><strong>주최 측 개입 불가!</strong><p>✔ 매주 토요일 생중계되는 '동행복권(나눔로또)' 번호와 100% 동기화됩니다.</p><p>✔ 자체 추첨 조작 의혹을 원천 차단하여 고객 신뢰를 극대화합니다.</p></article>
      <article class="mjtto-origin-feature-card" data-mjtto-reveal><span>Loop</span><h3>강력한 루프 형성</h3><strong>고객이 매주 돌아옵니다!</strong><p>✔ 고객은 매주 당첨을 확인하기 위해 모바일 플랫폼에 접속하고, 자연스럽게 제휴사를 재인지합니다.</p><p>✔ 5등 당첨 시스템은 고객이 다시 매장을 방문하거나 상담을 받게 하는 강력한 유입 기폭제가 됩니다.</p></article>
      <article class="mjtto-origin-feature-card" data-mjtto-reveal><span>Legal</span><h3>법적 안정성 확보</h3><strong>완전히 합법적인 구조!</strong><p>✔ '소비자 현장경품' 형태로, 2016년 경품 가액 한도 폐지에 따라 고가 경품 제공에 법적 제한이 없습니다.</p><p>✔ 무상 사은품 제공 방식으로 사행성 유발이나 도박죄에 해당하지 않습니다.</p></article>
    </div>
  </div>
</section>

<section id="mjtto-guide" class="mjtto-section mjtto-origin-join">
  <div class="mjtto-container">
    <div class="mjtto-origin-section-head" data-mjtto-reveal>
      <div class="mjtto-kicker">How To Join</div>
      <h2>매주또 리워드<br>이용안내</h2>
      <p>복잡한 절차 없이 제휴사 상담(방문)을<br class="pc_hidden"> 통하여 이벤트 참여가 가능합니다.</p>
    </div>
    <div class="mjtto-origin-step-grid">
      <article class="mjtto-origin-step-card" data-mjtto-reveal><div class="mjtto-origin-step-icon"><img src="<?php echo G5_THEME_URL; ?>/img/22ec52b856acb.png" alt="제휴사 상담 및 행운권 획득"></div><span>STEP 01</span><h3>제휴사 상담 및 행운권 획득</h3><p>제휴사 방문 및 상담을 통하여 행운권 획득</p></article>
      <article class="mjtto-origin-step-card" data-mjtto-reveal><div class="mjtto-origin-step-icon"><img src="<?php echo G5_THEME_URL; ?>/img/60edbd2682513.png" alt="로또 추첨 확인"></div><span>STEP 02</span><h3>로또 추첨 확인</h3><p>매주 토요일 로또 추첨 확인(공정성 보장)</p></article>
      <article class="mjtto-origin-step-card" data-mjtto-reveal><div class="mjtto-origin-step-icon"><img src="<?php echo G5_THEME_URL; ?>/img/a03cc4e5f535e.png" alt="당첨자 정보등록 및 경품수령"></div><span>STEP 03</span><h3>당첨자 정보등록 및 경품수령</h3><p>당첨 및 경품 수령(QR코드 접수)</p></article>
      <article class="mjtto-origin-step-card" data-mjtto-reveal><div class="mjtto-origin-step-icon"><img src="<?php echo G5_THEME_URL; ?>/img/c661f4546d48b.png" alt="제휴사 연락 방문"></div><span>STEP 04</span><h3>제휴사 연락(방문)</h3><p>고객 재방문 및 시스템 선순환</p></article>
    </div>
  </div>
</section>

<section class="mjtto-section mjtto-origin-draw">
  <div class="mjtto-container">
    <div class="mjtto-origin-section-head" data-mjtto-reveal>
      <div class="mjtto-kicker">Draw Results</div>
      <h2>당첨번호 발표</h2>
      <p>매주 토요일 8시35분 TV방송<br>로또번호 동일하게 확인 가능합니다.</p>
    </div>
    <?php if (!empty($mjtto_draw_result_list)) { ?>
      <div class="mjtto-lotto-slider" data-mjtto-lotto-slider data-mjtto-reveal>
        <button type="button" class="mjtto-lotto-arrow mjtto-lotto-arrow-prev" data-mjtto-lotto-prev aria-label="이전 회차 보기">‹</button>
        <div class="mjtto-lotto-slider-viewport">
          <?php foreach ($mjtto_draw_result_list as $mjtto_draw_result_index => $mjtto_draw_result_row) { ?>
            <?php
            $mjtto_draw_numbers = array((int)$mjtto_draw_result_row['win_a'], (int)$mjtto_draw_result_row['win_b'], (int)$mjtto_draw_result_row['win_c'], (int)$mjtto_draw_result_row['win_d'], (int)$mjtto_draw_result_row['win_e'], (int)$mjtto_draw_result_row['win_f']);
            $mjtto_bonus_number = (int)$mjtto_draw_result_row['bonus_no'];
            $mjtto_is_active = ($mjtto_draw_result_index === 0) ? ' is-active' : '';
            ?>
            <div class="mjtto-lotto-result-card<?php echo $mjtto_is_active; ?>" data-mjtto-lotto-card>
              <img class="mjtto-lotto-logo" src="<?php echo G5_THEME_URL; ?>/img/main_logo.png" alt="매주또">
              <strong class="mjtto-lotto-round"><?php echo (int)$mjtto_draw_result_row['round_no']; ?>회</strong>
              <span class="mjtto-lotto-date"><?php echo date('Y.m.d', strtotime($mjtto_draw_result_row['draw_date'])); ?> 추첨</span>
              <div class="mjtto-lotto-balls">
                <?php foreach ($mjtto_draw_numbers as $mjtto_draw_number) { ?><span class="ball <?php echo mjtto_lotto_ball_class($mjtto_draw_number); ?>"><?php echo $mjtto_draw_number; ?></span><?php } ?>
                <em>+</em>
                <span class="ball <?php echo mjtto_lotto_ball_class($mjtto_bonus_number); ?>"><?php echo $mjtto_bonus_number; ?></span>
              </div>
              <p class="mjtto-lotto-source">※ 당첨번호 데이터 출처: 매주또 동기화 DB</p>
            </div>
          <?php } ?>
        </div>
        <button type="button" class="mjtto-lotto-arrow mjtto-lotto-arrow-next" data-mjtto-lotto-next aria-label="다음 회차 보기">›</button>
        <div class="mjtto-lotto-slider-count"><span data-mjtto-lotto-current>1</span> / <span><?php echo count($mjtto_draw_result_list); ?></span></div>
      </div>
    <?php } else { ?>
      <div class="mjtto-lotto-result-card" data-mjtto-reveal><img class="mjtto-lotto-logo" src="<?php echo G5_THEME_URL; ?>/img/main_logo.png" alt="매주또"><strong class="mjtto-lotto-round">당첨번호 준비중</strong><p class="mjtto-lotto-source">아직 동기화된 당첨번호가 없습니다.</p></div>
    <?php } ?>
  </div>
</section>

<section class="mjtto-section mjtto-origin-weekly">
  <div class="mjtto-container">
    <div class="mjtto-origin-section-head mjtto-weekly-head" data-mjtto-reveal>
      <div class="mjtto-kicker">Weekly Prizes</div>
      <h2>함께하는<br class="pc_hidden"> 제휴사</h2>
    </div>
    <div class="mjtto-origin-partner-grid">
      <?php
      $mjtto_partner_images = array('1c3bc525496a2.png','872424cdef107.png','4de5e615c2487.png','6af8f7006f179.png','94cf8078dbf39.png','56492f37996ac.png','342797a5ff890.png','7d0279b5d5a7a.jpg','cb6788a6a2d6b.png','9844fd32a6ca2.png','6dc4b5663305e.png','4389fcf6e9dec.png','22009f0f8dd28.png','8452c53de646b.png');
      foreach ($mjtto_partner_images as $mjtto_partner_image) {
      ?>
        <div class="mjtto-origin-partner-item"><img src="<?php echo G5_THEME_URL; ?>/img/<?php echo $mjtto_partner_image; ?>" alt="함께하는 제휴사"></div>
      <?php } ?>
    </div>
  </div>
</section>
