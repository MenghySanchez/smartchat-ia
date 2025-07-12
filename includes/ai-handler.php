<?php

add_action('rest_api_init', function () {
    register_rest_route('smartchat/v1', '/ask', [
        'methods' => 'POST',
        'callback' => 'smartchat_handle_ai_request',
        'permission_callback' => '__return_true',
    ]);
});

function smartchat_handle_ai_request($request) {
    $params = $request->get_json_params();
    $message = sanitize_text_field($params['message'] ?? '');

    if (empty($message)) {
        return rest_ensure_response(['reply' => 'Mensaje vacío.']);
    }

    $provider = get_option('smartchat_ai_provider', 'openai');
    $api_key  = get_option('smartchat_ai_key');
    $model    = get_option('smartchat_ai_model');

    if (!$api_key) {
        return rest_ensure_response(['reply' => 'API Key no configurada.']);
    }

    if ($provider === 'openai') {
        $model = $model ?: 'gpt-3.5-turbo';

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . trim($api_key),
            ],
            'body' => json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un asistente que responde con base en la información del sitio web.'],
                    ['role' => 'user', 'content' => $message]
                ],
            ]),
        ]);

        if (is_wp_error($response)) {
            return rest_ensure_response(['reply' => 'Error al conectar con OpenAI: ' . $response->get_error_message()]);
        }

        $raw = wp_remote_retrieve_body($response);
        $body = json_decode($raw, true);

        if (isset($body['choices'][0]['message']['content'])) {
            return rest_ensure_response(['reply' => $body['choices'][0]['message']['content']]);
        }

        return rest_ensure_response(['reply' => 'Respuesta inválida de OpenAI: ' . $raw]);
    }

    if ($provider === 'gemini') {
        $model = $model ?: 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type'   => 'application/json',
                'X-goog-api-key' => trim($api_key),
            ],
            'body' => json_encode([
                'contents' => [
                    ['parts' => [['text' => $message]]]
                ]
            ]),
        ]);

        if (is_wp_error($response)) {
            return rest_ensure_response(['reply' => 'Error al conectar con Gemini: ' . $response->get_error_message()]);
        }

        $raw = wp_remote_retrieve_body($response);
        $body = json_decode($raw, true);

        if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return rest_ensure_response(['reply' => $body['candidates'][0]['content']['parts'][0]['text']]);
        }

        return rest_ensure_response(['reply' => 'Respuesta inválida de Gemini: ' . $raw]);
    }

    return rest_ensure_response(['reply' => 'Proveedor de IA no soportado.']);
}