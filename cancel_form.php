<?php
include_once "config.php";
  header('Content-Type: text/html; charset=utf-8');
  date_default_timezone_set('Asia/Seoul');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>S'Pay 취소 페이지</title>
<style type="text/css">
    body                    {font-family:굴림; font-size:10pt; color:#000000; text-decoration:none;}
    font                    {font-family:굴림; font-size:10pt; color:#000000;  text-decoration:none;}
    td                      {font-family:굴림; font-size:10pt; color:#000000;  text-decoration:none; padding:1px;}
    table                   {width:100%; border-collapse:collapse;}
    .left                   {min-width:150px; text-align:right;}
    .right                  {padding-left:10px;}
    .right span             {font-size:9pt; color:red;padding: 0px 2px;}
    input[type='text']      {width:350px; line-height:20px;}
    select                  {width:358px; height:25px; line-height:20px;}
    form                    {margin:0;}
    .wrapper                {margin-top:20px; overflow:hidden;}
    .menu                   {float:left;}
    .contents               {float:left; margin-left:10px;}
    .tab                    {background-color:#f1f1f1;padding:10px 20px;border:1px solid #e1e1e1; font-weight: bold; font-size:1.1em;}
    .menuBtn                {text-align:center; line-height:30px; background-color:#fff; font-family:굴림; font-size:10pt; border-bottom:1px solid #ccc; height:30px; width:200px; margin:3px 0px; transition:0.3s; cursor:pointer; display:block;}
    .menuBtn:hover          {background-color:#ddd;}
    .cnclBtn                {background-color:#fff; border-radius:8px; border:1px solid #ccc; height:40px; width:300px; margin:3px 0px; transition:0.3s; cursor:pointer;margin-right:50px;}
    .cnclBtn:hover          {background-color:#ddd;}
</style>   
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script type="text/javascript">
//필수 파라미터
var MANDATORY_PARAMS = ['crcCd','orgTrdNo','cnclAmt','cnclOrd','vAcntNo','refundBankCd','refundAcntNo','refundDpstrNm'];
var REQ_PARAMS = new Object();

//포인트/티머니/상품권류 요청파라미터
REQ_PARAMS.common = {
    orgTrdNo : '세틀뱅크 원거래번호',
    crcCd : '통화구분',
    cnclAmt : '취소거래금액',
    cnclOrd : '취소회차',
    cnclRsn : '취소사유내용'
};
//신용카드 요청파라미터
REQ_PARAMS.card = {
    orgTrdNo : '세틀뱅크 원거래번호',
    crcCd : '통화구분',
    cnclAmt : '취소거래금액',
    cnclOrd : '취소회차',
    cnclRsn : '취소사유내용',
    taxTypeCd : '세금유형',
    taxAmt : '과세금액',
    vatAmt : '부가세금액',
    taxFreeAmt : '비과세금액',
    svcAmt : '봉사료'
};
//휴대폰/계좌이체 요청파라미터
REQ_PARAMS.commonTax = {
    orgTrdNo : '세틀뱅크 원거래번호',
    crcCd : '통화구분',
    cnclAmt : '취소거래금액',
    cnclOrd : '취소회차',
    cnclRsn : '취소사유내용',
    taxTypeCd : '세금유형',
    taxAmt : '과세금액',
    vatAmt : '부가세금액',
    taxFreeAmt : '비과세금액'
};
//가상계좌/010가상계좌 채번취소 요청파라미터
REQ_PARAMS.vbankCancel = {
    orgTrdNo : '세틀뱅크 원거래번호',
    vAcntNo : '가상계좌번호'
};

//가상계좌/010가상계좌/휴대폰결제 환불 요청파라미터
REQ_PARAMS.refund = {
    orgTrdNo : '세틀뱅크 원거래번호',
    crcCd : '통화구분',
    cnclAmt : '환불거래금액',
    cnclOrd : '취소회차',
    refundBankCd : '환불 은행코드',
    refundAcntNo : '환불 계좌번호',
    refundDpstrNm : '환불계좌 예금주명',
    cnclRsn : '환불사유내용',
    taxTypeCd : '세금유형',
    taxAmt : '과세금액',
    vatAmt : '부가세금액',
    taxFreeAmt : '비과세금액'
};

//간편결제 요청파라미터
REQ_PARAMS.corp = {
    orgTrdNo : '세틀뱅크 원거래번호',
    crcCd : '통화구분',
    cnclAmt : '취소거래금액',
    cnclOrd : '취소회차',
    cnclRsn : '취소사유내용',
    taxTypeCd : '세금유형',
    taxAmt : '과세금액',
    vatAmt : '부가세금액',
    taxFreeAmt : '비과세금액',
    svcAmt : '봉사료'
};

//날짜 및 결제수단 등 재설정. 결제수단에 따른 FORM양식 변경
function init(type){
    var curr_date = new Date();
    var year = curr_date.getFullYear().toString();
    var month = ("0" + (curr_date.getMonth() + 1)).slice(-2).toString();
    var day = ("0" + (curr_date.getDate())).slice(-2).toString();
    var hours = ("0" + curr_date.getHours()).slice(-2).toString();
    var mins = ("0" + curr_date.getMinutes()).slice(-2).toString();
    var secs = ("0" + curr_date.getSeconds()).slice(-2).toString();
    var random4 = ("000" + Math.random() * 10000 ).slice(-4).toString();
    var formType = '';

    $('#STPG_cnclForm [name="trdDt"]').val(year + month + day); //취소요청일자 세팅
    $('#STPG_cnclForm [name="trdTm"]').val(hours + mins + secs);//취소요청시간 세팅
    $('#STPG_cnclForm [name="mchtTrdNo"]').val("CANCEL" + year + month + day + hours + mins + secs + random4);//취소주문번호 세팅

    /***********************************************************************************************************************
     * 
     *      결제수단(method) 파라미터 설명
     * 신용카드[CA], 계좌이체[RA], 가상계좌[VA], 휴대폰[MP], 
     * 틴캐시[TC], 해피머니[HM], 문화상품권[CG], 스마트문상[SG], 
     * 도서상품권[BG], 티머니[TM], KT클립포인트[CP], 간편결제[PZ]
     * 
     *      업무구분(bizType) 파라미터 설명
     * 일반취소/가상계좌환불/010가상계좌환불[C0],
     * 휴대폰 결제 환불[C1]
     * 가상계좌/010가상계좌 채번취소[A2]
     * 
     ***********************************************************************************************************************/
    if( 'card' === type ){
        $('#STPG_cnclForm [name="method"]').val('CA');
        $('#STPG_cnclForm [name="bizType"]').val('C0');
        formType = 'card';
    }else if('bank' === type){
        $('#STPG_cnclForm [name="method"]').val('RA');
        $('#STPG_cnclForm [name="bizType"]').val('C0');
        formType = 'commonTax';
    }else if('vbankCancel' === type){
        $('#STPG_cnclForm [name="method"]').val('VA');
        $('#STPG_cnclForm [name="bizType"]').val('A2');
        formType = 'vbankCancel';
    }else if('vbankRefund' === type){
        $('#STPG_cnclForm [name="method"]').val('VA');
        $('#STPG_cnclForm [name="bizType"]').val('C0');
        formType = 'refund';
    }else if('mobile' === type){
        $('#STPG_cnclForm [name="method"]').val('MP');
        $('#STPG_cnclForm [name="bizType"]').val('C0');
        formType = 'commonTax';
    }else if('mobileRefund' === type){
        $('#STPG_cnclForm [name="method"]').val('MP');
        $('#STPG_cnclForm [name="bizType"]').val('C1');
        formType = 'refund';
    }else if('teencash' === type){
        $('#STPG_cnclForm [name="method"]').val('TC');
        $('#STPG_cnclForm [name="bizType"]').val('C0');
        formType = 'common';
    }else if('happymoney' === type){
        $('#STPG_cnclForm [name="method"]').val('HM');
        $('#STPG_cnclForm [name="bizType"]').val('C0');
        formType = 'common';
    }else if('culturecash' === type){
        $('#STPG_cnclForm [name="method"]').val('CG');
        $('#STPG_cnclForm [name="bizType"]').val('C0');
        formType = 'common';
    }else if('smartcash' === type){
        $('#STPG_cnclForm [name="method"]').val('SG');
        $('#STPG_cnclForm [name="bizType"]').val('C0');
        formType = 'common';
    }else if('booknlife' === type){
        $('#STPG_cnclForm [name="method"]').val('BG');
        $('#STPG_cnclForm [name="bizType"]').val('C0');
        formType = 'common';
    }else if('tmoney' === type){
        $('#STPG_cnclForm [name="method"]').val('TM');
        $('#STPG_cnclForm [name="bizType"]').val('C0');
        formType = 'common';
    }else if('point' === type){
        $('#STPG_cnclForm [name="method"]').val('CP');
        $('#STPG_cnclForm [name="bizType"]').val('C0');
        formType = 'common';
    }else if('corp' === type){
        $('#STPG_cnclForm [name="method"]').val('PZ');
        $('#STPG_cnclForm [name="bizType"]').val('C0');
        formType = 'corp';
    }else{
        alert('error');
        return;
    }

    return formType;
}


/** 필수 파라미터 체크 */
function mandatory(param){
    var tmp = param;
    var required = false;
    for(let i=0; i<MANDATORY_PARAMS.length; i++){
        if( param === MANDATORY_PARAMS[i])
            required = true;
    }
    return required;
}

/** 취소 버튼 동작 */
function cancel(){
  $('#STPG_cnclForm').attr("action", "cancel_showResult.php");
  $('#STPG_cnclForm').attr("method", "post");
  $('#STPG_cnclForm').attr("target", "_self");
  $('#STPG_cnclForm').submit();
}

/** 메뉴 이벤트 */
function menuAction(type){
    var html = '<table>';
    var formType = '';
    
    //날짜 및 결제수단 등 재설정. 결제수단에 따른 FORM양식 변경
    formType = init(type);

    for( var k in REQ_PARAMS[formType]){
        html += '<tr>';
        html += '<td class="left">' + REQ_PARAMS[formType][k] + '</td>';
        html += '<td class="right"><input type="text" name="'+ k +'" value="" />' + (mandatory(k)? '<span>* 필수 *</span>' :'') + '</td>';
        html += '</tr>';
    }
    html += '<tr>';
    html += '<td colspan="2" class="right" style="text-align: right;"><input class="cnclBtn" type="button" value="취소 하기" onclick="cancel()"/></td>';
    html += '</tr>';
    html += '</table>';
    
    //요청 바디에 추가
    $('#reqBody').empty().append(html);

    //Default값 셋팅
    $('#STPG_cnclForm [name="crcCd"]'           ).val('KRW');
    $('#STPG_cnclForm [name="cnclAmt"]'         ).val('300');
    $('#STPG_cnclForm [name="cnclOrd"]'         ).val('001');
    $('#STPG_cnclForm [name="orgTrdNo"]'        ).val('STFP_PGVAnx_mid_il0000000000000000000000000');
    $('#STPG_cnclForm [name="vAcntNo"]'         ).val('0123456789');
    $('#STPG_cnclForm [name="refundBankCd"]'    ).val('000');
    $('#STPG_cnclForm [name="refundAcntNo"]'    ).val('0123456789');
    $('#STPG_cnclForm [name="refundDpstrNm"]'   ).val('홍길동');
    $('#STPG_cnclForm [name="cnclRsn"]'         ).val('상품이 마음에 들지 않습니다');
}
</script>
</head>
<body>
<button onclick="location.href='pay_form.php'">결제 샘플로</button>
<button onclick="location.href='cancel_form.php'">취소 샘플로</button>
<h2>결제수단별 취소 API (Non-UI)</h2>
<h4>현재 설정된 상점아이디 >>> <?php echo PG_MID ?></h4>
<hr>
<div class="wrapper">
    <div class="menu">
        <div class="menuBtn" onclick="menuAction('card');">신용카드 취소</div>
        <div class="menuBtn" onclick="menuAction('bank');">계좌이체 취소</div>
        <div class="menuBtn" onclick="menuAction('vbankCancel');">가상계좌/010가상계좌 (채번)취소</div>
        <div class="menuBtn" onclick="menuAction('vbankRefund');">가상계좌/010가상계좌 환불</div>
        <div class="menuBtn" onclick="menuAction('mobile');">휴대폰결제 취소</div>
        <div class="menuBtn" onclick="menuAction('mobileRefund');">휴대폰결제 환불</div>
        <div class="menuBtn" onclick="menuAction('teencash');">틴캐시 취소</div>
        <div class="menuBtn" onclick="menuAction('happymoney');">해피머니 취소</div>
        <div class="menuBtn" onclick="menuAction('culturecash');">문화상품권 취소</div>
        <div class="menuBtn" onclick="menuAction('smartcash');">스마트문상 취소</div>
        <div class="menuBtn" onclick="menuAction('booknlife');">도서상품권 취소</div>
        <div class="menuBtn" onclick="menuAction('tmoney');">티머니 취소</div>
        <div class="menuBtn" onclick="menuAction('point');">KT클립포인트 취소</div>
        <div class="menuBtn" onclick="menuAction('corp');">간편결제 취소</div>
    </div>
    <div class="contents">
    <form id="STPG_cnclForm" name="STPG_cnclForm" >
        <!-- 요청 헤더 -->
        <input type="hidden" name="mchtId" value="<?php echo PG_MID ?>" />  <!-- 상점아이디 -->
        <input type="hidden" name="ver" value="0A19" />                     <!-- 버전(0A**, **:메뉴얼버전) -->
        <input type="hidden" name="method" value="" />                      <!-- 취소할 결제수단 -->
        <input type="hidden" name="bizType" value="" />                     <!-- 업무구분 -->
        <input type="hidden" name="encCd" value="23" />                     <!-- 암호화구분 -->
        <input type="hidden" name="mchtTrdNo" value="" />                   <!-- 상점주문번호(원거래 주문번호가 아닌 취소요청에 대한 상점고유 주문번호) -->
        <input type="hidden" name="trdDt" value="" />                       <!-- 취소요청일자(yy) -->
        <input type="hidden" name="trdTm" value="" />                       <!-- 취소요청시간 -->
        <input type="hidden" name="mobileYn" value="N" />                   <!-- 모바일여부(Y, N) -->
        <input type="hidden" name="osType" value="W" />                     <!-- OS구분(A:Android, I:IOS, W:Windows, M:Mac, E:others)-->

        <!-- 요청 바디 -->
        <div id="reqBody"></div>

    </form>
    </div>
</div>
</body>
</html>