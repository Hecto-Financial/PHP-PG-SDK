<?php
include_once "config.php";
include_once "settleUtils.php";

//설정 정보 가져오기
$aesKey = AES256_KEY;           //AES256 암복호화 키
$licenseKey = LICENSE_KEY;      //라이센스 키
$apiHost = CANCEL_SERVER;       //자동연장결제 타겟 서버
$connTimeout = CONN_TIMEOUT;    //curl connect timeout
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
    "telCo"     => null_to_empty(get_param("telCo")),       //통신사
    "email"     => null_to_empty(get_param("email")),       //상점고객이메일
    "mUserId"   => null_to_empty(get_param("mUserId")),     //상점고객아이디
    "crcCd"     => null_to_empty(get_param("crcCd")),       //통화구분
    "trdAmt"    => null_to_empty(get_param("trdAmt")),      //거래금액
    "prdtNm"    => null_to_empty(get_param("prdtNm")),      //상품명
    "sellerNm"  => null_to_empty(get_param("sellerNm")),    //판매자명
    "ordNm"     => null_to_empty(get_param("ordNm")),       //주문자명
    "billKey"   => null_to_empty(get_param("billKey"))      //자동결제키
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
    "pktHash"=> "",     //해쉬값
    "telCo"=> "",       //통신사
    "trdAmt"=> "",      //거래금액
    "billKey"=> ""      //자동결제키
);


//AES256 암호화 처리 될 파라미터
$ENCRYPT_PARAMS = array("telCo", "trdAmt");


//AES256 복호화 필요 파라미터
$DECRYPT_PARAMS = array("telCo", "trdAmt");



/** ===================================================================================
 *                          SHA256 해쉬 처리
 *  조합필드 : 요청일자 + 요청시간 + 상점아이디 + 상점주문번호 + 거래금액 + 라이센스키
 *  ===================================================================================   */
$hashPlain = "";
$hashCipher ="";
try{
    $hashPlain = $REQ_HEADER["trdDt"].$REQ_HEADER["trdTm"].$REQ_HEADER["mchtId"].$REQ_HEADER["mchtTrdNo"].$REQ_BODY["trdAmt"].$licenseKey;
    $hashCipher = hash("sha256", $hashPlain);
}catch(Exception $ex){
    log_message(LOG_FILE, "[".$REQ_HEADER["mchtTrdNo"]."][SHA256 HASHING] Hashing Fail! : ".$ex->getMessage());
}finally{
    log_message(LOG_FILE, "[".$REQ_HEADER["mchtTrdNo"]."][SHA256 HASHING] Plain Text[".$hashPlain."] ---> Cipher Text[".$hashCipher."]");
    $REQ_BODY["pktHash"] = $hashCipher; //해쉬 결과 값 세팅
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

//URL설정
$requestUrl = $apiHost."/spay/APIService.do";


//요청파라미터 JSON에 세팅
//params, data 이름은 세틀로 전달되야 하는 값이니 변경하지 마십시오.
$reqParam = array(
    "params" => $REQ_HEADER,
    "data" => $REQ_BODY
);


/** ===============================================================================
 *                          API호출(가맹점->세틀) 및 응답 처리
 *  ===============================================================================  */
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
                    throw new Exception("base64_decode() error [".$i."]".$i."[".$aesCipher."]");
                }

                $aesPlain = openssl_decrypt($cipherRaw, "AES-256-ECB",  $aesKey , OPENSSL_RAW_DATA);

                if( $aesPlain === false ){
                    throw new Exception("openssl_decrypt() error [".$i."]".$i."[".$aesCipher."]");
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
<title>결제 요청 결과</title>
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
<h2>결제 요청 결과</h2>
<div class="wrapper">
    <div class="tab">응답 파라미터</div>
    <table>
        <tr>
            <td class="left">mchtId[상점아이디]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["mchtId"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">ver[버전]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["ver"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">method[결제수단]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["method"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">bizType[업무구분]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["bizType"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">encCd[암호화구분]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["encCd"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">mchtTrdNo[상점주문번호]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["mchtTrdNo"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">trdNo[세틀뱅크 거래번호]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["trdNo"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">trdDt[요청일자]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["trdDt"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">trdTm[요청시간]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["trdTm"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">outStatCd[거래상태코드]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["outStatCd"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">outRsltCd[거래결과코드]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["outRsltCd"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">outRsltMsg[결과메세지]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["outRsltMsg"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">pktHash[해쉬값]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["pktHash"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">telCo[통신사]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["telCo"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">trdAmt[거래금액]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["trdAmt"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>
        <tr>
            <td class="left">billKey[자동결제키]</td>
            <td class="right"><?php echo htmlspecialchars($respParam["billKey"], ENT_QUOTES, "UTF-8") ?></td>
        </tr>

        <tr>
            <td colspan="2" style="text-align: center;"><input class="button" type="button" name="button" value="돌아가기" onclick="location.href='pay_form.php'"></td>
        </tr>
    </table>
</div>
</body>
</html>