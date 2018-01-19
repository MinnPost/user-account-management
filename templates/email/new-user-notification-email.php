<?php

// default message

$msg  = __( 'Hello!', 'user-account-management' ) . "\r\n\r\n";
$msg .= sprintf( __( 'Welcome to MinnPost! You registered using the email address %s.', 'user-account-management' ), $attributes['user_data']->user_email ) . "\r\n\r\n";
$msg .= __( "If this was a mistake, or you didn't ask for a password reset, just ignore this email and nothing will happen.", 'user-account-management' ) . "\r\n\r\n";
$msg .= __( 'Thanks!', 'user-account-management' ) . "\r\n";

echo $msg;
