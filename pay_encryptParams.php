<?php
header('Content-Type:application/json');
include_once "config.php";
include_once "settleUtils.php";

/** 설정 정보 얻기 */
$licenseKey = LICENSE_KEY;
$aesKey = AES256_KEY;

/** 해쉬 및 aes256암호화 후 리턴 될 json */
$rsp = array();

/** SHA256 해쉬 파라미터 */
$mchtId     = null_to_empty(get_param("mchtId"));
$method     = null_to_empty(get_param("method"));
$mchtTrdNo  = null_to_empty(get_param("mchtTrdNo"));
$trdDt      = null_to_empty(get_param("trdDt"));
$trdTm      = null_to_empty(get_param("trdTm"));
$trdAmt     = null_to_empty(get_param("plainTrdAmt"));

/** AES256 암호화 파라미터 */
$params = array(
    "trdAmt"            => $trdAmt,
    "mchtCustNm"        => null_to_empty(get_param("plainMchtCustNm")),
    "cphoneNo"          => null_to_empty(get_param("plainCphoneNo")),
    "email"             => null_to_empty(get_param("plainEmail")),
    "mchtCustId"        => null_to_empty(get_param("plainMchtCustId")),
    "taxAmt"            => null_to_empty(get_param("plainTaxAmt")),
    "vatAmt"            => null_to_empty(get_param("plainVatAmt")),
    "taxFreeAmt"        => null_to_empty(get_param("plainTaxFreeAmt")),
    "svcAmt"            => null_to_empty(get_param("plainSvcAmt")),
    "clipCustNm"        => null_to_empty(get_param("plainClipCustNm")),
    "clipCustCi"        => null_to_empty(get_param("plainClipCustCi")),
    "clipCustPhoneNo"   => null_to_empty(get_param("plainClipCustPhoneNo"))
);



/*============================================================================================================================================
 *  SHA256 해쉬 처리
 *조합 필드 : 상점아이디 + 결제수단 + 상점주문번호 + 요청일자 + 요청시간 + 거래금액(평문) + 라이센스키
 *============================================================================================================================================*/
$hashPlain = $mchtId.$method.$mchtTrdNo.$trdDt.$trdTm.$trdAmt.$licenseKey;
$hashCipher ="";
/** SHA256 해쉬 처리, finally 구분은 PHP ver 5.5 부터 적용 */
try{
    $hashCipher = hash("sha256", $hashPlain);//해쉬 값
}catch(Exception $ex){
    log_message(LOG_FILE, "[".$mchtTrdNo."][SHA256 HASHING] Hashing Fail! : ".$ex->getMessage());
    throw new Exception("Error occurred during hashing!");
}finally{
    log_message(LOG_FILE, "[".$mchtTrdNo."][SHA256 HASHING] Plain Text[".$hashPlain."] ---> Cipher Text[".$hashCipher."]");
    $rsp["hashCipher"] = $hashCipher; // sha256 해쉬 결과 저장
}



/*============================================================================================================================================
 *  AES256 암호화 처리(AES-256-ECB encrypt -> Base64 encoding)
 *============================================================================================================================================ */
try{
    foreach ($params as $key => $value) {
        
        $aesPlain = $params[$key];
        if( !( "" == $aesPlain )){
            $chiperRaw = openssl_encrypt($aesPlain, "AES-256-ECB",  $aesKey , OPENSSL_RAW_DATA);
            $aesCipher = base64_encode($chiperRaw);

            $params[$key] = $aesCipher;//암호화된 데이터로 세팅
            log_message(LOG_FILE, "[".$mchtTrdNo."][AES256 Encrypt] ".$key."[".$aesPlain."] ---> [".$aesCipher."]");
        }
    }

}catch(Exception $ex){
    log_message(LOG_FILE, "[".$mchtTrdNo."][AES256 Encrypt] AES256 Fail! : ".$ex->getMessage());
    throw new Exception("aes256 encrypt fail");
}finally{
    $rsp["encParams"] = $params;//aes256 암호화 결과 저장
}
/* 결과 리턴 */
echo json_encode($rsp);













?>