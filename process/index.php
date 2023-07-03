<?php

$path = preg_replace('/wp-content.*$/','',__DIR__);

require_once($path."wp-load.php");

if(isset($_POST['maplesyrupweb_submit_form'])) {

    // echo "<pre>";print_r($_POST);echo "</pre>";

    $yourName = sanitize_text_field($_POST['full_name']);
    $yourEmail = sanitize_email($_POST['email_address']);

    $targetDirArray = wp_upload_dir();
    $targetDir = $targetDirArray['path'];

    $targetFile = $targetDir . basename($_FILES['my_file_upload']['name']);

    if (move_uploaded_file($_FILES['my_file_upload']['tmp_name'], $targetFile)){
        $file_upload_message = 'A file has been uploaded to the uploads directory.';

    }
    else {
        $file_upload_message = 'No file was uploaded.';

    }


    $to = get_option('send_to_email');
    $subject = "Maplesyrup Web Form Submission";
    $body = '';

    $theName = sanitize_text_field($_POST['full_name']);
    $theEmail = sanitize_text_field($_POST['email_address']);
    $thePhone = sanitize_text_field($_POST['phone_number']);
    $theMessage = sanitize_textarea_field($_POST['your_message']);
    $headers = 'From: '.$theName.' <'.$theEmail.'>' . "\r\n" . 'Reply-To: ' . $theEmail;
    // wp_mail($to, $subject, $theMessage, $headers, $targetFile);

    $message = 'Hi, The following info has been submitted<br/>';
    $message .= 'Name: ' . $yourName . '<br/>';
    $message .= 'Email:' . $yourEmail . '<br/>';
    $message .= 'Message:' . $theMessage . '<br/>';
    $message .= 'Attachment:' . $file_upload_message . '<br/>';

    wp_mail($to,$subject, $message, $targetFile);

    wp_redirect('http://fictional-university.local/thank-you');

}

?>