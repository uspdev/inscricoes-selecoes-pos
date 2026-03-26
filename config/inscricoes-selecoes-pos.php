<?php

return [
    // Deprecado, remover no próximo release
    'admins' => env('SENHAUNICA_ADMINS'),

    'usar_replicado' => env('USAR_REPLICADO', true),
    'upload_max_filesize' => (int) env('UPLOAD_MAX_FILESIZE', '16') * 1024,

    // deprecado em 2/23. Remover no próximo release
    'forcar_https' => env('FORCAR_HTTPS', false),

    // WSBoleto
    'ws_boleto_usuario' => env('WS_BOLETO_USUARIO'),
    'ws_boleto_senha' => env('WS_BOLETO_SENHA'),
    'ws_boleto_cancelar' => env('WS_BOLETO_CANCELAR', true),

    // reCAPTCHA
    'recaptcha_site_key' => env('RECAPTCHA_SITE_KEY'),
    'recaptcha_secret_key' => env('RECAPTCHA_SECRET_KEY'),

    // tempo de expiração do link de redefinição de senha de usuários locais (em minutos)
    'password_reset_link_expiry_time' => (int) env('PASSWORD_RESET_LINK_EXPIRY_TIME', 60),

    // média em segundos do tempo para download de 1 Gb
    'timeout_por_gb' => (int) env('TIMEOUT_POR_GB', 90),

    'integracao-cadastros-auxiliares' => env('CADASTROS_AUXILIARES_MENSAGENS_INTEGRACAO', false),
];
