<?php
    include_once "config.php";
    header('Content-Type: text/html; charset=utf-8');
    date_default_timezone_set('Asia/Seoul');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>헥토파이낸셜 PG 신용카드 빌키 결제 샘플</title>
<style>
#STPG_billForm, p               {font-family:굴림; font-size:10pt;}
#STPG_billForm .required::after {content:"* 필수 *"; color:red;}
#STPG_billForm .fixed::after    {content:"* 고정값 *"; color:red;}
#STPG_billForm h4               {background-color:#f1f1f1; padding:4px; margin:2px;}
#STPG_billForm select           {width:287px;}
#STPG_billForm input            {width:280px;}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script type="text/javascript">
/**
 * 신용카드 빌키 결제 API (2회차 결제)
 * - 0원 등록(cardReg_form.php) 또는 표준 결제창에서 발급받은 billKey 로 UI 없이 서버 간 통신으로 결제합니다.
 * - 호출 경로: /spay/APICardActionPay.do (게이트웨이, JSON)
 * - 결제수단(method=CA), 업무구분(bizType=B0)은 신용카드 빌키 결제 고정값입니다.
 */
$(function(){
    var d = new Date();
    var year  = d.getFullYear().toString();
    var month = ("0" + (d.getMonth() + 1)).slice(-2);
    var day   = ("0" + d.getDate()).slice(-2);
    var hours = ("0" + d.getHours()).slice(-2);
    var mins  = ("0" + d.getMinutes()).slice(-2);
    var secs  = ("0" + d.getSeconds()).slice(-2);

    $('#STPG_billForm [name="trdDt"]').val(year + month + day);
    $('#STPG_billForm [name="trdTm"]').val(hours + mins + secs);
    $('#STPG_billForm [name="mchtTrdNo"]').val("BILLPAY" + year + month + day + hours + mins + secs);
});

/** 결제 요청 */
function doAction(){
    $('#STPG_billForm').attr("action", "billkey_pay_showResult.php");
    $('#STPG_billForm').attr("method", "post");
    $('#STPG_billForm').attr("target", "_self");
    $('#STPG_billForm').submit();
}
</script>
</head>
<body>
<button onclick="location.href='cardReg_form.php'">0원 등록(빌키발급) 샘플로</button>
<button onclick="location.href='cancel_form.php'">취소 샘플로</button>
<h3>전자결제(PG) 신용카드 빌키 결제 API</h3>
<p>
<b>신용카드 빌키 결제 API</b> : 0원 등록(빌키발급) 결제창 또는 표준 결제창을 통해 발급받은 빌키로 결제하는 API입니다.<br>
</p>
<form id="STPG_billForm" name="STPG_billForm">
    <table>
    <tr><td colspan="2" style="text-align:center;"><h4>params</h4></td></tr>

    <!-- 상점아이디(구인증 nxca_ks_gu / 비인증 nxca_jt_bi) -->
    <tr class="required">
        <td>mchtId[상점아이디]</td><td><input type="text" name="mchtId" value="nxca_jt_bi" maxlength="12"/></td>
    </tr>

    <tr class="fixed">
        <td>ver[전문버전]</td><td><input type="text" name="ver" value="0A19" maxlength="4" readonly/></td>
    </tr>

    <!-- 결제수단(신용카드 빌키 결제[CA] 고정) -->
    <tr class="fixed">
        <td>method[결제수단]</td><td><input type="text" name="method" value="CA" maxlength="2" readonly/></td>
    </tr>

    <!-- 업무구분(신용카드 빌키 결제[B0] 고정) -->
    <tr class="fixed">
        <td>bizType[업무구분]</td><td><input type="text" name="bizType" value="B0" maxlength="2" readonly/></td>
    </tr>

    <tr class="fixed">
        <td>encCd[암호화구분]</td><td><input type="text" name="encCd" value="23" maxlength="2" readonly/></td>
    </tr>

    <tr class="required">
        <td>mchtTrdNo[상점주문번호]</td><td><input type="text" name="mchtTrdNo" value="" maxlength="100"/></td>
    </tr>

    <tr class="required">
        <td>trdDt[요청일자]</td><td><input type="text" name="trdDt" value="" maxlength="8"/></td>
    </tr>

    <tr class="required">
        <td>trdTm[요청시간]</td><td><input type="text" name="trdTm" value="" maxlength="6"/></td>
    </tr>

    <tr>
        <td>mobileYn[모바일여부]</td>
        <td><select name="mobileYn"><option value="N">PC</option><option value="Y">모바일</option></select></td>
    </tr>

    <tr>
        <td>osType[OS구분]</td>
        <td><select name="osType">
            <option value="W">Windows</option><option value="A">Android</option>
            <option value="I">iOS</option><option value="M">Mac</option><option value="E">others</option>
        </select></td>
    </tr>

    <tr><td colspan="2" style="text-align:center;"><h4>data</h4></td></tr>

    <!-- 빌키(자동결제키) -->
    <tr class="required">
        <td>billKey[빌키]</td><td><input type="text" name="billKey" value="SBILL_0123456789" maxlength="50"/></td>
    </tr>

    <!-- 거래금액 -->
    <tr class="required">
        <td>trdAmt[거래금액]</td><td><input type="text" name="trdAmt" value="1000" maxlength="12"/></td>
    </tr>

    <tr class="required">
        <td>pmtprdNm[상품명]</td><td><input type="text" name="pmtprdNm" value="테스트상품" maxlength="128"/></td>
    </tr>

    <tr class="required">
        <td>mchtCustNm[상점고객명]</td><td><input type="text" name="mchtCustNm" value="홍길동" maxlength="30"/></td>
    </tr>

    <tr class="required">
        <td>mchtCustId[상점고객아이디]</td><td><input type="text" name="mchtCustId" value="HongGilDong" maxlength="50"/></td>
    </tr>

    <tr>
        <td>email[이메일]</td><td><input type="text" name="email" value="HongGilDong@example.com" maxlength="60"/></td>
    </tr>

    <!-- 할부개월수(00:일시불, 02~12) -->
    <tr class="required">
        <td>instmtMon[할부개월수]</td><td><input type="text" name="instmtMon" value="00" maxlength="2"/></td>
    </tr>

    <tr class="fixed">
        <td>crcCd[통화구분]</td><td><input type="text" name="crcCd" value="KRW" readonly/></td>
    </tr>

    <tr>
        <td>taxTypeCd[과세구분코드]</td>
        <td><select name="taxTypeCd">
            <option value="N">과세</option><option value="Y">면세</option><option value="G">복합과세</option>
        </select></td>
    </tr>

    <!-- 과세/부가세/비과세 금액(복합과세인 경우 필수) -->
    <tr><td>taxAmt[과세금액]</td><td><input type="text" name="taxAmt" value="" maxlength="12"/></td></tr>
    <tr><td>vatAmt[부가세금액]</td><td><input type="text" name="vatAmt" value="" maxlength="12"/></td></tr>
    <tr><td>taxFreeAmt[비과세금액]</td><td><input type="text" name="taxFreeAmt" value="" maxlength="12"/></td></tr>
    <tr><td>svcAmt[봉사료]</td><td><input type="text" name="svcAmt" value="" maxlength="12"/></td></tr>

    <tr class="required">
        <td>notiUrl[결과처리URL]</td><td><input type="text" name="notiUrl" value="http://localhost/receiveNoti.php" maxlength="250"/></td>
    </tr>

    <tr>
        <td>mchtParam[기타주문정보]</td><td><input type="text" name="mchtParam" value="기타주문정보"/></td>
    </tr>

    <tr>
        <td colspan="2" style="text-align:center;"><input style="margin-top:20px;" type="button" value="결제 요청" onclick="doAction()"/></td>
    </tr>
    </table>
</form>
</body>
</html>
