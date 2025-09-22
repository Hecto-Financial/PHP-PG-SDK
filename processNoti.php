<?php

    /** 노티를 성공적으로 수신한 경우 처리할 로직을 작성하여 주세요. */ 
    function noti_success($noti){
        /* TODO : 관련 로직 추가 */
        
        return true;
    }
    
    /** 입금대기시 처리할 로직을 작성하여 주세요. */
    function noti_waiting_pay($noti){
        /* TODO : 관련 로직 추가 */
        
        return true;
    }   
    
    /** 노티 수신중 해시 체크 에러가 생긴 경우 처리할 로직을 작성하여 주세요. */
    function noti_hash_error($noti){
        /* TODO : 관련 로직 추가 */
        
        return false;
    }
?>