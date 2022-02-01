PHP simple SMTP services
=================

An easy to use SMTP (Simple Mail Transfer Protocol) library which helps you to send and receive emails.

`server.php` - A simple SMTP server. TLS is not implemented.
`config.json` - Configuring file for SMTP server.

## Examples
One terminal as server:

`user@ubuntu:~/proj/SMTP$ sudo php server.php`

Two terninal as client:

`user@ubuntu:~/proj/SMTP/test$ php send.php`

First task on server terminal:

```bash
Initiating...
Loaded 4 addresses from config.
SMTP server. Listening on <0.0.0.0:25>...
#1 
#1 ~ connected
#1 <. 220 You have accessed some server
#1 .> EHLO user3
#1 <. 250-some.server
#1 <. 250-SIZE 10240000
#1 <. 250-VRFY
#1 <. 250 AUTH LOGIN
#1 .> AUTH LOGIN
#1 <. 334 VXNlcm5hbWU6
#1 .> dXNlcjNAZXhhbXBsZS5jb20=
#1 <. 334 UGFzc3dvcmQ6
#1 .> MTIzNDU2
#1 <. 235 Authenticated as <user3@example.com>
#1 .> MAIL FROM: <user3@example.com>
#1 <. 250 OK
#1 .> RCPT TO: <user1@example.com>
#1 <. 250 OK
#1 .> DATA
#1 <. 354 End data with <CR><LF>.<CR><LF>
#1 .> X-Mailer: PHP/8.0.8
#1 .> MIME-Version: 1.0
#1 .> Date: Tue, 01 Feb 2022 17:41:04 +0300
#1 .> Subject: Example subject
#1 .> From: "Example Sender" <user3@example.com>
#1 .> Return-Path: "Example Sender" <user3@example.com>
#1 .> To: "Example Receiver" <user1@example.com>
#1 .> Content-Type: multipart/alternative; boundary="alt-bd21a647286fc3459c684525ea28ae98"
#1 .> --alt-bd21a647286fc3459c684525ea28ae98
#1 .> Content-Type: text/html; charset=utf-8
#1 .> Content-Transfer-Encoding: base64
#1 .> PGI+RXhhbXBsZTwvYj48aT4gbWVzc2FnZTwvaT4uLi4=
#1 .> --alt-bd21a647286fc3459c684525ea28ae98--
#1 .> .
#1 <. 250 OK
#1 .> QUIT
#1 <. 221 Bye
#1 ~ disconnected
```
First task on client terminal:
``` 
Success!
Success!
```
Second task on server terminal:
```bash 
#1 
#1 ~ connected
#1 <. 220 You have accessed some server
#1 .> EHLO user
#1 <. 250-some.server
#1 <. 250-SIZE 10240000
#1 <. 250-VRFY
#1 <. 250 AUTH LOGIN
#1 .> MAIL FROM: <spammer@localhost>
#1 <. 552 You can go right away
#1 ~ disconnected
```
Second task on client terminal:
```json
Array
(
    [CREATE_OBJECT] => 127.0.0.1:25 Host:user
    [CONNECTION] => 220 You have accessed some server Server=>127.0.0.1:25
    [HELLO] => Array
        (
            [1] =>  250-some.server
                    250-SIZE 10240000
                    250-VRFY
                    250 AUTH LOGIN
        )
    [MAIL_FROM] => 552 You can go right away
)
```

It is not recommended to hard-code SMTP login credentials, as in the examples above.
It is recommended to put them in another file and download it or set it to an environment variable. For example, as in task #3 
The third task is only on the client terminal:
```json
Array
(
    [CREATE_OBJECT] => smtp.aol.com:587 Host:smgdell
    [CONNECTION] => 220 smtp.mail.yahoo.com ESMTP ready Server=>tcp://smtp.aol.com:587
    [HELLO] => Array
        (
            [1] =>  250-kubenode518.mail-prod1.omega.ir2.yahoo.com Hello smgdell [185.160.38.18])
                    250-PIPELINING
                    250-ENHANCEDSTATUSCODES
                    250-8BITMIME
                    250-SIZE 41697280
                    250 STARTTLS
            [2] =>  250-kubenode518.mail-prod1.omega.ir2.yahoo.com Hello smgdell [185.160.38.18])
                    250-PIPELINING
                    250-ENHANCEDSTATUSCODES
                    250-8BITMIME
                    250-SIZE 41697280
                    250 AUTH PLAIN LOGIN XOAUTH2 OAUTHBEARER
        )
    [STARTTLS] => 220 2.0.0 Ready to start TLS
    [AUTH] =>     334 VXNlcm5hbWU6
    [USERNAME] => 334 UGFzc3dvcmQ6 User=sender@aol.com
    [PASSWORD] => 535 5.7.0 (#AUTH005) Too many bad auth attempts. Pass=my_secret_password
    [MAIL_FROM] => 
    [RECIPIENTS] => Array
        (
            [0] => 
        )
    [MESSAGE] => --alt-9579c30b3e38a6627f6abbbaa4ed91fb
                Content-Type: text/html; charset=utf-8
                Content-Transfer-Encoding: base64

                PGI+RXhhbXBsZSBUTFM8L2I+PGk+IG1lc3NhZ2U8L2k+Li4u

                --alt-9579c30b3e38a6627f6abbbaa4ed91fb--
    [HEADERS] => X-Mailer: PHP/8.0.8
                MIME-Version: 1.0
                Date: Tue, 01 Feb 2022 18:48:31 +0300
                Subject: TLS subject
                From: "Test Sender" <sender@aol.com>
                Return-Path: "Test Sender" <sender@aol.com>
                To: "Test Receiver" <sender@aol.com>
                Content-Type: multipart/alternative; boundary="alt-9579c30b3e38a6627f6abbbaa4ed91fb"
    [DATA] => Array
        (
            [1] => 
            [2] => 
        )
    [QUIT] => 
)
```

## Debug

### Local interface
```bash
sudo tcpdump -i lo -n  port 25 | sed -e 's/Flag.*length//g'
```
### Wan interface (eth0)
```bash
sudo tcpdump -i eth0 -n  port 25 | sed -e 's/Flag.*length//g'
```