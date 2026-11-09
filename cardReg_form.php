<?php
    include_once "config.php";
    include_once "settleUtils.php";
    header('Content-Type: text/html; charset=utf-8');
    date_default_timezone_set('Asia/Seoul');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>헥토파이낸셜 PG 신용카드 0원 등록(빌키발급) 샘플</title>
<style type="text/css">
    body            {font-family:굴림; font-size:10pt; color:#000000;}
    td              {font-family:굴림; font-size:10pt; padding:1px; border:1px solid #e1e1e1;}
    table           {width:100%; border-collapse:collapse;}
    .left           {padding-left:10px; width:30%;}
    .right          {width:70%;}
    .right span     {font-size:9pt; color:red; padding:0 2px;}
    input[type='text'] {width:350px; margin:5px;}
    form            {margin:0;}
    .wrapper        {width:470px; border:1px solid #e1e1e1; margin-top:20px;}
    .tab            {background-color:#f1f1f1; padding:10px 20px; border:1px solid #e1e1e1; font-weight:bold; font-size:1.1em;}
    .content        {padding:3px;}
    .payBtn         {background-color:#fff; border-radius:8px; border:1px solid #ccc; height:50px; width:99%; margin:3px 0; cursor:pointer;}
    .payBtn:hover   {background-color:#ddd;}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo PAYMENT_SERVER ?>/resources/js/v1/SettlePG_v1.2.js"></script>
<script type="text/javascript">
/**
 * 신용카드 0원 등록(빌키발급) 결제창
 * - 실제 결제(승인) 없이 0원 인증으로 카드를 등록하고 정기결제용 빌키(billKey)를 발급받습니다.
 * - 결제창(UI) 방식이며, 내부적으로 /card/cardAuth.do 를 호출합니다.
 *   (JS SDK: method="card" + methodSub="auth" 조합이 빌키발급창으로 라우팅됩니다.)
 * - 발급받은 billKey 로 2회차 결제는 billkey_pay_form.php(빌키 결제 API)를 사용하세요.
 * - 거래금액(trdAmt)은 0원 인증 방식이므로 "0" 고정입니다.
 */

/** 날짜/주문번호/고객IP 세팅 */
function init(){
    var d = new Date();
    var year  = d.getFullYear().toString();
    var month = ("0" + (d.getMonth() + 1)).slice(-2);
    var day   = ("0" + d.getDate()).slice(-2);
    var hours = ("0" + d.getHours()).slice(-2);
    var mins  = ("0" + d.getMinutes()).slice(-2);
    var secs  = ("0" + d.getSeconds()).slice(-2);
    var rnd4  = ("000" + Math.floor(Math.random() * 10000)).slice(-4);

    $('#STPG_cardRegForm [name="custIp"]').val("<?php echo getRealClientIp() ?>");
    $('#STPG_cardRegForm [name="trdDt"]').val(year + month + day);
    $('#STPG_cardRegForm [name="trdTm"]').val(hours + mins + secs);
    $('#STPG_cardRegForm [name="mchtTrdNo"]').val("CARDREG" + year + month + day + hours + mins + secs + rnd4);
}

/** 카드 등록(빌키발급) 버튼 동작 */
function registerCard(){
    init();

    // SHA256 해시 + 민감정보 AES256 암호화 (pay_encryptParams.php 재사용)
    // 해시 조합: mchtId + method + mchtTrdNo + trdDt + trdTm + trdAmt(평문,"0") + 라이센스키
    $.ajax({
        type : "POST",
        url : "pay_encryptParams.php",
        dataType : "json",
        data : $("#STPG_cardRegForm").serialize(),
        success : function(rsp){
            for(var name in rsp.encParams){
                $('#STPG_cardRegForm [name='+name+']').val( rsp.encParams[name] );
            }

            // 가맹점 -> 헥토파이낸셜 빌키발급 결제창 호출
            SETTLE_PG.pay({
                env : "<?php echo PAYMENT_SERVER ?>",
                mchtId : $('#STPG_cardRegForm [name="mchtId"]').val(),
                method : "card",        // 신용카드 (고정)
                methodSub : "auth",     // 빌키발급창(0원 등록) (고정)
                trdDt : $('#STPG_cardRegForm [name="trdDt"]').val(),
                trdTm : $('#STPG_cardRegForm [name="trdTm"]').val(),
                mchtTrdNo : $('#STPG_cardRegForm [name="mchtTrdNo"]').val(),
                mchtName : $('#STPG_cardRegForm [name="mchtName"]').val(),
                mchtEName : $('#STPG_cardRegForm [name="mchtEName"]').val(),
                pmtPrdtNm : $('#STPG_cardRegForm [name="pmtPrdtNm"]').val(),
                trdAmt : $('#STPG_cardRegForm [name="trdAmt"]').val(),   // 암호문("0" 암호화)
                mchtCustNm : $('#STPG_cardRegForm [name="mchtCustNm"]').val(),
                mchtCustId : $('#STPG_cardRegForm [name="mchtCustId"]').val(),
                notiUrl : $('#STPG_cardRegForm [name="notiUrl"]').val(),
                nextUrl : $('#STPG_cardRegForm [name="nextUrl"]').val(),
                cancUrl : $('#STPG_cardRegForm [name="cancUrl"]').val(),
                mchtParam : $('#STPG_cardRegForm [name="mchtParam"]').val(),
                custIp : $('#STPG_cardRegForm [name="custIp"]').val(),
                pktHash : rsp.hashCipher,
                ui : {
                    type : "popup",   // popup, iframe, self, blank
                    width : "430",
                    height : "660"
                }
            }, function(rsp){
                console.log(rsp);
            });
        },
        error : function(){ alert("암호화 처리 중 에러가 발생했습니다."); }
    });
}

/** pay_receiveResult.php(팝업)로부터 응답 값을 받아 폼에 세팅 */
function rstparamSet(rslt){
    $('#STPG_cardRegForm [name="respMchtId"]').val(rslt.mchtId);
    $('#STPG_cardRegForm [name="respOutStatCd"]').val(rslt.outStatCd);
    $('#STPG_cardRegForm [name="respOutRsltCd"]').val(rslt.outRsltCd);
    $('#STPG_cardRegForm [name="respOutRsltMsg"]').val(rslt.outRsltMsg);
    $('#STPG_cardRegForm [name="respMethod"]').val(rslt.method);
    $('#STPG_cardRegForm [name="respMchtTrdNo"]').val(rslt.mchtTrdNo);
    $('#STPG_cardRegForm [name="respMchtCustId"]').val(rslt.mchtCustId);
    $('#STPG_cardRegForm [name="respTrdNo"]').val(rslt.trdNo);
    $('#STPG_cardRegForm [name="respTrdAmt"]').val(rslt.trdAmt);
    $('#STPG_cardRegForm [name="respMchtParam"]').val(rslt.mchtParam);
    $('#STPG_cardRegForm [name="respAuthDt"]').val(rslt.authDt);
    $('#STPG_cardRegForm [name="respFnNm"]').val(rslt.fnNm);
    $('#STPG_cardRegForm [name="respFnCd"]').val(rslt.fnCd);
    $('#STPG_cardRegForm [name="respBillKey"]').val(rslt.billKey);
}

/** 폼에 세팅된 응답 값을 결과 페이지로 출력 */
function goResult(){
    $('#STPG_cardRegForm').attr("action", "pay_showResult.php");
    $('#STPG_cardRegForm').attr("method", "post");
    $('#STPG_cardRegForm').attr("target", "");
    $('#STPG_cardRegForm').submit();
}
</script>
</head>
<body>
<button onclick="location.href='pay_form.php'">결제 샘플로</button>
<button onclick="location.href='billkey_pay_form.php'">빌키 결제 샘플로</button>
<div class="wrapper">
    <form id="STPG_cardRegForm" name="STPG_cardRegForm">
        <!-- 요청 파라미터(필수) -->
        <input type="hidden" name="method" value="card" />
        <input type="hidden" name="trdDt" value="" />
        <input type="hidden" name="trdTm" value="" />
        <input type="hidden" name="mchtTrdNo" value="" />
        <input type="hidden" name="mchtName" value="헥토파이낸셜" />
        <input type="hidden" name="mchtEName" value="HectoFinancial" />
        <input type="hidden" name="pmtPrdtNm" value="테스트상품" />
        <input type="hidden" name="notiUrl" value="http://localhost/receiveNoti.php" />
        <input type="hidden" name="nextUrl" value="http://localhost/pay_receiveResult.php" />
        <input type="hidden" name="cancUrl" value="http://localhost/pay_receiveResult.php" />
        <input type="hidden" name="custIp" value="" />
        <input type="hidden" name="mchtParam" value="상점 예약 필드" />

        <!-- 암호화 처리 후 세팅될 파라미터 -->
        <input type="hidden" name="trdAmt" />
        <input type="hidden" name="mchtCustNm" />
        <input type="hidden" name="mchtCustId" />

        <!-- 응답 파라미터(팝업 복귀 시 세팅) -->
        <input type="hidden" name="respMchtId" />
        <input type="hidden" name="respOutStatCd" />
        <input type="hidden" name="respOutRsltCd" />
        <input type="hidden" name="respOutRsltMsg" />
        <input type="hidden" name="respMethod" />
        <input type="hidden" name="respMchtTrdNo" />
        <input type="hidden" name="respMchtCustId" />
        <input type="hidden" name="respTrdNo" />
        <input type="hidden" name="respTrdAmt" />
        <input type="hidden" name="respMchtParam" />
        <input type="hidden" name="respAuthDt" />
        <input type="hidden" name="respFnNm" />
        <input type="hidden" name="respFnCd" />
        <input type="hidden" name="respBillKey" />

        <div class="tab">신용카드 0원 등록(빌키발급) 결제창 호출</div>
        <table>
            <tr>
                <td class="left">상점아이디</td>
                <td class="right">
                    <input type="text" name="mchtId" value="nxca_jt_gu" />
                    <span>구인증 nxca_jt_gu / 비인증 nxca_jt_bi</span>
                </td>
            </tr>
            <tr>
                <td class="left">거래금액</td>
                <td class="right">
                    <input type="text" name="plainTrdAmt" value="0" readonly />
                    <span>0원 인증 고정</span>
                </td>
            </tr>
            <tr>
                <td class="left">고객명</td>
                <td class="right"><input type="text" name="plainMchtCustNm" value="홍길동" /></td>
            </tr>
            <tr>
                <td class="left">상점고객아이디</td>
                <td class="right"><input type="text" name="plainMchtCustId" value="HongGilDong" /></td>
            </tr>
        </table>
        <div class="content">
            <input type="button" class="payBtn" value="신용카드 등록(빌키발급)" onclick="registerCard()" />
        </div>
    </form>
</div>
</body>
</html>
