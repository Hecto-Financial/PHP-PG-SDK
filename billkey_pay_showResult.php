<?php
include_once "config.php";
include_once "settleUtils.php";

/**
 * 신용카드 빌키 결제 API 결과 처리 (2회차 결제)
 * - 호출 경로: /spay/APICardActionPay.do (게이트웨이, JSON, UI 없음 / 서버 간 통신)
 * - 빌키 결제 전용 샘플입니다. 카드번호 직접 입력(수기결제)은 지원하지 않습니다.
 */

//config.php에서 설정 정보 가져오기
$aesKey = AES256_KEY;           //AES256 암복호화 키
$licenseKey = LICENSE_KEY;      //라이센스 키
$serverURL = CANCEL_SERVER;     //게이트웨이(API) 서버 = 취소/빌키결제 공통 호스트(tbgw/gw)
$connTimeout = CONN_TIMEOUT;    //connect timeout
$timeout = TIMEOUT;             //curl total timeout

//요청 파라미터(헤더)
$REQ_HEADER = array(
    "mchtId"    => null_to_empty(get_param("mchtId")),      //상점아이디
    "ver"       => null_to_empty(get_param("ver")),         //버전
    "method"    => null_to_empty(get_param("method")),      //결제수단(CA)
    "bizType"   => null_to_empty(get_param("bizType")),     //업무구분(B0)
    "encCd"     => null_to_empty(get_param("encCd")),       //암호화구분
    "mchtTrdNo" => null_to_empty(get_param("mchtTrdNo")),   //상점주문번호
    "trdDt"     => null_to_empty(get_param("trdDt")),       //요청일자
    "trdTm"     => null_to_empty(get_param("trdTm")),       //요청시간
    "mobileYn"  => null_to_empty(get_param("mobileYn")),    //모바일여부
    "osType"    => null_to_empty(get_param("osType"))       //운영체제 구분
);

//요청 파라미터(바디) - 빌키 결제 전용(카드번호 등 수기 입력 필드 없음)
$REQ_BODY = array(
    "pktHash"   => null_to_empty(get_param("pktHash")),     //해쉬값
    "pmtprdNm"  => null_to_empty(get_param("pmtprdNm")),    //상품명
    "mchtCustNm"=> null_to_empty(get_param("mchtCustNm")),  //상점고객명
    "mchtCustId"=> null_to_empty(get_param("mchtCustId")),  //상점고객아이디
    "email"     => null_to_empty(get_param("email")),       //이메일
    "billKey"   => null_to_empty(get_param("billKey")),     //빌키
    "instmtMon" => null_to_empty(get_param("instmtMon")),   //할부개월수
    "crcCd"     => null_to_empty(get_param("crcCd")),       //통화구분
    "taxTypeCd" => null_to_empty(get_param("taxTypeCd")),   //세금유형
    "trdAmt"    => null_to_empty(get_param("trdAmt")),      //거래금액
    "taxAmt"    => null_to_empty(get_param("taxAmt")),      //과세금액
    "vatAmt"    => null_to_empty(get_param("vatAmt")),      //부가세금액
    "taxFreeAmt"=> null_to_empty(get_param("taxFreeAmt")),  //비과세금액
    "svcAmt"    => null_to_empty(get_param("svcAmt")),      //봉사료
    "notiUrl"   => null_to_empty(get_param("notiUrl")),     //결과처리URL
    "mchtParam" => null_to_empty(get_param("mchtParam"))    //기타주문정보
);

//응답 파라미터(헤더)
$RES_HEADER = array(
    "mchtId" => "", "ver" => "", "method" => "", "bizType" => "", "encCd" => "",
    "mchtTrdNo" => "", "trdNo" => "", "trdDt" => "", "trdTm" => "",
    "outStatCd" => "", "outRsltCd" => "", "outRsltMsg" => ""
);

//응답 파라미터(바디)
$RES_BODY = array(
    "pktHash" => "",        //해쉬값
    "trdAmt" => "",         //거래금액
    "billKey" => "",        //빌키
    "cardNo" => "",         //마스킹된 카드번호(사전 협의 가맹점 한정)
    "vldDtMon" => "",       //유효기간(월)
    "vldDtYear" => "",      //유효기간(년)
    "issrId" => "",         //발급사아이디
    "cardNm" => "",         //카드사명
    "cardKind" => "",       //카드종류명
    "ninstmtTypeCd" => "",  //무이자할부타입
    "instmtMon" => "",      //할부개월수
    "apprNo" => ""          //승인번호
);

//AES256 암호화 필요 파라미터(요청) - 금액 항목만
$ENCRYPT_PARAMS = array("trdAmt", "taxAmt", "vatAmt", "taxFreeAmt", "svcAmt");

//AES256 복호화 필요 파라미터(응답)
$DECRYPT_PARAMS = array("trdAmt");

/** ===============================================================================================
 *                          SHA256 해쉬 처리
 *  조합필드 : 거래일자 + 거래시간 + 상점아이디 + 상점거래번호 + 거래금액(평문) + 라이센스키
 *  ===============================================================================================   */
$hashPlain = "";
$hashCipher = "";
try{
    $hashPlain = $REQ_HEADER["trdDt"].$REQ_HEADER["trdTm"].$REQ_HEADER["mchtId"].$REQ_HEADER["mchtTrdNo"].$REQ_BODY["trdAmt"].$licenseKey;
    $hashCipher = hash("sha256", $hashPlain);
}catch(Exception $ex){
    log_message(LOG_FILE, "[".$REQ_HEADER["mchtTrdNo"]."][SHA256 HASHING] Hashing Fail! : ".$ex->getMessage());
}finally{
    log_message(LOG_FILE, "[".$REQ_HEADER["mchtTrdNo"]."][SHA256 HASHING] Plain Text[".$hashPlain."] ---> Cipher Text[".$hashCipher."]");
    $REQ_BODY["pktHash"] = $hashCipher;
}

/** AES256 암호화 처리 */
try{
    foreach($ENCRYPT_PARAMS as $i){
        $aesPlain = $REQ_BODY[$i];
        if( !( "" == $aesPlain )){
            $chiperRaw = openssl_encrypt($aesPlain, "AES-256-ECB", $aesKey, OPENSSL_RAW_DATA);
            $REQ_BODY[$i] = base64_encode($chiperRaw);
        }
    }
}catch(Exception $ex){
    log_message(LOG_FILE, "[".$REQ_HEADER["mchtTrdNo"]."][AES256 Encrypt] AES256 Fail! : ".$ex->getMessage());
    throw new Exception("aes256 encrypt fail");
}

//URL설정
$requestUrl = $serverURL."/spay/APICardActionPay.do";

//요청파라미터 JSON에 세팅 (params, data 이름은 변경 금지)
$reqParam = array(
    "params" => $REQ_HEADER,
    "data" => $REQ_BODY
);

/** API호출(가맹점->헥토파이낸셜) 및 응답 처리 */
$respParam = array();
$resData = send_api($requestUrl, $reqParam, $connTimeout, $timeout);

//응답 파라미터 파싱
$resData = json_decode($resData, true);
$respHeader = (is_array($resData) && array_key_exists('params', $resData)) ? $resData['params'] : null;
$respBody   = (is_array($resData) && array_key_exists('data', $resData)) ? $resData['data'] : null;

if( $respHeader != null ){
    foreach ($RES_HEADER as $key => $val ) {
        $respParam[$key] = null_to_empty( array_key_exists($key, $respHeader) ? $respHeader[$key] : "" );
    }
}else{
    foreach ($RES_HEADER as $key => $val ) { $respParam[$key] = ""; }
}

if( $respBody != null){
    foreach ($RES_BODY as $key => $val ) {
        $respParam[$key] = null_to_empty( array_key_exists($key, $respBody) ? $respBody[$key] : "" );
    }
}else{
    foreach ($RES_BODY as $key => $val ) { $respParam[$key] = ""; }
}

/** AES256 복호화 처리 */
try{
    foreach($DECRYPT_PARAMS as $i){
        if( array_key_exists($i, $respParam)){
            $aesCipher = trim($respParam[$i]);
            if( "" != $aesCipher ){
                $cipherRaw = base64_decode($aesCipher);
                if( $cipherRaw === false ){
                    throw new Exception("base64_decode() error ".$i."[".$aesCipher."]");
                }
                $aesPlain = openssl_decrypt($cipherRaw, "AES-256-ECB", $aesKey, OPENSSL_RAW_DATA);
                if( $aesPlain === false ){
                    throw new Exception("openssl_decrypt() error ".$i."[".$aesCipher."]");
                }
                $respParam[$i] = $aesPlain;
            }
        }
    }
}catch(Exception $ex){
    log_message(LOG_FILE, "[".$REQ_HEADER["mchtTrdNo"]."][AES256 Decrypt] AES256 Decrypt Fail! : ".$ex->getMessage());
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>헥토파이낸셜</title>
<style type="text/css">
#STPG_RSLT		{font-family:굴림; font-size:10pt;}
#STPG_RSLT h4	{background-color:#f1f1f1;padding:4px;margin:2px;}
#STPG_RSLT td   {padding:3px; border:1px solid #e1e1e1;}
#STPG_RSLT table{border-collapse:collapse;}
</style>
</head>
<body>
<h3>빌키 결제 응답 결과</h3>
<div id="STPG_RSLT">
    <table>
        <tr><td colspan="2" style="text-align:center;"><h4>params</h4></td></tr>
        <tr><td>mchtId[상점아이디]</td><td><?php echo htmlspecialchars($respParam["mchtId"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>ver[버전]</td><td><?php echo htmlspecialchars($respParam["ver"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>method[결제수단]</td><td><?php echo htmlspecialchars($respParam["method"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>bizType[업무구분]</td><td><?php echo htmlspecialchars($respParam["bizType"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>encCd[암호화구분]</td><td><?php echo htmlspecialchars($respParam["encCd"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>mchtTrdNo[상점주문번호]</td><td><?php echo htmlspecialchars($respParam["mchtTrdNo"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>trdNo[헥토파이낸셜 거래번호]</td><td><?php echo htmlspecialchars($respParam["trdNo"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>trdDt[요청일자]</td><td><?php echo htmlspecialchars($respParam["trdDt"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>trdTm[요청시간]</td><td><?php echo htmlspecialchars($respParam["trdTm"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>outStatCd[거래상태코드]</td><td><?php echo htmlspecialchars($respParam["outStatCd"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>outRsltCd[거래결과코드]</td><td><?php echo htmlspecialchars($respParam["outRsltCd"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>outRsltMsg[결과메세지]</td><td><?php echo htmlspecialchars($respParam["outRsltMsg"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td colspan="2" style="text-align:center;"><h4>data</h4></td></tr>
        <tr><td>pktHash[해쉬값]</td><td><?php echo htmlspecialchars($respParam["pktHash"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>trdAmt[거래금액]</td><td><?php echo htmlspecialchars($respParam["trdAmt"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>cardNo[카드번호(마스킹)]</td><td><?php echo htmlspecialchars($respParam["cardNo"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>vldDtYear[유효기간(년)]</td><td><?php echo htmlspecialchars($respParam["vldDtYear"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>vldDtMon[유효기간(월)]</td><td><?php echo htmlspecialchars($respParam["vldDtMon"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>issrId[발급사아이디]</td><td><?php echo htmlspecialchars($respParam["issrId"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>cardNm[카드사명]</td><td><?php echo htmlspecialchars($respParam["cardNm"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>cardKind[카드종류명]</td><td><?php echo htmlspecialchars($respParam["cardKind"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>ninstmtTypeCd[무이자할부타입]</td><td><?php echo htmlspecialchars($respParam["ninstmtTypeCd"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>instmtMon[할부개월수]</td><td><?php echo htmlspecialchars($respParam["instmtMon"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr><td>apprNo[승인번호]</td><td><?php echo htmlspecialchars($respParam["apprNo"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr style="background-color:yellow;"><td>billKey[빌키]</td><td><?php echo htmlspecialchars($respParam["billKey"], ENT_QUOTES, "UTF-8") ?></td></tr>
        <tr>
            <td colspan="2" style="text-align:center;">
                <input type="button" value="돌아가기" style="margin-top:20px;" onclick="location.href='billkey_pay_form.php'">
            </td>
        </tr>
    </table>
</div>
</body>
</html>
