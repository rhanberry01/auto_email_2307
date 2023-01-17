<html>
<head>
<title>PHPMailer - SMTP (Gmail) basic test</title>
</head>
<body>

<?php

//error_reporting(E_ALL);
error_reporting(E_STRICT);

date_default_timezone_set('America/Toronto');

require_once('../class.phpmailer.php');
//include("class.smtp.php"); // optional, gets called from within class.phpmailer.php if not already loaded

$mail             = new PHPMailer();

// $body             = file_get_contents('contents.html');
// $body             = preg_replace('/[\]/','',$body);

$mail->IsSMTP(); // telling the class to use SMTP
$mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
                                           // 1 = errors and messages
                                           // 2 = messages only
$mail->SMTPAuth   = true;                  // enable SMTP authentication
$mail->SMTPSecure = "tls";                 // sets the prefix to the servier
$mail->Host       = "us2.smtp.mailhostbox.com";
$mail->Port       = 25;                   // set the SMTP port for the GMAIL server
$mail->Username   = "srsemail1@srssulit.com";  // GMAIL username
$mail->Password   = "@LQBShZ0";            // GMAIL password

$mail->SetFrom('srsemail1@srssulit.com', 'First Last');
$mail->AddAddress('bataljade9614@gmail.com', 'John Doe');
$mail->AddReplyTo("bataljade9614@gmail.com","First Last");

$mail->Subject    = "TEST";
$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test

$mail->MsgHTML("HI");

// $address = "whoto@otherdomain.com";
// $mail->AddAddress($address, "John Doe");

// $mail->AddAttachment("images/phpmailer.gif");      // attachment
// $mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

if(!$mail->Send()) {
  echo "Mailer Error: " . $mail->ErrorInfo;
} else {
  echo "Message sent!";
}

?>

</body>
</html>
