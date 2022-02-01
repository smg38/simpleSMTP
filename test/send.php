<?php

require  "../src/EmailSender.php";
//use Snipworks\Smtp\Email;
function me_send($mail,$txt)
{
    #echo "$txt\n";
    if($mail->send()){
        echo "Success!\n";
    } else {
        echo "An error occurred.\n";
        echo "\n==============\n";
        print_r($mail->getLogs());
    }
}
// Send local server #1
$flag=[true,true,true];
$flag=[false,false,true]; 
if ($flag[0]){
    $mail = new Email('127.0.0.1', 25,5,5,'user3'); //smtp.example.com@example.com
    $mail->setLogin('user3@example.com','123456' );#'MTIzNDU2'
    $mail->addTo('user1@example.com', 'Example Receiver');
    $mail->setFrom('user3@example.com', 'Example Sender');
    $mail->setSubject('Example subject');
    $mail->setHtmlMessage('<b>Example</b><i> message</i>...');
    me_send($mail,'user1@example.com');
    $mail->addTo('user2@test.com', 'Example Receiver2');
    me_send($mail,'');
}
// Test blacklist #2
if ($flag[1]){
    $mail = new Email('127.0.0.1', 25,5,5,'user'); //smtp.example.com@example.com
    #$mail->setLogin('user3@example.com','123456' );#'MTIzNDU2'
    $mail->addTo('user1@example.com', 'Example Receiver');
    $mail->setFrom('spammer@localhost', 'Example Spammer');
    $mail->setSubject('Example subject');
    $mail->setTextMessage('<b>Example</b><i> message</i>...');
    $mail->send();
    print_r($mail->getLogs());
}
// TLS connect #3
if ($flag[2]){
    require_once('config.php');
    $mail = new Email('smtp.aol.com',587 );
    $mail->setProtocol(Email::TLS);
    $mail->setLogin(SMTP_EMAIL,SMTP_PASSWORD );
    $mail->addTo(SMTP_EMAIL, 'Test Receiver');
    $mail->setFrom(SMTP_EMAIL, 'Test Sender');
    $mail->setSubject('TLS subject');
    $mail->setHtmlMessage('<b>Example TLS</b><i> message</i>...');
    $mail->send();
}
?>