<?php
include_once "config.php";
//고객 IP
function getRealClientIp() {

    $ipaddress = '';

    if (getenv('HTTP_CLIENT_IP')) {

        $ipaddress = getenv('HTTP_CLIENT_IP');

    } else if(getenv('HTTP_X_FORWARDED_FOR')) {

        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');

    } else if(getenv('HTTP_X_FORWARDED')) {

        $ipaddress = getenv('HTTP_X_FORWARDED');

    } else if(getenv('HTTP_FORWARDED_FOR')) {

        $ipaddress = getenv('HTTP_FORWARDED_FOR');

    } else if(getenv('HTTP_FORWARDED')) {

        $ipaddress = getenv('HTTP_FORWARDED');

    } else if(getenv('REMOTE_ADDR')) {

        $ipaddress = getenv('REMOTE_ADDR');

    } else {

        $ipaddress = "";

    } 

    return $ipaddress;

}
/* 파일에 로그 출력 */
function log_message($log_fname, $message) {
    date_default_timezone_set('Asia/Seoul'); //기준 시각 서울로
    $micro_date = microtime(false);
    $date_array = explode(" ", $micro_date);
    $date = date("Y-m-d H:i:s.", $date_array[1]);
    $milliseconds = substr( "000".round( $date_array[0] * 1000 ) , -3);

    $path = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $file = $path[0]['file'];
    $line = $path[0]['line'];

    /* 디렉터리 존재시 로그파일 생성 후 출력 */
    if( file_exists(LOG_DIR) && is_dir(LOG_DIR)){
        $dirname = realpath(LOG_DIR);
        $log_fname = $log_fname.".".date("Y-m-d").".log";
        $filePath = $dirname."/".$log_fname;
        error_log($date.$milliseconds."( $file : Line $line ) : ".$message."\n", 3, $filePath); 
    }
}

/* POST로 전송된 파라미터 읽는 함수 */
function get_param($name){
    if( $_SERVER['REQUEST_METHOD'] == "POST") {
        if(isset($_POST[$name])){
            $param = $_POST[$name];
            $param = prevent_xss( $param );
            return $param;
        }else{
            return NULL;
        }
    }
}

/* NULL -> "" */
function null_to_empty($data){
    if( $data == NULL){
        return "";
    }else{
        return $data;
    }
}

/* XSS 방지 */
function prevent_xss( $param){
    $param = trim( $param );
    $param = stripslashes($param);
    $param = htmlspecialchars($param);
    return $param;
}


/* CURL POST전송 */
function send_api( $targetUrl, $param = array(), $connTimeout, $timeout){
    $tmp = $param["params"];
    $mchtTrdNo = $tmp["mchtTrdNo"];
    
    log_message(LOG_FILE, "[".$mchtTrdNo."]=========================START SEND API=========================");

    $sendData = "";
    $resData = "";

    $sendData = json_encode($param);

    log_message(LOG_FILE, "[".$mchtTrdNo."][Send API] URL=".$targetUrl.", Connect Timeout=".$connTimeout." Timeout=".$timeout );
    log_message(LOG_FILE, "[".$mchtTrdNo."][Send Data] ".$sendData);

    /* curl 연결 설정 */
    $ch = curl_init();                                                  //curl 초기화
    curl_setopt($ch, CURLOPT_URL, $targetUrl);                          //URL 지정하기
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                     //요청 결과를 문자열로 반환 
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connTimeout);             //connection timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);                        //total timeout
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                    //원격 서버의 인증서가 유효한지 검사 안함
    curl_setopt($ch, CURLOPT_POST, true);                               //true시 post 전송 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $sendData);                    //POST data
    
    
    
    $resData = curl_exec($ch);                                          //curl 실행 -> Json응답데이터 저장
    
    /* 통신중 에러가 발생한 경우 */
    $curl_errno = curl_errno($ch);
    if( $curl_errno ){                                                
        $error_msg = curl_error($ch);
    }

    $HTTP_CODE = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);//curl 통신 종료

 

    try{
        /* curl 통신 에러 */
        if( isset($error_msg)){
            throw new Exception($error_msg);
        }
        /* curl 정상 통신 */
        else{
            log_message(LOG_FILE, "[".$mchtTrdNo."][Response Code] ".$HTTP_CODE);
            if( $HTTP_CODE == 200 ){

                log_message(LOG_FILE, "[".$mchtTrdNo."][Response Data] ".$resData);
            }else{
                //not 200
                log_message(LOG_FILE, "[".$mchtTrdNo."][Connect Error] ".$HTTP_CODE);
                $params = array(
                    "outStatCd" => "0099",
                    "outRsltCd" => "0099",
                    "outRsltMsg" => "[HTTP Error] code:".$HTTP_CODE
                );
                $data = array();
                $tmp = array(
                    "params" => $params,
                    "data" => $data
                );
                
                $resData = json_encode($tmp);
            }
        }

    }catch(Exception $ex){
        log_message(LOG_FILE, "[".$mchtTrdNo."][Connect Error] ".$ex->getMessage());
                    
        $params = array(
            "outStatCd" => "0099",
            "outRsltCd" => "0099",
            "outRsltMsg" => "[Connect Error]".$ex->getMessage()
        );
        $data = array();
        $tmp = array(
            "params" => $params,
            "data" => $data
        );
        
        $resData = json_encode($tmp, JSON_UNESCAPED_UNICODE);
    }

    log_message(LOG_FILE, "[".$mchtTrdNo."]=========================END SEND API=========================");
    return $resData;
}
?>