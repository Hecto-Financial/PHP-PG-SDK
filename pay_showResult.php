<?php
    include_once "config.php";
    include_once "settleUtils.php";

    /** 넘어온 응답 파라미터 받기 */
    $mchtId             = null_to_empty(get_param("respMchtId"));           //상점아이디
    $outStatCd          = null_to_empty(get_param("respOutStatCd"));        //결과코드
    $outRsltCd          = null_to_empty(get_param("respOutRsltCd"));        //거절코드
    $outRsltMsg         = null_to_empty(get_param("respOutRsltMsg"));       //결과메세지
    $method             = null_to_empty(get_param("respMethod"));           //결제수단
    $mchtTrdNo          = null_to_empty(get_param("respMchtTrdNo"));        //상점주문번호
    $mchtCustId         = null_to_empty(get_param("respMchtCustId"));       //상점고객아이디
    $trdNo              = null_to_empty(get_param("respTrdNo"));            //세틀뱅크 거래번호
    $trdAmt             = null_to_empty(get_param("respTrdAmt"));           //거래금액
    $mchtParam          = null_to_empty(get_param("respMchtParam"));        //상점예약필드
    $authDt             = null_to_empty(get_param("respAuthDt"));           //승인일시
    $authNo             = null_to_empty(get_param("respAuthNo"));           //승인번호
    $reqIssueDt         = null_to_empty(get_param("respReqIssueDt"));       //채번요청일시
    $intMon             = null_to_empty(get_param("respIntMon"));           //할부개월수
    $fnNm               = null_to_empty(get_param("respFnNm"));             //카드사명
    $fnCd               = null_to_empty(get_param("respFnCd"));             //카드사코드
    $pointTrdNo         = null_to_empty(get_param("respPointTrdNo"));       //포인트거래번호
    $pointTrdAmt        = null_to_empty(get_param("respPointTrdAmt"));      //포인트거래금액
    $cardTrdAmt         = null_to_empty(get_param("respCardTrdAmt"));       //신용카드결제금액
    $vtlAcntNo          = null_to_empty(get_param("respVtlAcntNo"));        //가상계좌번호
    $expireDt           = null_to_empty(get_param("respExpireDt"));         //입금기한
    $cphoneNo           = null_to_empty(get_param("respCphoneNo"));         //휴대폰번호
    $billKey            = null_to_empty(get_param("respBillKey"));          //자동결제키
    $csrcAmt            = null_to_empty(get_param("respCsrcAmt"));          //현금영수증 발급 금액(네이버페이)
?>
<html>
<head>
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>헥토파이낸셜 PG 결제 샘플</title>
<style type="text/css">
    body            {font-family:굴림; font-size:10pt; color:#000000; text-decoration:none;}
    font            {font-family:굴림; font-size:10pt; color:#000000; text-decoration:none;}
    td              {font-family:굴림; font-size:10pt; color:#000000; text-decoration:none; padding:3px; border:1px solid #e1e1e1;}
    table           {width:100%; border-collapse:collapse;}
    .left           {padding-left:5px; width:200px;}
    .right          {padding-left:5px;}
    .wrapper        {width:700px;border:1px solid #e1e1e1;}
    .tab            {background-color:#f1f1f1;padding:10px 20px;border:1px solid #e1e1e1; font-weight: bold; font-size:1.1em;}
    .button         {padding:5px 20px; border-radius:20px; border:1px solid #ccc; width:70%; margin:5px 0px; transition:0.3s; cursor:pointer;}
    .button:hover   {background-color:#aaaaaa;}
</style>
</head>
<body>
<h2>승인 요청 결과</h2>
<div class="wrapper">
    <div class="tab">응답 파라미터</div>
    <table>
        <tr>
            <td class="left">mchtId[상점아이디]</td>
            <td class="right"><?php echo $mchtId ?></td>
        </tr>
        <tr>
            <td class="left">outStatCd[거래상태]</td>
            <td class="right"><?php echo $outStatCd ?></td>
        </tr>
        <tr>
            <td class="left">outRsltCd[거절코드]</td>
            <td class="right"><?php echo $outRsltCd ?></td>
        </tr>
        <tr>
            <td class="left">outRsltMsg[메세지]</td>
            <td class="right"><?php echo $outRsltMsg ?></td>
        </tr>
        <tr>
            <td class="left">method[결제수단]</td>
            <td class="right"><?php echo $method ?></td>
        </tr>
        <tr>
            <td class="left">mchtTrdNo[상점주문번호]</td>
            <td class="right"><?php echo $mchtTrdNo ?></td>
        </tr>
        <tr>
            <td class="left">mchtCustId[상점고객아이디]</td>
            <td class="right"><?php echo $mchtCustId ?></td>
        </tr>
        <tr>
            <td class="left">trdNo[세틀뱅크거래번호]</td>
            <td class="right"><?php echo $trdNo ?></td>
        </tr>
        <tr>
            <td class="left">trdAmt[거래금액]</td>
            <td class="right"><?php echo $trdAmt ?></td>
        </tr>
        <tr>
            <td class="left">mchtParam[상점예약필드]</td>
            <td class="right"><?php echo $mchtParam ?></td>
        </tr>

        <tr>
            <td class="left">authDt[승인일시]</td>
            <td class="right"><?php echo $authDt ?></td>
        </tr>
        <tr>
            <td class="left">authNo[승인번호]</td>
            <td class="right"><?php echo $authNo ?></td>
        </tr>
        <tr>
            <td class="left">reqIssueDt[채번요청일시]</td>
            <td class="right"><?php echo $reqIssueDt ?></td>
        </tr>
        <tr>
            <td class="left">intMon[할부개월수]</td>
            <td class="right"><?php echo $intMon ?></td>
        </tr>
        <tr>
            <td class="left">fnNm[카드사명]</td>
            <td class="right"><?php echo $fnNm ?></td>
        </tr>
        <tr>
            <td class="left">fnCd[카드사코드]</td>
            <td class="right"><?php echo $fnCd ?></td>
        </tr>
        <tr>
            <td class="left">pointTrdNo[포인트거래번호]</td>
            <td class="right"><?php echo $pointTrdNo ?></td>
        </tr>
        <tr>
            <td class="left">pointTrdAmt[포인트거래금액]</td>
            <td class="right"><?php echo $pointTrdAmt ?></td>
        </tr>
        <tr>
            <td class="left">cardTrdAmt[신용카드결제금액]</td>
            <td class="right"><?php echo $cardTrdAmt ?></td>
        </tr>
        <tr>
            <td class="left">vtlAcntNo[가상계좌번호]</td>
            <td class="right"><?php echo $vtlAcntNo ?></td>
        </tr>
        <tr>
            <td class="left">expireDt[입금기한]</td>
            <td class="right"><?php echo $expireDt ?></td>
        </tr>
        <tr>
            <td class="left">cphoneNo[휴대폰번호]</td>
            <td class="right"><?php echo $cphoneNo ?></td>
        </tr>
        <tr>
            <td class="left">billKey[자동결제키]</td>
            <td class="right"><?php echo $billKey ?></td>
        </tr>
        <tr>
            <td class="left">csrcAmt[현금영수증 발급 금액(네이버페이)]</td>
            <td class="right"><?php echo $csrcAmt ?></td>
        </tr>

        <tr>
            <td colspan="2" style="text-align: center;">
                <input class="button" type="button" name="button" value="돌아가기" onclick="location.href='pay_form.php'">
            </td>
        </tr>
    </table>
</div>
</body>
</html>