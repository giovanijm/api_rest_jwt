<?php

return [
    'email-verify' => [
        'subject' => 'Verifique seu endereço de e-mail',
        'greeting' => 'Olá, :name!',
        'action' => 'Verificar endereço de e-mail',
        'line_1' => 'Clique no botão abaixo para verificar seu endereço de e-mail.',
        'line_2' => 'Se você não criou uma conta, nenhuma ação adicional é necessária.',
    ],

    'password-reset' => [
        'subject' => 'Redefinir sua senha',
        'greeting' => 'Olá, :name!',
        'action' => 'Redefinir senha',
        'line_1' => 'Você está recebendo este e-mail porque recebemos uma solicitação de redefinição de senha para sua conta.',
        'line_2' => 'Este link de redefinição de senha expirará em :count minutos.',
        'line_3' => 'Se você não solicitou uma redefinição de senha, nenhuma ação adicional é necessária.',
    ],

    'forgot-password' => [
        'subject' => 'Seu código de redefinição de senha',
        'greeting' => 'Olá, :name!',
        'line_1' => 'Você está recebendo este e-mail porque recebemos uma solicitação de redefinição de senha para sua conta.',
        'line_2' => 'Seu código de redefinição de senha é :code',
        'line_3' => 'Este código expirará em :count minutos.',
        'line_4' => 'Se você não solicitou uma redefinição de senha, nenhuma ação adicional é necessária.',
    ],
];
