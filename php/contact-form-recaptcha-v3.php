<?php
/*
Name: 			Contact Form - Google Recaptcha v3
Written by: 	Okler Themes - (http://www.okler.net)
Theme Version:	8.0.0
*/

namespace PortoContactForm;

ini_set('allow_url_fopen', true);

session_cache_limiter('nocache');
header('Expires: ' . gmdate('r', 0));

header('Content-type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'php-mailer/src/PHPMailer.php';
require 'php-mailer/src/SMTP.php';
require 'php-mailer/src/Exception.php';

if(isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {

	// Your Google reCAPTCHA generated Secret Key here
	$secret = '6Lflm7oZAAAAAItcBWqYhxerR2alBEhpzvVUYWyc';
	
	if( ini_get('allow_url_fopen') ) {
		//reCAPTCHA - Using file_get_contents()
		$verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$_POST['g-recaptcha-response']);
		$responseData = json_decode($verifyResponse);
	} else if( function_exists('curl_version') ) {
		// reCAPTCHA - Using CURL
		$fields = array(
	        'secret'    =>  $secret,
	        'response'  =>  $_POST['g-recaptcha-response'],
	        'remoteip'  =>  $_SERVER['REMOTE_ADDR']
	    );

	    $verifyResponse = curl_init("https://www.google.com/recaptcha/api/siteverify");
	    curl_setopt($verifyResponse, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($verifyResponse, CURLOPT_TIMEOUT, 15);
	    curl_setopt($verifyResponse, CURLOPT_POSTFIELDS, http_build_query($fields));
	    $responseData = json_decode(curl_exec($verifyResponse));
	    curl_close($verifyResponse);
	} else {
		$arrResult = array ('response'=>'error','errorMessage'=>'You need CURL or file_get_contents() activated in your server. Please contact your host to activate.');
		echo json_encode($arrResult);
		die();
	}

	if($responseData->success) {

		// Step 1 - Enter your email address below.
		$email = 'ocarvajal@plusnet.cl.test-google-a.com';

		// If the e-mail is not working, change the debug option to 2 | $debug = 2;
		$debug = 0;

		// If contact form don't have the subject input, change the value of subject here
		$subject = 'Nueva solicitud de contacto';//( isset($_POST['subject']) ) ? $_POST['subject'] : 'Define subject in php/contact-form-recaptcha.php line 62';

		$message = '';

		foreach($_POST as $label => $value) {
			if( $label != 'g-recaptcha-response' ) {
				$label = ucwords($label);

				// Use the commented code below to change label texts. On this example will change "Email" to "Email Address"

				if( $label == 'Message' ) {               
					$label = 'Mensaje';              
				}

				if( $label == 'Name' ) {               
					$label = 'Nombre';              
				}

				$message .= $label.": " . htmlspecialchars($value, ENT_QUOTES) . "<br>\n";
			}
		}

		$mail = new PHPMailer(true);

		try {

			$mail->SMTPDebug = $debug;                                 // Debug Mode

			$mail->AddAddress($email);	 						       // Add recipient

			// From 
			$mail->SetFrom('no-reply@plusnet.cl', '[LEAD] ' . $_POST['name']);

			// Repply To
			if( isset($_POST['email']) ) {
				$mail->AddReplyTo($_POST['email'], $_POST['name']);
			}

			$mail->IsHTML(true);                                  // Set email format to HTML

			$mail->CharSet = 'UTF-8';

			$mail->Subject = $subject;
			$mail->Body    = $message;

			$mail->Send();
			$arrResult = array ('response'=>'success');

		} catch (Exception $e) {
			$arrResult = array ('response'=>'error','errorMessage'=>$e->errorMessage());
		} catch (\Exception $e) {
			$arrResult = array ('response'=>'error','errorMessage'=>$e->getMessage());
		}

		if ($debug == 0) {
			echo json_encode($arrResult);
		}

	} else {
		$arrResult = array ('response'=>'error','errorMessage'=>'reCaptcha Error: Verifcation failed (no success). Please contact the website administrator.');
		echo json_encode($arrResult);
	}

} else { 
	$arrResult = array ('response'=>'error','errorMessage'=>'reCaptcha Error: Invalid token. Please contact the website administrator.');
	echo json_encode($arrResult);
}