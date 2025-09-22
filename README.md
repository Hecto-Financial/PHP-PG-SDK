# PHP-PG-SDK

세틀뱅크 PG 연동을 위한 PHP 샘플 코드입니다.

## 파일 구조

```
/(Project Root Directory)
     |   
    │  index.html			<--- index페이지
    │  config.php			<--- 기본정보 설정파일(*자사에 맞게 변경 필요)
     |   settleUtils.php		<--- 유틸성 함수가 선언된 페이지
     |   
    │  pay_form.php		<--- 결제시 메인 폼
    │  pay_encryptParams.php	<--- 결제시 파라미터 암호화 및 해쉬 처리 페이지
     |   pay_autoPayResult.php	<--- 휴대폰 자동연장결제시 사용되는 페이지
    │  pay_receiveResult.php	<--- 결제 완료 후 응답파라미터 수신페이지
    │  pay_showResult.php		<--- 자식페이지에서 전달된 응답파라미터 출력
     |   
    │  cancel_form.php		<--- 취소 메인 폼
    │  cancel_showResult.php	<--- 취소 처리 및 결과 화면
     |   
    │  receiveNoti.php		<--- 결제 완료 후 노티 수신 페이지
    │  processNoti.php		<--- 노티 수신 후 처리하는 페이지
     |   
```

## 파일 설명

### 공통 페이지
- **config.php**: 상점아이디, 암복호화키 등을 설정할 수 있는 설정파일입니다.
- **settleUtils.php**: 유틸성 함수가 선언된 페이지입니다. PHP에 curl패키지와 openssl 패키지가 설치되어 있어야 정상 작동합니다.
- **receiveNoti.php**: 결제 또는 취소 처리가 완료된 후, 세틀뱅크가 가맹점으로 전달하는 노티(결과통보)를 수신하는 페이지입니다.
- **processNoti.php**: receiveNoti.php에서 결제 또는 취소의 성공/실패에 따라 적절한 로직을 수행하는 메소드를 정의한 파일입니다.

### 결제 관련 페이지
- **pay_form.php**: 결제 요청시 사용자로부터 정보를 입력받는 Form 페이지입니다. 결제는 Form POST방식으로 처리됩니다.
- **pay_encryptParams.php**: pay_form.php에서 암호화가 필요한 파라미터들을 ajax통신으로 암호화 하는 페이지입니다. 또한 sha256해쉬 처리도 수행합니다. PHP에 curl패키지와 openssl 패키지가 설치되어 있어야 정상 작동합니다.
- **pay_receiveResult.php**: 결제창에서 결제가 완료된 이후 닫기 버튼을 누를때, 세틀뱅크로 부터 응답파라미터를 수신하는 페이지입니다.
- **pay_showResult.php**: pay_receiveResult.php에서 받은 파라미터를 부모창으로 전송할 수 있는데, 이때 전송된 파라미터들을 수신하여 출력하는 페이지입니다.
- **pay_autoPayResult.php**: 휴대폰 자동연장 결제시 사용되는 결제 및 결과화면 페이지입니다.

### 취소 관련 페이지
- **cancel_form.php**: 취소 요청시 사용자로부터 정보를 입력받는 Form 페이지입니다.
- **cancel_showResult.php**: 세틀뱅크와 Server to Server 로 커넥션하여, 취소 요청을 하고 응답을 받아 결과를 출력하는 페이지입니다.

## 프로세스 처리 순서

- **결제 처리 순서**: pay_form.php -> pay_encryptParams.php -> pay_receiveResult.php -> pay_showResult.php
- **휴대폰 자동연장 결제**: pay_form.php -> pay_autoPayResult.php
- **취소 처리 순서**: cancel_form.php -> cancel_showResult.php
- **노티 처리 순서**: receiveNoti.php -> processNoti.php

## config.php 설정파일 변수 설명

- **PG_MID**: 상점아이디. 테스트환경에서의 상점아이디는 샘플소스에 기재되어있습니다. 상용테스트시에는 세틀뱅크에서 발급한 MID로 설정하셔야 합니다. 이 값은 외부에 노출되어서는 안됩니다.
- **LICENSE_KEY**: MID당 하나의 라이센스키가 발급됩니다. SHA256해쉬체크 용도로 사용됩니다. 이 값은 외부에 노출되어서는 안됩니다.
- **AES256_KEY**: 개인정보/민감정보를 암복호화 하는데 사용되는 키로서, 외부에 노출되어서는 안됩니다.
- **PAYMENT_SERVER**: 세틀뱅크 결제 처리 서버의 URL입니다. 변경하지 마십시오.
- **CANCEL_SERVER**: 세틀뱅크 취소 처리 서버의 URL입니다. 변경하지 마십시오.
- **CONN_TIMEOUT**: 세틀뱅크 API통신 curl연결 타임아웃입니다.
- **TIMEOUT**: 세틀뱅크 API통신 curl전체 타임아웃입니다.
- **LOG_DIR**: 로그파일을 남길 디렉터리입니다. 디렉터리가 존재해야 로그파일이 생성됩니다.
- **LOG_FILE**: 결제 또는 취소 거래에 대한 로그를 남길 파일명입니다.
- **NOTI_LOG_FILE**: 노티 처리에 대한 로그를 남길 파일명입니다.

## 노티 수신 페이지

- **파일명**: receiveNoti.php
- 결제 또는 취소 완료 후 세틀 서버에서 콜백으로 호출하게 되는 페이지이며, 세틀에서 가맹점으로 노티를 전송하게 됩니다.
- nextUrl(결과페이지)에서는 성공/실패에 대한 결과 화면을 고객에게 리턴하여 주시고,
- notiUrl(노티수신페이지)에서는 가맹점의 실제 내부데이터, DB를 처리하시면 됩니다.
