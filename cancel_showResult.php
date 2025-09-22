<?php
include_once "config.php";
include_once "settleUtils.php";

//설정 정보 가져오기
$aesKey = AES256_KEY;           //AES256 암복호화 키
$licenseKey = LICENSE_KEY;      //라이센스 키
$cnclServer = CANCEL_SERVER;    //타겟URL
$connTimeout = CONN_TIMEOUT;    //connect timeout
$timeout = TIMEOUT;             //curl total timeout

//요청 파라미터(헤더)
$REQ_HEADER = array(
    "mchtId"    => null_to_empty(get_param("mchtId")),      //상점아이디
    "ver"       => null_to_empty(get_param("ver")),         //버전
    "method"    => null_to_empty(get_param("method")),      //결제수단
    "bizType"   => null_to_empty(get_param("bizType")),     //업무구분
    "encCd"     => null_to_empty(get_param("encCd")),       //암호화구분
    "mchtTrdNo" => null_to_empty(get_param("mchtTrdNo")),   //상점주문번호
    "trdDt"     => null_to_empty(get_param("trdDt")),       //요청일자
    "trdTm"     => null_to_empty(get_param("trdTm")),       //요청시간
    "mobileYn"  => null_to_empty(get_param("mobileYn")),    //모바일여부
    "osType"    => null_to_empty(get_param("osType"))       //운영체제 구분
);


//요청 파라미터(바디)
$REQ_BODY = array(
    "orgTrdNo"      => null_to_empty(get_param("orgTrdNo")),        //원거래번호
    "cnclAmt"       => null_to_empty(get_param("cnclAmt")),         //취소금액
    "crcCd"         => null_to_empty(get_param("crcCd")),           //통화구분
    "cnclOrd"       => null_to_empty(get_param("cnclOrd")),         //부분취소회차
    "cnclRsn"       => null_to_empty(get_param("cnclRsn")),         //취소사유
    "taxTypeCd"     => null_to_empty(get_param("taxTypeCd")),       //면세여부
    "taxAmt"        => null_to_empty(get_param("taxAmt")),          //과세금액
    "vatAmt"        => null_to_empty(get_param("vatAmt")),          //부가세금액
    "taxFreeAmt"    => null_to_empty(get_param("taxFreeAmt")),      //비과세금액(면세금액)
    "svcAmt"        => null_to_empty(get_param("svcAmt")),          //봉사료
    "vAcntNo"       => null_to_empty(get_param("vAcntNo")),         //가상계좌번호
    "refundBankCd"  => null_to_empty(get_param("refundBankCd")),    //환불은행코드      
    "refundAcntNo"  => null_to_empty(get_param("refundAcntNo")),    //환불계좌번호
    "refundDpstrNm" => null_to_empty(get_param("refundDpstrNm"))    //환불계좌예금주명
);


//응답 파라미터(헤더)
$RES_HEADER = array(
    "mchtId" => "",     //상점아이디
    "ver" => "",        //버전
    "method" => "",     //결제수단
    "bizType" => "",    //업무구분
    "encCd" => "",      //암호화구분
    "mchtTrdNo" => "",  //상점주문번호
    "trdNo" => "",      //세틀뱅크거래번호
    "trdDt" => "",      //요청일자
    "trdTm" => "",      //요청시간
    "outStatCd" => "",  //결과코드
    "outRsltCd" => "",  //거절코드
    "outRsltMsg" => ""  //결과메세지
);


//응답 파라미터(바디)
$RES_BODY = array(
    "pktHash" => "",        //해쉬값
    "orgTrdNo" => "",       //원거래번호
    "cnclAmt" => "",        //취소금액
    "cardCnclAmt" => "",    //신용카드취소금액
    "pntCnclAmt" => "",     //포인트취소금액
    "coupCnclAmt" => "",    //쿠폰취소금액
    "blcAmt" => "",         //취소가능잔액
    "acntType" => "",       //계좌구분
    "vAcntNo" => "",        //가상계좌번호
    "rfdPsblCd" => ""       //휴대폰결제 환불가능여부
);


//AES256 암호화 필요 파라미터
$ENCRYPT_PARAMS = array("refundAcntNo", "vAcntNo", "cnclAmt","taxAmt","vatAmt","taxFreeAmt", "svcAmt");


//AES256 복호화 필요 파라미터
$DECRYPT_PARAMS = array("cnclAmt","cardCnclAmt","pntCnclAmt","coupCnclAmt","blcAmt", "vAcntNo");



/** ===============================================================================
 *                          SHA256 해쉬 처리
 *  조합필드 : 요청일자 + 요청시간 + 상점아이디 + 상점주문번호 + 취소금액 + 라이센스키
 *  ===============================================================================   */
$hashPlain = "";
$hashCipher ="";
try{
    if( "VA" === $REQ_HEADER["method"]   &&  "A2" === $REQ_HEADER["bizType"]  ){// 가상계좌/010가상계좌 채번취소 0원으로 설정
        $hashPlain = $REQ_HEADER["trdDt"].$REQ_HEADER["trdTm"].$REQ_HEADER["mchtId"].$REQ_HEADER["mchtTrdNo"]."0".$licenseKey;
    }
    else{
        $hashPlain = $REQ_HEADER["trdDt"].$REQ_HEADER["trdTm"].$REQ_HEADER["mchtId"].$REQ_HEADER["mchtTrdNo"].$REQ_BODY["cnclAmt"].$licenseKey;
    }
    
    $hashCipher = hash("sha256", $hashPlain);
}catch(Exception $ex){
    log_message(LOG_FILE, "[".$REQ_HEADER["mchtTrdNo"]."][SHA256 HASHING] Hashing Fail! : ".$ex->getMessage());
}finally{
    log_message(LOG_FILE, "[".$REQ_HEADER["mchtTrdNo"]."][SHA256 HASHING] Plain Text[".$hashPlain."] ---> Cipher Text[".$hashCipher."]");
    $REQ_BODY["pktHash"] = $hashCipher; // sha256 해쉬 결과 저장
}



/** ======================================================================
 *                          AES256 암호화 처리
 *  ======================================================================   */
try{
    foreach($ENCRYPT_PARAMS as $i){
        $aesPlain = $REQ_BODY[$i];
        if( !( "" == $aesPlain )){
            
            $chiperRaw = openssl_encrypt($aesPlain, "AES-256-ECB",  $aesKey , OPENSSL_RAW_DATA);
            $aesCipher = base64_encode($chiperRaw);

            $REQ_BODY[$i] = $aesCipher;//암호화 결과 값 세팅
            log_message(LOG_FILE, "[".$REQ_HEADER["mchtTrdNo"]."][AES256 Encrypt] ".$i."[".$aesPlain."] ---> [".$aesCipher."]");
        }
    }
}catch(Exception $ex){
    log_message(LOG_FILE, "[".$REQ_HEADER["mchtTrdNo"]."][AES256 Encrypt] AES256 Fail! : ".$ex->getMessage());
    throw new Exception("aes256 encrypt fail");
}

/** ======================================================================
 *                          타겟 URL 설정
 *  타겟 서버 : (tb)gw.settlebank.co.kr
 *  공통 취소 : ~/spay/APICancel.do 
 *  가상계좌 채번취소 : ~/spay/APIVBank.do
 *  가상계좌, 휴대폰결제 환불 : ~/spay/APIRefund.do
 *  ======================================================================   */
$requestUrl = "";
if( "VA" === $REQ_HEADER["method"] ){ 
    if( "C0" === $REQ_HEADER["bizType"]){
        $requestUrl = $cnclServer."/spay/APIRefund.do";
    }
    else{
        $requestUrl = $cnclServer."/spay/APIVBank.do";
    }
}
else if( "MP" === $REQ_HEADER["method"] ){ 
    if( "C1" === $REQ_HEADER["bizType"]){
        $requestUrl = $cnclServer."/spay/APIRefund.do";
    }
    else{
        $requestUrl = $cnclServer."/spay/APICancel.do";
    }
}
else{
    $requestUrl = $cnclServer."/spay/APICancel.do";
}

//요청파라미터 JSON에 세팅
//params, data 이름은 세틀로 전달되야 하는 값이니 변경하지 마십시오.
$reqParam = array(
    "params" => $REQ_HEADER,
    "data" => $REQ_BODY
);


/** ======================================================================
 *                          API호출(가맹점->세틀) 및 응답 처리
 *  ======================================================================   */
$respParam = array();


//send_api ( API호출 URL, 전송될데이터, 연결 타임아웃, curl 타임아웃 )
$resData = send_api($requestUrl, $reqParam, $connTimeout, $timeout); 

//응답 파라미터 파싱
$resData = json_decode( $resData, true );
$respHeader =   array_key_exists('params', $resData) ? $resData['params'] : null;
$respBody =  array_key_exists('data', $resData) ? $resData['data'] : null;

//응답 파라미터 세팅(헤더)
if( $respHeader != null ){
    foreach ($RES_HEADER as $key => $val ) {
        $respParam[$key] =  null_to_empty( array_key_exists($key, $respHeader) ? $respHeader[$key] : "" );
    }
}else{
    foreach ($RES_HEADER as $key => $val ) {
        $respParam[$key] =  "";
    }
}

//응답 파라미터 세팅(바디)
if( $respBody != null){
    foreach ($RES_BODY as $key => $val ) {
        $respParam[$key] =  null_to_empty( array_key_exists($key, $respBody) ? $respBody[$key] : "" );
    }
}else{
    foreach ($RES_BODY as $key => $val ) {
        $respParam[$key] =  "";
    }
}



/** ======================================================================
 *                          AES256 복호화 처리
 *  ====================================================================== */
try{
    foreach($DECRYPT_PARAMS as $i){
        if( array_key_exists($i, $respParam)){
            $aesCipher = trim($respParam[$i]);
            if( "" != $aesCipher ){
                $cipherRaw = base64_decode($aesCipher);
                if( $cipherRaw === false ){
                    throw new Exception("base64_decode() error ".$i."[".$aesCipher."]");
                }

                $aesPlain = openssl_decrypt($cipherRaw, "AES-256-ECB",  $aesKey , OPENSSL_RAW_DATA);

                if( $aesPlain === false ){
                    throw new Exception("openssl_decrypt() error ".$i."[".$aesCipher."]");
                }

                $respParam[$i] = $aesPlain;//복호화된 데이터로 세팅
                log_message(LOG_FILE, "[".$REQ_HEADER["mchtTrdNo"]."][AES256 Decrypt] ".$i."[".$aesCipher."] ---> [".$aesPlain."]");
            }
        }
    }
}catch(Exception $ex){
    log_message(LOG_FILE, "[".$REQ_HEADER["mchtTrdNo"]."][AES256 Decrypt] AES256 Fail! : ".$ex->getMessage());
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>취소 요청 결과</title>
<style type="text/css">
    body            {font-family:굴림; font-size:10pt; color:#000000; text-decoration:none;}
    font            {font-family:굴림; font-size:10pt; color:#000000; text-decoration:none;}
    td              {font-family:굴림; font-size:10pt; color:#000000; text-decoration:none; padding:3px; border:1px solid #e1e1e1;}
    .left           {padding-left:5px; width:210px;}
    .right          {padding-left:5px;}
    .wrapper        {width:700px;border:1px solid #e1e1e1;}
    .tab            {background-color:#f1f1f1;padding:10px 20px;border:1px solid #e1e1e1; font-weight: bold; font-size:1.1em;}
    table           {width:100%; border-collapse:collapse;}
    .button         {padding:5px 20px; border-radius:20px; border:1px solid #ccc; width:70%; margin:5px 0px; transition:0.3s; cursor:pointer;}
    .button:hover   {background-color:#aaaaaa;}
</style>
</head>
<body>
<h2>취소 요청 결과</h2>
<div class="wrapper">
    <div class="tab">응답 파라미터</div>
    <table>
        <tr>
            <td class="left">mchtId[상점아이디]</td>
            <td class="right"><?php echo $respParam["mchtId"] ?></td>
        </tr>
        <tr>
            <td class="left">ver[버전]</td>
            <td class="right"><?php echo $respParam["ver"] ?></td>
        </tr>
        <tr>
            <td class="left">method[결제수단]</td>
            <td class="right"><?php echo $respParam["method"] ?></td>
        </tr>
        <tr>
            <td class="left">bizType[업무구분]</td>
            <td class="right"><?php echo $respParam["bizType"] ?></td>
        </tr>
        <tr>
            <td class="left">encCd[암호화구분]</td>
            <td class="right"><?php echo $respParam["encCd"] ?></td>
        </tr>
        <tr>
            <td class="left">mchtTrdNo[상점주문번호]</td>
            <td class="right"><?php echo $respParam["mchtTrdNo"] ?></td>
        </tr>
        <tr>
            <td class="left">trdNo[세틀뱅크 거래번호]</td>
            <td class="right"><?php echo $respParam["trdNo"] ?></td>
        </tr>
        <tr>
            <td class="left">trdDt[취소요청일자]</td>
            <td class="right"><?php echo $respParam["trdDt"] ?></td>
        </tr>
        <tr>
            <td class="left">trdTm[취소요청시간]</td>
            <td class="right"><?php echo $respParam["trdTm"] ?></td>
        </tr>
        <tr>
            <td class="left">outStatCd[거래상태코드]</td>
            <td class="right"><?php echo $respParam["outStatCd"] ?></td>
        </tr>
        <tr>
            <td class="left">outRsltCd[거래결과코드]</td>
            <td class="right"><?php echo $respParam["outRsltCd"] ?></td>
        </tr>
        <tr>
            <td class="left">outRsltMsg[결과메세지]</td>
            <td class="right"><?php echo $respParam["outRsltMsg"] ?></td>
        </tr>
        <tr>
            <td class="left">pktHash[해쉬값]</td>
            <td class="right"><?php echo $respParam["pktHash"] ?></td>
        </tr>
        <tr>
            <td class="left">orgTrdNo[원거래번호]</td>
            <td class="right"><?php echo $respParam["orgTrdNo"] ?></td>
        </tr>
        <tr>
            <td class="left">cnclAmt[취소금액]</td>
            <td class="right"><?php echo $respParam["cnclAmt"] ?></td>
        </tr>
        <tr>
            <td class="left">cardCnclAmt[신용카드 취소금액]</td>
            <td class="right"><?php echo $respParam["cardCnclAmt"] ?></td>
        </tr>
        <tr>
            <td class="left">pntCnclAmt[포인트 취소금액]</td>
            <td class="right"><?php echo $respParam["pntCnclAmt"] ?></td>
        </tr>
        <tr>
            <td class="left">coupCnclAmt[쿠폰 취소금액]</td>
            <td class="right"><?php echo $respParam["coupCnclAmt"] ?></td>
        </tr>
        <tr>
            <td class="left">blcAmt[취소 가능 잔액]</td>
            <td class="right"><?php echo $respParam["blcAmt"] ?></td>
        </tr>
        <tr>
            <td class="left">acntType[계좌구분]</td>
            <td class="right"><?php echo $respParam["acntType"] ?></td>
        </tr>
        <tr>
            <td class="left">vAcntNo[가상계좌번호]</td>
            <td class="right"><?php echo $respParam["vAcntNo"] ?></td>
        </tr>
        <tr>
            <td class="left">rfdPsblCd[환불가능여부]</td>
            <td class="right"><?php echo $respParam["rfdPsblCd"] ?></td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center;"><input class="button" type="button" name="button" value="돌아가기" onclick="location.href='cancel_form.php'"></td>
        </tr>
    </table>
</div>
</body>
</html>