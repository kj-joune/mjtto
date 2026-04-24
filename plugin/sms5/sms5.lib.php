<?php
if (!defined('_GNUBOARD_')) exit;

/*************************************************************************
**
**  sms5에 사용할 함수 모음
**
*************************************************************************/

// 한페이지에 보여줄 행, 현재페이지, 총페이지수, URL
function sms5_sub_paging($write_pages, $cur_page, $total_page, $url, $add="", $starget="")
{
    if( $starget ){
        $url = preg_replace('#&amp;'.$starget.'=[0-9]*#', '', $url) . '&amp;'.$starget.'=';
    }

    $str = '';
    if ($cur_page > 1) {
        $str .= '<a href="'.$url.'1'.$add.'" class="pg_page pg_start">처음</a>'.PHP_EOL;
    }

    $start_page = ( ( (int)( ($cur_page - 1 ) / $write_pages ) ) * $write_pages ) + 1;
    $end_page = $start_page + $write_pages - 1;

    if ($end_page >= $total_page) $end_page = $total_page;

    if ($start_page > 1) $str .= '<a href="'.$url.($start_page-1).$add.'" class="pg_page pg_prev">이전</a>'.PHP_EOL;

    if ($total_page > 1) {
        for ($k=$start_page;$k<=$end_page;$k++) {
            if ($cur_page != $k)
                $str .= '<a href="'.$url.$k.$add.'" class="pg_page">'.$k.'<span class="sound_only">페이지</span></a>'.PHP_EOL;
            else
                $str .= '<span class="sound_only">열린</span><strong class="pg_current">'.$k.'</strong><span class="sound_only">페이지</span>'.PHP_EOL;
        }
    }

    if ($total_page > $end_page) $str .= '<a href="'.$url.($end_page+1).$add.'" class="pg_page pg_next">다음</a>'.PHP_EOL;

    if ($cur_page < $total_page) {
        $str .= '<a href="'.$url.$total_page.$add.'" class="pg_page pg_end">맨끝</a>'.PHP_EOL;
    }

    if ($str)
        return "<nav class=\"pg_wrap\"><span class=\"pg\">{$str}</span></nav>";
    else
        return "";
}

// php8 버전 호환 권한 검사 함수
function ajax_auth_check_menu($auth, $sub_menu, $attr){
    $check_auth = isset($auth[$sub_menu]) ? $auth[$sub_menu] : '';
    return ajax_auth_check($check_auth, $attr);
}

// 권한 검사
function ajax_auth_check($auth, $attr)
{
    global $is_admin;

    if ($is_admin == 'super') return;

    if (!trim($auth))
        die("{\"error\":\"이 메뉴에는 접근 권한이 없습니다.\\n\\n접근 권한은 최고관리자만 부여할 수 있습니다.\"}");

    $attr = strtolower($attr);

    if (!strstr($auth, $attr)) {
        if ($attr == 'r')
            die("{\"error\":\"읽을 권한이 없습니다.\"}");
        else if ($attr == 'w')
            die("{\"error\":\"입력, 추가, 생성, 수정 권한이 없습니다.\"}");
        else if ($attr == 'd')
            die("{\"error\":\"삭제 권한이 없습니다.\"}");
        else
            die("{\"error\":\"속성이 잘못 되었습니다.\"}");
    }
}

if ( ! function_exists('array_overlap')) {
    function array_overlap($arr, $val) {
        for ($i=0, $m=count($arr); $i<$m; $i++) {
            if ($arr[$i] == $val)
                return true;
        }
        return false;
    }
}
if ( ! function_exists('get_hp')) {
    function get_hp($hp, $hyphen=1)
    {
        global $g5;

        if (!is_hp($hp)) return '';

        if ($hyphen) $preg = "$1-$2-$3"; else $preg = "$1$2$3";

        $hp = str_replace('-', '', trim($hp));
        $hp = preg_replace("/^(01[016789])([0-9]{3,4})([0-9]{4})$/", $preg, $hp);

        if (isset($g5['sms5_demo']) && $g5['sms5_demo'])
            $hp = '0100000000';

        return $hp;
    }
}
if ( ! function_exists('is_hp')) {
    function is_hp($hp)
    {
        $hp = str_replace('-', '', trim($hp));
        if (preg_match("/^(01[016789])([0-9]{3,4})([0-9]{4})$/", $hp))
            return true;
        else
            return false;
    }
}
if ( ! function_exists('alert_just')) {
    // 경고메세지를 경고창으로
    function alert_just($msg='', $url='')
    {
        global $g5;

        if (!$msg) $msg = '올바른 방법으로 이용해 주십시오.';

        //header("Content-Type: text/html; charset=$g5[charset]");
        echo "<meta charset=\"utf-8\">";
        echo "<script language='javascript'>alert('$msg');";
        echo "</script>";
        exit;
    }
}

if ( ! function_exists('utf2euc')) {
    function utf2euc($str) {
        return iconv("UTF-8","cp949//IGNORE", $str);
    }
}
if ( ! function_exists('is_ie')) {
    function is_ie() {
        return isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false);
    }
}

/**
 * SMS 발송을 관장하는 메인 클래스이다.
 *
 * 접속, 발송, URL발송, 결과등의 실질적으로 쓰이는 모든 부분이 포함되어 있다.
 */

if($config['cf_sms_type'] == 'LMS') {
    include_once(G5_LIB_PATH.'/sysk.lms.lib.php');

    class SMS5 extends LMS {
        public $icode_id;
        public $icode_pw;
        public $socket_host;
        public $socket_port;
        public $socket_portcode;
        public $send_type;
        public $Data = array();
        public $Result = array();
        public $Log = array();
		var $icode_key = '';

        function Add($strDest, $strCallBack, $strCaller, $strSubject, $strURL, $strData, $strDate="", $nCount=0) {
            global $config;

            // 시스템 JSON 모듈은 UTF-8 을 사용하며, sms 또는 lms 는 euc-kr 로 사용한다.
            if(! (isset($config['cf_icode_token_key']) && $config['cf_icode_token_key'])){
                // EUC-KR로 변환
                $strCaller  = iconv_euckr($strCaller);
                $strSubject = iconv_euckr($strSubject);
                $strData    = iconv_euckr($strData);
            }

            return parent::Add($strDest, $strCallBack, $strCaller, $strSubject, $strURL, $strData, $strDate, $nCount);
        }

        function Send() {
            global $g5;

            if (isset($g5['sms5_demo_send']) && $g5['sms5_demo_send']) {
                foreach($this->Data as $puts) {
                    if (rand(0,10)) {
                        $phone = substr($puts,26,11);
                        $code = '47022497 ';
                    } else {
                        $phone = substr($puts,26,11);
                        $code = 'Error(02)';
                    }
                    $this->Result[] = "$phone:$code";
                    $this->Log[] = $puts;
                }
                $this->Data = array();
                return true;
                exit;
            }

            return parent::Send();
        }
    }
} else {
    include_once(G5_LIB_PATH.'/sysk.sms.lib.php');

    class SMS5 extends SMS {
        var $Log = array();

        function CheckCommonTypeDest($strDest, $nCount) {
            for ($i=0; $i<$nCount; $i++) {
                $hp_number = preg_replace("/[^0-9]/", "", $strDest[$i]['bk_hp']);
                if (strlen($hp_number)<10 || strlen($hp_number)>11) return "휴대폰 번호가 틀렸습니다";

                $CID = substr($hp_number, 0, 3);
                if (preg_match("/[^0-9]/", $CID) || ($CID!='010' && $CID!='011' && $CID!='016' && $CID!='017' && $CID!='018' && $CID!='019')) return "휴대폰 앞자리 번호가 잘못되었습니다";
            }
            return '';
        }

        function CheckCommonTypeCallBack($strCallBack) {
            if (preg_match("/[^0-9]/", $strCallBack)) return "회신 전화번호가 잘못되었습니다";
            return '';
        }

        function CheckCommonTypeDate($strDate) {
            $strDate = preg_replace("/[^0-9]/", "", $strDate);
            if ($strDate) {
                if (!checkdate(substr($strDate,4,2), substr($strDate,6,2), substr($strDate,0,4))) return "예약날짜가 잘못되었습니다";
                if (substr($strDate,8,2)>23 || substr($strDate,10,2)>59) return "예약시간이 잘못되었습니다";
            }
            return '';
        }

        function Add2($strDest, $strCallBack, $strCaller, $strURL, $strMessage, $strDate="", $nCount=0) {
            $Error = $this->CheckCommonTypeDest($strDest, $nCount);
            if ($Error) return $Error;
            $Error = $this->CheckCommonTypeCallBack($strCallBack);
            if ($Error) return $Error;
            $Error = $this->CheckCommonTypeDate($strDate);
            if ($Error) return $Error;

            for ($i=0; $i<$nCount; $i++) {
                $hp_number = preg_replace('/[^0-9]/', '', $strDest[$i]['bk_hp']);
                $strData = $strMessage;
                if (!empty($strDest[$i]['bk_name'])) {
                    $strData = str_replace('{이름}', $strDest[$i]['bk_name'], $strData);
                }

                $this->Data[] = array(
                    'dest' => $hp_number,
                    'cb'   => preg_replace('/[^0-9]/', '', $strCallBack),
                    'msg'  => cut_char($strData, 80),
                    'rsv'  => $strDate,
                );
            }

            return true;
        }

        function Send() {
            $this->Result = array();
            $this->Log = array();

            foreach ($this->Data as $row) {
                $dest = isset($row['dest']) ? $row['dest'] : '';
                $cb   = isset($row['cb']) ? $row['cb'] : '';
                $msg  = isset($row['msg']) ? $row['msg'] : '';
                $rsv  = isset($row['rsv']) ? $row['rsv'] : '';

                list($ok, $reason) = sysk_sms_send_one($dest, $cb, $msg, '', false, $rsv);

                $d = preg_replace('/[^0-9]/', '', (string)$dest);
                $this->Log[] = 'SYSK|to=' . $d . '|cb=' . $cb . '|result=' . ($ok ? 'OK' : $reason);

                if ($ok) {
                    $this->Result[] = $d . ':OK';
                } else {
                    if ($reason === 'API_FAIL') {
                        $hs = '23';
                    } else if ($reason === 'BAD_RESP' || strpos($reason, 'HTTP_') === 0 || $reason === 'NO_BASE' || $reason === 'NO_CFG') {
                        $hs = '99';
                    } else {
                        $hs = '23';
                    }
                    $this->Result[$d] = $d . ':Error(' . $hs . ')';
                }
            }

            $this->Data = array();
            return true;
        }
    }
}