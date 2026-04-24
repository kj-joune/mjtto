<?php
if (!defined('_GNUBOARD_')) exit;

/*
 * sysk.lms.lib.php
 * UpdatedAt: 2026-03-07 (Asia/Seoul)
 *
 * 목적:
 * - icode_lms_lib.php의 LMS 클래스(LMS) 인터페이스를 유지한 채로
 *   SYSK SMS API로 발송
 */

require_once dirname(__FILE__) . '/sysk.sms.lib.php'; // sysk_sms_send_one 재사용

// icode 쪽에서 호출하는 함수가 있으면 최소한 제공
function get_icode_port_type($id, $pw) {
    // SYSK에서는 포트 타입 의미 없음. 호출부 호환용으로 1 리턴
    return 1;
}

class LMS {
    public $icode_id;
    public $icode_pw;
    public $socket_host;
    public $socket_port;
    public $socket_portcode;

    public $Data = array();
    public $Result = array();

    function SMS_con($host, $id, $pw, $portcode) {
        // 시그니처 유지. 실제 전송은 sysk.sms.lib.php가 $config에서 읽음.
        $this->socket_host = $host;
        $this->socket_portcode = $portcode;
        $this->icode_id = $id;
        $this->icode_pw = $pw;
    }

    function Init() {
        $this->Data = array();
        $this->Result = array();
    }

    // icode와 동일 시그니처 유지
    function Add($strDest, $strCallBack, $strCaller, $strSubject, $strURL, $strData, $strDate="", $nCount=0) {
        // $strDest는 배열일 수 있음 (icode 방식)
        // nCount 만큼 처리
        $this->Data[] = array(
            'dest'    => $strDest,
            'cb'      => $strCallBack,
            'caller'  => $strCaller,
            'subject' => $strSubject,
            'url'     => $strURL,
            'msg'     => $strData,
            'date'    => $strDate,
            'cnt'     => (int)$nCount,
        );
        return true;
    }

    function Send() {
        $this->Result = array();

        foreach ($this->Data as $pack) {
            $cnt = (int)$pack['cnt'];
            $cb  = $pack['cb'];
            $msg = (string)$pack['msg'];
            $sub = (string)$pack['subject'];

            // dest 배열/문자열 모두 지원
            $dests = $pack['dest'];
            if (!is_array($dests)) $dests = array($dests);

            for ($i=0; $i<$cnt; $i++) {
                if (!isset($dests[$i])) continue;
                $dest = $dests[$i];

                // 90byte 기준은 원래 icode 로직이지만, 여기선 제목이 있거나 길면 LMS로 취급
                $is_lms = true;

                list($ok, $reason) = sysk_sms_send_one($dest, $cb, $msg, $sub, $is_lms, $pack['date']);

                $d = preg_replace('/[^0-9]/', '', (string)$dest);
                if ($ok) {
                    $this->Result[] = $d . ":OK";
                } else {
                    $this->Result[$d] = $d . ":Error(" . $reason . ")";
                    error_log('[SYSK_LMS_FAIL] to='.$d.' reason='.$reason);
                }
            }
        }

        $this->Data = array();
        return true;
    }
}