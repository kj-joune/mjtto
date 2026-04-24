<?php
if (!defined('_GNUBOARD_')) exit;

/*
 * sysk.sms.lib.php
 * UpdatedAt: 2026-03-07  (Asia/Seoul)
 *
 * 목적:
 * - icode_sms_lib.php의 사용 패턴을 그대로 두고, include만 교체하면
 *   SYSK SMS API로 발송되도록 하는 드롭인 대체 라이브러리
 *
 * 설정 매핑 (g5_config 그대로 사용)
 * - cf_icode_id         => SYSK user
 * - cf_icode_pw         => SYSK pass
 * - cf_icode_token_key  => SYSK domain
 * - cf_icode_server_ip  => SYSK host (예: mail.ksvc.co.kr)
 * - cf_icode_server_port=> timeout(초)로 사용 (없으면 5)
 *
 * API:
 * - https://{host}/SMS/api/send.php  (POST)
 */

///////////////////////////////////////////////////////////////////////////////////////////
// icode_sms_lib.php 에 있던 유틸을 최소한으로 유지(호출부 호환용)

function spacing($text, $size) {
    for ($i=0; $i<$size; $i++) $text.=" ";
    $text = substr($text,0,$size);
    return $text;
}

function cut_char($word, $cut) {
    $word = substr($word,0,$cut);
    for ($k=$cut-1; $k>1; $k--) {
        if (ord(substr($word,$k,1))<128) break;
    }
    $word = substr($word,0,$cut-($cut-$k+1)%2);
    return $word;
}

function CheckCommonType($dest, $rsvTime) {
    $dest=preg_replace("/[^0-9]/i","",$dest);
    if (strlen($dest)<10 || strlen($dest)>11) return "휴대폰 번호가 틀렸습니다";
    $CID=substr($dest,0,3);
    if ( preg_match("/[^0-9]/i",$CID) || ($CID!='010' && $CID!='011' && $CID!='016' && $CID!='017' && $CID!='018' && $CID!='019') ) return "휴대폰 앞자리 번호가 잘못되었습니다";
    $rsvTime=preg_replace("/[^0-9]/i","",$rsvTime);
    if ($rsvTime) {
        if (!checkdate(substr($rsvTime,4,2),substr($rsvTime,6,2),substr($rsvTime,0,4))) return "예약날짜가 잘못되었습니다";
        if (substr($rsvTime,8,2)>23 || substr($rsvTime,10,2)>59) return "예약시간이 잘못되었습니다";
    }
    return "";
}

///////////////////////////////////////////////////////////////////////////////////////////
// SYSK transport (PHP 5.2 compat)

function sysk_sms_api_base() {
    global $config;
    $host = isset($config['cf_icode_server_ip']) ? trim($config['cf_icode_server_ip']) : '';
    if ($host === '') return '';
    if (strpos($host, 'http://') === 0 || strpos($host, 'https://') === 0) {
        // 혹시 전체 URL로 넣는 사이트가 있으면 그대로 사용
        return rtrim($host, '/') . '/SMS/api';
    }
    return 'https://' . $host . '/SMS/api';
}

function sysk_sms_timeout() {
    global $config;
    $t = isset($config['cf_icode_server_port']) ? (int)$config['cf_icode_server_port'] : 0;
    if ($t <= 0) $t = 5;
    return $t;
}

function sysk_sms_cfg(&$user, &$pass, &$domain) {
    global $config;
    $raw    = isset($config['cf_icode_id']) ? trim($config['cf_icode_id']) : '';
    $pass   = isset($config['cf_icode_pw']) ? trim($config['cf_icode_pw']) : '';
    $user   = $raw;
    $domain = '';

    $pos = strpos($raw, '@');
    if ($pos !== false) {
        $user   = substr($raw, 0, $pos);
        $domain = substr($raw, $pos + 1);
    }

    return ($user !== '' && $pass !== '' && $domain !== '');
}

function sysk_sms_post($url, $fields, $timeout_sec) {
    $post = http_build_query($fields, '', '&');

    // curl 우선
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout_sec);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout_sec);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array($code, $resp, $err);
    }

    // file_get_contents fallback
    $ctx = stream_context_create(array(
        'http' => array(
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n".
                         "Connection: close\r\n",
            'content' => $post,
            'timeout' => $timeout_sec,
        ),
        'ssl' => array(
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ),
    ));

    $resp = @file_get_contents($url, false, $ctx);
    // HTTP 코드 파싱(가능하면)
    $code = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                $code = (int)$m[1];
                break;
            }
        }
    }
    return array($code, $resp, '');
}

function sysk_sms_to_euckr($s) {
    $s = (string)$s;
    // 메시지가 이미 EUC-KR이면 그대로, UTF-8이면 변환
    if (function_exists('mb_detect_encoding')) {
        $enc = @mb_detect_encoding($s, array('EUC-KR','UTF-8'), true);
        if ($enc === 'UTF-8') {
            $c = @iconv('UTF-8', 'EUC-KR//IGNORE', $s);
            if ($c !== false) return $c;
        }
        return $s;
    }
    // mbstring 없으면 iconv 시도
    $c = @iconv('UTF-8', 'EUC-KR//IGNORE', $s);
    return ($c === false) ? $s : $c;
}

function sysk_sms_send_one($to, $from, $msg, $title, $is_lms, $rsvTime="") {
    $to   = preg_replace('/[^0-9]/', '', (string)$to);
    $from = preg_replace('/[^0-9]/', '', (string)$from);

    $base = sysk_sms_api_base();
    if ($base === '') return array(false, 'NO_BASE');

    $ok = sysk_sms_cfg($user, $pass, $domain);
    if (!$ok) return array(false, 'NO_CFG');

    $timeout = sysk_sms_timeout();
    $url = rtrim($base, '/') . '/send.php';

    // SYSK쪽이 EUC-KR을 기대하는 구성(고도몰과 동일)
    $msg = sysk_sms_to_euckr($msg);
    $title = sysk_sms_to_euckr($title);

    $fields = array(
        'user'   => $user,
        'domain' => $domain,
        'pass'   => $pass,
        'to'     => $to,
        'from'   => $from,
        'msg'    => $msg,
    );

    // LMS면 title 전달 (서버가 무시하더라도 안전)
    if ($is_lms && $title !== '') $fields['title'] = $title;

    // 예약은 필요하면 확장(현재는 즉시발송 기준)
    // if ($rsvTime) $fields['date'] = $rsvTime;

    list($code, $resp, $err) = sysk_sms_post($url, $fields, $timeout);

    if ($code < 200 || $code >= 300) {
        return array(false, 'HTTP_'.$code);
    }
    $resp = trim((string)$resp);

    // 응답이 JSON이면 ok=1 체크
    $j = @json_decode($resp, true);
    if (is_array($j) && isset($j['ok'])) {
        if ((int)$j['ok'] === 1) return array(true, 'OK');
        return array(false, 'API_FAIL');
    }

    // JSON이 아니면 보수적으로 실패 처리
    return array(false, 'BAD_RESP');
}

///////////////////////////////////////////////////////////////////////////////////////////
// icode와 동일한 클래스/메서드 시그니처 제공

class SMS {
    public $ID;
    public $PWD;
    public $SMS_Server;
    public $port;
    public $SMS_Port;

    public $Data = array();
    public $Result = array();

    function SMS_con($sms_server, $sms_id, $sms_pw, $port) {
        // icode와 동일한 인터페이스 유지 (인자는 받되 실제 설정은 $config 사용)
        $this->ID = $sms_id;
        $this->PWD = $sms_pw;
        $this->SMS_Server = $sms_server;
        $this->SMS_Port = $port;
    }

    function Init() {
        $this->Data = array();
        $this->Result = array();
    }

    function Add($dest, $callBack, $Caller, $msg, $rsvTime="") {
        // 내용 검사(기존과 동일 수준)
        $Error = CheckCommonType($dest, $rsvTime);
        if ($Error) return $Error;
        if (preg_match("/[^0-9]/i", $callBack)) return "회신 전화번호가 잘못되었습니다";

        // 기존 icode는 80자 컷, 여기서도 동일하게 유지(SMS 기준)
        $msg = cut_char($msg, 80);

        $this->Data[] = array(
            'dest' => $dest,
            'cb'   => $callBack,
            'msg'  => $msg,
            'rsv'  => $rsvTime,
        );
        return "";
    }

    function Send() {
        $this->Result = array();

        foreach ($this->Data as $row) {
            $dest = $row['dest'];
            $cb   = $row['cb'];
            $msg  = $row['msg'];

            // SYSK SMS 전송
            list($ok, $reason) = sysk_sms_send_one($dest, $cb, $msg, '', false, $row['rsv']);

            $d = preg_replace('/[^0-9]/', '', (string)$dest);
            if ($ok) {
                // icode는 결과에 "dest:xxxxx" 형태를 넣었음. 여기선 간단히 OK 표시
                $this->Result[] = $d . ":OK";
            } else {
                $this->Result[$d] = $d . ":Error(" . $reason . ")";
                // 실패 로그는 최소만
                error_log('[SYSK_SMS_FAIL] to='.$d.' reason='.$reason);
            }
        }

        $this->Data = array();
        return true;
    }
}