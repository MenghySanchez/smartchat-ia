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
    // Instrucciones para el idioma y estilo de respuesta
$language_instruction = "Responde en el idioma que detectes en el mensaje del usuario.";
$simplify_instruction = "Responde de forma clara, breve y con párrafos cortos para facilitar la lectura.";

    if (empty($message)) {
        return rest_ensure_response(['reply' => 'Mensaje vacío.']);
    }

    // Compilar contexto detallado del sitio
    $site_context = "";

    // 1. Entradas y páginas completas
    $posts = get_posts([
        'post_type' => ['post', 'page'],
        'post_status' => 'publish',
        'numberposts' => -1,
    ]);

    if ($posts) {
        $site_context .= "📄 Entradas y páginas:\n";
        foreach ($posts as $post) {
            $content = wp_strip_all_tags($post->post_content);
            $site_context .= "### " . $post->post_title . " ###\n" . $content . "\n\n";
        }
    }

    // 2. Productos de WooCommerce (completos)
    if (class_exists('WooCommerce')) {
        $products = wc_get_products([
            'limit' => -1,
            'status' => 'publish',
        ]);
        if ($products) {
            $site_context .= "\n🛒 Productos:\n";
            foreach ($products as $product) {
                $site_context .= "Producto: " . $product->get_name() . "\n";
                $site_context .= "Precio: " . wc_price($product->get_price()) . "\n";
                $site_context .= "Descripción: " . wp_strip_all_tags($product->get_description()) . "\n\n";
            }
        }

        // 3. Categorías de producto
        $product_cats = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
        ]);
        if (!is_wp_error($product_cats) && $product_cats) {
            $site_context .= "\n📂 Categorías:\n";
            foreach ($product_cats as $cat) {
                $site_context .= "- " . $cat->name . "\n";
            }
        }

        // 4. Reseñas de productos
        $comments = get_comments([
            'status' => 'approve',
            'number' => 20,
            'post_type' => 'product'
        ]);
        if ($comments) {
            $site_context .= "\n⭐ Reseñas recientes:\n";
            foreach ($comments as $comment) {
                $site_context .= "- " . wp_trim_words(wp_strip_all_tags($comment->comment_content), 40) . "\n";
            }
        }
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
['role' => 'system', 'content' => $language_instruction . "\n" . $simplify_instruction . "\n\nActúa como asistente experto del sitio web. Usa esta información para responder:\n" . $site_context],
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
                    [
                        'parts' => [
['text' => $language_instruction . "\n" . $simplify_instruction . "\n\nActúa como asistente experto del sitio web. Usa esta información para responder:\n" . $site_context],
                            ['text' => "Mensaje del usuario: " . $message]
                        ]
                    ]
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