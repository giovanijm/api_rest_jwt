<?php

return [
    'email-verify' => [
        'subject' => 'Verify Your Email Address',
        'greeting' => 'Hello, :name!',
        'action' => 'Verify Email Address',
        'line_1' => 'Click the button below to verify your email address.',
        'line_2' => 'If you did not create an account, no further action is required.',
    ],

    'password-reset' => [
        'subject' => 'Reset Your Password',
        'greeting' => 'Hello, :name!',
        'action' => 'Reset Password',
        'line_1' => 'You are receiving this email because we received a password reset request for your account.',
        'line_2' => 'This password reset link will expire in :count minutes.',
        'line_3' => 'If you did not request a password reset, no further action is required.',
    ],

    'forgot-password' => [
        'subject' => 'Your Password Reset Code',
        'greeting' => 'Hello, :name!',
        'line_1' => 'You are receiving this email because we received a password reset request for your account.',
        'line_2' => 'Your password reset code is :code',
        'line_3' => 'This code will expire in :count minutes.',
        'line_4' => 'If you did not request a password reset, no further action is required.',
    ],
];
