<?php
header('Content-Type: text/html; charset=utf-8');
include_once "config.php";
include_once "settleUtils.php";


/** 설정 정보 저장 */
$aesKey = AES256_KEY;

/** 응답 파라미터 세팅 */
$RES_PARAMS = array(
    "mchtId"        => null_to_empty(get_param("mchtId")),          //상점아이디
    "outStatCd"     => null_to_empty(get_param("outStatCd")),       //결과코드
    "outRsltCd"     => null_to_empty(get_param("outRsltCd")),       //거절코드
    "outRsltMsg"    => null_to_empty(get_param("outRsltMsg")),      //결과메세지
    "method"        => null_to_empty(get_param("method")),          //결제수단
    "mchtTrdNo"     => null_to_empty(get_param("mchtTrdNo")),       //상점주문번호
    "mchtCustId"    => null_to_empty(get_param("mchtCustId")),      //상점고객아이디
    "trdNo"         => null_to_empty(get_param("trdNo")),           //세틀뱅크 거래번호
    "trdAmt"        => null_to_empty(get_param("trdAmt")),          //거래금액
    "mchtParam"     => null_to_empty(get_param("mchtParam")),       //상점예약필드
    "authDt"        => null_to_empty(get_param("authDt")),          //승인일시
    "authNo"        => null_to_empty(get_param("authNo")),          //승인번호
    "reqIssueDt"    => null_to_empty(get_param("reqIssueDt")),      //채번요청일시
    "intMon"        => null_to_empty(get_param("intMon")),          //할부개월수
    "fnNm"          => null_to_empty(get_param("fnNm")),            //카드사명
    "fnCd"          => null_to_empty(get_param("fnCd")),            //카드사코드
    "pointTrdNo"    => null_to_empty(get_param("pointTrdNo")),      //포인트거래번호
    "pointTrdAmt"   => null_to_empty(get_param("pointTrdAmt")),     //포인트거래금액
    "cardTrdAmt"    => null_to_empty(get_param("cardTrdAmt")),      //신용카드결제금액
    "vtlAcntNo"     => null_to_empty(get_param("vtlAcntNo")),       //가상계좌번호
    "expireDt"      => null_to_empty(get_param("expireDt")),        //입금기한
    "cphoneNo"      => null_to_empty(get_param("cphoneNo")),        //휴대폰번호
    "billKey"       => null_to_empty(get_param("billKey")),         //자동결제키
    "csrcAmt"       => null_to_empty(get_param("csrcAmt"))          //현금영수증 발급 금액(네이버페이)
);


//AES256 복호화 필요 파라미터
$DECRYPT_PARAMS = array("mchtCustId","trdAmt", "pointTrdAmt", "cardTrdAmt", "vtlAcntNo", "cphoneNo", "csrcAmt" );

/*============================================================================================================================================
 *  AES256 복호화 처리(Base64 decoding -> AES-256-ECB decrypt )
 *============================================================================================================================================ */
try{
    foreach($DECRYPT_PARAMS as $i){
        if( array_key_exists($i, $RES_PARAMS)){
            $aesCipher = trim($RES_PARAMS[$i]);
            if( "" != $aesCipher ){
                $cipherRaw = base64_decode($aesCipher);
                if( $cipherRaw === false ){
                    throw new Exception("base64_decode() error".$i."[".$aesCipher."]");
                }
                $aesPlain = openssl_decrypt($cipherRaw, "AES-256-ECB",  $aesKey , OPENSSL_RAW_DATA);
                if( $aesPlain === false ){
                    throw new Exception("openssl_decrypt() error".$i."[".$aesCipher."]");
                }

                $RES_PARAMS[$i] = $aesPlain;//복호화된 데이터로 세팅
                log_message(LOG_FILE, "[".$RES_PARAMS["mchtTrdNo"]."][AES256 Decrypt] ".$i."[".$aesCipher."] ---> [".$aesPlain."]");
            }
        }
    }
}catch(Exception $ex){
    log_message(LOG_FILE, "[".$RES_PARAMS["mchtTrdNo"]."][AES256 Decrypt] AES256 Fail! : ".$ex->getMessage());
}



//응답 파라미터 로깅
$logStr = "[".$RES_PARAMS["mchtTrdNo"]."][Response Data] ";
foreach( $RES_PARAMS as $key => $val) {
    $logStr .= $key."(".$val.") ";
}
log_message(LOG_FILE, $logStr);
?>
<html>
<head><title>헥토파이낸셜 PG 결제 샘플</title>
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<style type="text/css">
    body            {font-family:굴림; font-size:10pt; color:#000000; text-decoration:none;}
    font            {font-family:굴림; font-size:10pt; color:#000000; text-decoration:none;}
    td              {font-family:굴림; font-size:10pt; color:#000000; text-decoration:none; padding:3px; border:1px solid #e1e1e1;}
    .left           {padding-left:5px; width:100px;}
    .right          {padding-left:5px;}
    .wrapper        {max-width:700px;border:1px solid #e1e1e1;}
    .tab            {background-color:#f1f1f1;padding:10px 20px;border:1px solid #e1e1e1; font-weight: bold; font-size:1.1em;}
    table           {width:100%; border-collapse:collapse;}
    .button         {padding:5px 20px; border-radius:20px; border:1px solid #ccc; width:70%; margin:5px 0px; transition:0.3s; cursor:pointer;}
    .button:hover   {background-color:#aaaaaa;}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
//결제 결과 세팅
var _PAY_RESULT = {
    mchtId :        "<?php echo $RES_PARAMS["mchtId"] ?>",
    outStatCd :     "<?php echo $RES_PARAMS["outStatCd"] ?>",
    outRsltCd :     "<?php echo $RES_PARAMS["outRsltCd"] ?>",
    outRsltMsg :    "<?php echo $RES_PARAMS["outRsltMsg"] ?>",
    method :        "<?php echo $RES_PARAMS["method"] ?>",
    mchtTrdNo :     "<?php echo $RES_PARAMS["mchtTrdNo"] ?>",
    mchtCustId :    "<?php echo $RES_PARAMS["mchtCustId"] ?>",
    trdNo :         "<?php echo $RES_PARAMS["trdNo"] ?>",
    trdAmt :        "<?php echo $RES_PARAMS["trdAmt"] ?>",
    mchtParam :     "<?php echo $RES_PARAMS["mchtParam"] ?>",
    authDt :        "<?php echo $RES_PARAMS["authDt"] ?>",
    authNo :        "<?php echo $RES_PARAMS["authNo"] ?>",
    reqIssueDt :    "<?php echo $RES_PARAMS["reqIssueDt"] ?>",
    intMon :        "<?php echo $RES_PARAMS["intMon"] ?>",
    fnNm :          "<?php echo $RES_PARAMS["fnNm"] ?>",
    fnCd :          "<?php echo $RES_PARAMS["fnCd"] ?>",
    pointTrdNo :    "<?php echo $RES_PARAMS["pointTrdNo"] ?>",
    pointTrdAmt :   "<?php echo $RES_PARAMS["pointTrdAmt"] ?>",
    cardTrdAmt :    "<?php echo $RES_PARAMS["cardTrdAmt"] ?>",
    vtlAcntNo :     "<?php echo $RES_PARAMS["vtlAcntNo"] ?>",
    expireDt :      "<?php echo $RES_PARAMS["expireDt"] ?>",
    cphoneNo :      "<?php echo $RES_PARAMS["cphoneNo"] ?>",
    billKey :       "<?php echo $RES_PARAMS["billKey"] ?>",
    csrcAmt :       "<?php echo $RES_PARAMS["csrcAmt"] ?>",
};

//main으로 결과 전달
function sendResult()
{
    if(top.opener){
        //팝업창
        top.opener.rstparamSet(_PAY_RESULT);
        top.opener.goResult();
        self.close();
    }
    else{//iframe
        parent.postMessage(JSON.stringify({action:"HECTO_IFRAME_CLOSE", params: _PAY_RESULT}), "*");
    }
}
</script>
</head>
<body>
<h2>승인 요청 결과</h2>
<div class="wrapper">
    <div class="tab">응답 파라미터</div>
    <table>
        <tr>
            <td class="left">mchtId</td>
            <td class="right"><?php echo $RES_PARAMS["mchtId"] ?></td>
        </tr>
        <tr>
            <td class="left">outStatCd</td>
            <td class="right"><?php echo $RES_PARAMS["outStatCd"] ?></td>
        </tr>
        <tr>
            <td class="left">outRsltCd</td>
            <td class="right"><?php echo $RES_PARAMS["outRsltCd"] ?></td>
        </tr>
        <tr>
            <td class="left">outRsltMsg</td>
            <td class="right"><?php echo $RES_PARAMS["outRsltMsg"] ?></td>
        </tr>
        <tr>
            <td class="left">method</td>
            <td class="right"><?php echo $RES_PARAMS["method"] ?></td>
        </tr>
        <tr>
            <td class="left">mchtTrdNo</td>
            <td class="right"><?php echo $RES_PARAMS["mchtTrdNo"] ?></td>
        </tr>
        <tr>
            <td class="left">mchtCustId</td>
            <td class="right"><?php echo $RES_PARAMS["mchtCustId"] ?></td>
        </tr>
        <tr>
            <td class="left">trdNo</td>
            <td class="right"><?php echo $RES_PARAMS["trdNo"] ?></td>
        </tr>
        <tr>
            <td class="left">trdAmt</td>
            <td class="right"><?php echo $RES_PARAMS["trdAmt"] ?></td>
        </tr>
        <tr>
            <td class="left">mchtParam</td>
            <td class="right"><?php echo $RES_PARAMS["mchtParam"] ?></td>
        </tr>
        <tr>
            <td class="left">authDt</td>
            <td class="right"><?php echo $RES_PARAMS["authDt"] ?></td>
        </tr>
        <tr>
            <td class="left">authNo</td>
            <td class="right"><?php echo $RES_PARAMS["authNo"] ?></td>
        </tr>
        <tr>
            <td class="left">reqIssueDt</td>
            <td class="right"><?php echo $RES_PARAMS["reqIssueDt"] ?></td>
        </tr>
        <tr>
            <td class="left">intMon</td>
            <td class="right"><?php echo $RES_PARAMS["intMon"] ?></td>
        </tr>
        <tr>
            <td class="left">fnNm</td>
            <td class="right"><?php echo $RES_PARAMS["fnNm"] ?></td>
        </tr>
        <tr>
            <td class="left">fnCd</td>
            <td class="right"><?php echo $RES_PARAMS["fnCd"] ?></td>
        </tr>
        <tr>
            <td class="left">pointTrdNo</td>
            <td class="right"><?php echo $RES_PARAMS["pointTrdNo"] ?></td>
        </tr>
        <tr>
            <td class="left">pointTrdAmt</td>
            <td class="right"><?php echo $RES_PARAMS["pointTrdAmt"] ?></td>
        </tr>
        <tr>
            <td class="left">cardTrdAmt</td>
            <td class="right"><?php echo $RES_PARAMS["cardTrdAmt"] ?></td>
        </tr>
        <tr>
            <td class="left">vtlAcntNo</td>
            <td class="right"><?php echo $RES_PARAMS["vtlAcntNo"] ?></td>
        </tr>
        <tr>
            <td class="left">expireDt</td>
            <td class="right"><?php echo $RES_PARAMS["expireDt"] ?></td>
        </tr>
        <tr>
            <td class="left">cphoneNo</td>
            <td class="right"><?php echo $RES_PARAMS["cphoneNo"] ?></td>
        </tr>
        <tr>
            <td class="left">billKey</td>
            <td class="right"><?php echo $RES_PARAMS["billKey"] ?></td>
        </tr>
        <tr>
            <td class="left">csrcAmt</td>
            <td class="right"><?php echo $RES_PARAMS["csrcAmt"] ?></td>
        </tr>

        <tr>
            <td colspan="2" style="text-align: center;">
                <input class="button" type="button" value="확인" onclick="sendResult()" /> 
            </td>
        </tr>
    </table>
</div>
</body>
</html>