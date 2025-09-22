<?php
include_once "settleUtils.php";
include_once "processNoti.php";

/** 이 페이지는 수정시 주의가 필요합니다. 수정시 html태그나 자바스크립트가 들어가는 경우 동작을 보장할 수 없습니다 */

/** 설정 정보 저장 */
$licenseKey = LICENSE_KEY;

/** 노티 처리 결과 */
$resp=false;

/** 노티 수신 파라미터 */
$outStatCd          = null_to_empty(get_param("outStatCd"));
$trdNo              = null_to_empty(get_param("trdNo"));
$method             = null_to_empty(get_param("method"));
$bizType            = null_to_empty(get_param("bizType"));
$mchtId             = null_to_empty(get_param("mchtId"));
$mchtTrdNo          = null_to_empty(get_param("mchtTrdNo"));
$mchtCustNm         = null_to_empty(get_param("mchtCustNm"));
$mchtName           = null_to_empty(get_param("mchtName"));
$pmtprdNm           = null_to_empty(get_param("pmtprdNm"));
$trdDtm             = null_to_empty(get_param("trdDtm"));
$trdAmt             = null_to_empty(get_param("trdAmt"));
$billKey            = null_to_empty(get_param("billKey"));
$billKeyExpireDt    = null_to_empty(get_param("billKeyExpireDt"));
$bankCd             = null_to_empty(get_param("bankCd"));
$bankNm             = null_to_empty(get_param("bankNm"));
$cardCd             = null_to_empty(get_param("cardCd"));
$cardNm             = null_to_empty(get_param("cardNm"));
$telecomCd          = null_to_empty(get_param("telecomCd"));
$telecomNm          = null_to_empty(get_param("telecomNm"));
$vAcntNo            = null_to_empty(get_param("vAcntNo"));
$expireDt           = null_to_empty(get_param("expireDt"));
$AcntPrintNm        = null_to_empty(get_param("AcntPrintNm"));
$dpstrNm            = null_to_empty(get_param("dpstrNm"));
$email              = null_to_empty(get_param("email"));
$mchtCustId         = null_to_empty(get_param("mchtCustId"));
$cardNo             = null_to_empty(get_param("cardNo"));
$cardApprNo         = null_to_empty(get_param("cardApprNo"));
$instmtMon          = null_to_empty(get_param("instmtMon"));
$instmtType         = null_to_empty(get_param("instmtType"));
$phoneNoEnc         = null_to_empty(get_param("phoneNoEnc"));
$orgTrdNo           = null_to_empty(get_param("orgTrdNo"));
$orgTrdDt           = null_to_empty(get_param("orgTrdDt"));
$mixTrdNo           = null_to_empty(get_param("mixTrdNo"));
$mixTrdAmt          = null_to_empty(get_param("mixTrdAmt"));
$payAmt             = null_to_empty(get_param("payAmt"));
$csrcIssNo          = null_to_empty(get_param("csrcIssNo"));
$cnclType           = null_to_empty(get_param("cnclType"));
$mchtParam          = null_to_empty(get_param("mchtParam"));
$acntType            = null_to_empty(get_param("acntType"));
$kkmAmt            = null_to_empty(get_param("kkmAmt"));
$coupAmt            = null_to_empty(get_param("coupAmt"));
$pktHash            = null_to_empty(get_param("pktHash"));

/* 응답 파라미터 Array 에 저장 */
$noti = array(
    "거래상태:".$outStatCd,
    "거래번호:".$trdNo,
    "결제수단:".$method,
    "업무구분:".$bizType,
    "상점아이디:".$mchtId,
    "상점주문번호:".$mchtTrdNo,
    "고객명:".$mchtCustNm,
    "상점한글명:".$mchtName,
    "상품명:".$pmtprdNm,
    "거래일시:".$trdDtm,
    "거래금액:".$trdAmt,
    "자동결제키:".$billKey,
    "자동결제키 유효기간:".$billKeyExpireDt,
    "은행코드:".$bankCd,
    "은행명:".$bankNm,
    "카드사코드:".$cardCd,
    "카드명:".$cardNm,
    "이통사코드:".$telecomCd,
    "이통사명:".$telecomNm,
    "가상계좌번호:".$vAcntNo,
    "가상계좌 입금만료일시:".$expireDt,
    "통장인자명:".$AcntPrintNm,
    "입금자명:".$dpstrNm,
    "고객이메일:".$email,
    "상점고객아이디:".$mchtCustId,
    "카드번호:".$cardNo,
    "카드승인번호:".$cardApprNo,
    "할부개월수:".$instmtMon,
    "할부타입:".$instmtType,
    "휴대폰번호(암호화):".$phoneNoEnc,
    "원거래번호:".$orgTrdNo,
    "원거래일자:".$orgTrdDt,
    "복합결제 거래번호:".$mixTrdNo,
    "복합결제 금액:".$mixTrdAmt,
    "실결제금액:".$payAmt,
    "현금영수증 승인번호:".$csrcIssNo,
    "취소거래타입:".$cnclType,
    "기타주문정보:".$mchtParam,
    "계좌구분:".$acntType,
    "카카오머니금액:".$kkmAmt,
    "쿠폰금액:".$coupAmt,
    "해시값:".$pktHash
);

/** 해쉬 조합 필드 
 *  결과코드 +  거래일시 + 상점아이디 + 가맹점거래번호 + 거래금액 + 라이센스키 */
$hashPlain = $outStatCd.$trdDtm.$mchtId.$mchtTrdNo.$trdAmt.$licenseKey;
$hashCipher ="";

/** SHA256 해쉬 처리 */
try{
    $hashCipher = hash("sha256", $hashPlain);//해쉬 값
}catch(Exception $ex){
    log_message(NOTI_LOG_FILE,"[".$mchtTrdNo."][SHA256 HASHING] Hashing Fail! : " . $ex->getMessage());
}finally{
    log_message(NOTI_LOG_FILE,"[".$mchtTrdNo."][SHA256 HASHING] Plain Text[".$hashPlain."] ---> Cipher Text[".$hashCipher."]");
}


/**
 * hash데이타값이 맞는 지 확인 하는 루틴은 세틀뱅크에서 받은 데이타가 맞는지 확인하는 것이므로 꼭 사용하셔야 합니다
 * 정상적인 결제 건임에도 불구하고 노티 페이지의 오류나 네트웍 문제 등으로 인한 hash 값의 오류가 발생할 수도 있습니다.
 * 그러므로 hash 오류건에 대해서는 오류 발생시 원인을 파악하여 즉시 수정 및 대처해 주셔야 합니다. 
 * 그리고 정상적으로 데이터를 처리한 경우에도 세틀뱅크에서 응답을 받지 못한 경우는 결제결과가 중복해서 나갈 수 있으므로 관련한 처리도 고려되어야 합니다
*/
if ($hashCipher == $pktHash) {
    log_message(NOTI_LOG_FILE, "[".$mchtTrdNo."][SHA256 Hash Check] hashCipher[".$hashCipher."] pktHash[".$pktHash."] equals?[TRUE]");
    if ("0021" == $outStatCd ){
        log_message(NOTI_LOG_FILE, "[".$mchtTrdNo."][Success] params:".join("|", $noti));
        $resp = noti_success($noti); 
    }
    else if ("0051" == $outStatCd ){
        log_message(NOTI_LOG_FILE, "[".$mchtTrdNo."][Wait For Deposit] params:".join("|", $noti));
        $resp = noti_Waiting_pay($noti);
    }
    else{
        log_message(NOTI_LOG_FILE, "[".$mchtTrdNo."][Undefined Code] outStatCd:".$outStatCd);
        $resp = false;
    }
}
else {
    log_message(NOTI_LOG_FILE, "[".$mchtTrdNo."][SHA256 Hash Check] hashCipher[".$hashCipher."] pktHash[".$pktHash."] equals?[FALSE]");
    $resp = noti_hash_error($noti);
} 

// OK, FAIL문자열은 세틀뱅크로 전송되어야 하는 값이므로 변경하거나 삭제하지마십시오.
if ($resp){
    echo "OK";
    log_message(NOTI_LOG_FILE, "[".$mchtTrdNo."][Result] OK");
}
else{
    echo "FAIL";
    log_message(NOTI_LOG_FILE, "[".$mchtTrdNo."][Result] FAIL");
}
?>




