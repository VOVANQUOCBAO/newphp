<?php

// Sao chép thành chatbot.local.php và điền key của bạn.
// Có thể dùng biến môi trường CHATBOT_API_KEY, CHATBOT_API_URL, CHATBOT_MODEL, CHATBOT_ENABLED
return [
    'enabled' => false,
    'api_key' => getenv('CHATBOT_API_KEY') ?: 'NHAP_KEY_TAI_DAY',
    'api_url' => getenv('CHATBOT_API_URL') ?: 'https://api.openai.com/v1/chat/completions',
    'model' => getenv('CHATBOT_MODEL') ?: 'gpt-4o-mini',
];
