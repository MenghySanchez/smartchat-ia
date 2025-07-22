<?php

/**
 * Registra el endpoint en la API REST de WordPress.
 */
add_action('rest_api_init', function () {
    register_rest_route('smartchat/v1', '/ask', [
        'methods' => 'POST',
        'callback' => 'smartchat_handle_ai_request',
        /**
         * ¡Callback de permisos CORREGIDO!
         * Solo permite peticiones de usuarios que han iniciado sesión.
         * Cambia 'read' por una capacidad más restrictiva si es necesario (ej. 'edit_posts').
         * Esto previene el abuso de tu API.
         */
        'permission_callback' => function () {
            return current_user_can('read');
        },
    ]);
});

/**
 * Invalida la caché del contexto cuando un post, página o producto se guarda.
 * Esto asegura que el chatbot siempre tenga información actualizada.
 */
add_action('save_post', 'smartchat_invalidate_context_cache', 10, 1);
add_action('save_post_product', 'smartchat_invalidate_context_cache', 10, 1);
function smartchat_invalidate_context_cache() {
    delete_transient('smartchat_site_context');
}

/**
 * Genera y cachea el contexto del sitio para no regenerarlo en cada petición.
 * Esta es la optimización de rendimiento más importante.
 */
function smartchat_get_cached_site_context() {
    // Intenta obtener el contexto desde la caché (transient)
    $cached_context = get_transient('smartchat_site_context');
    if (false !== $cached_context) {
        return $cached_context; // Devuelve el contexto cacheado si existe
    }

    // Si no está en caché, lo generamos de nuevo
    $site_context = "";

    // 1. Entradas y páginas (limitado para no exceder el tamaño del contexto)
    $posts = get_posts(['post_type' => ['post', 'page'], 'post_status' => 'publish', 'numberposts' => 20]);
    if ($posts) {
        $site_context .= "Entradas y páginas del sitio:\n";
        foreach ($posts as $post) {
            $content = wp_strip_all_tags($post->post_content);
            // Usamos wp_trim_words para no enviar contenido excesivamente largo
            $site_context .= "### " . $post->post_title . " ###\n" . wp_trim_words($content, 150) . "\n\n";
        }
    }

    // 2. Productos de WooCommerce (si existe)
    if (class_exists('WooCommerce')) {
        $products = wc_get_products(['limit' => 30, 'status' => 'publish']);
        if ($products) {
            $site_context .= "\nProductos de la tienda:\n";
            foreach ($products as $product) {
                $site_context .= "Producto: " . $product->get_name() . "\n";
                $site_context .= "Precio: " . $product->get_price() . " " . get_woocommerce_currency() . "\n";
                $site_context .= "Descripción: " . wp_trim_words(wp_strip_all_tags($product->get_description()), 70) . "\n\n";
            }
        }
    }

    // Guardar el nuevo contexto en la caché por 12 horas
    set_transient('smartchat_site_context', $site_context, 12 * HOUR_IN_SECONDS);

    return $site_context;
}

/**
 * Maneja la petición a la API, construye el prompt y llama al proveedor de IA.
 */
function smartchat_handle_ai_request($request) {
    // ... (el código anterior no cambia) ...
    $params = $request->get_json_params();
    $message = sanitize_text_field($params['message'] ?? '');

    if (empty($message)) {
        return new WP_REST_Response(['reply' => 'El mensaje no puede estar vacío.'], 400);
    }

    $site_data = smartchat_get_cached_site_context();
    $system_prompt = "Eres un asistente experto del sitio web. Responde de forma clara, breve y en el idioma del usuario. Usa 
    la siguiente información del sitio para formular tu respuesta:\n\n" . $site_data;

    $provider = get_option('smartchat_ai_provider', 'openai');
    $api_key  = get_option('smartchat_ai_key');
    $model    = get_option('smartchat_ai_model');

    if (empty($api_key)) {
        return new WP_REST_Response(['reply' => 'La API Key del proveedor de IA no está configurada.'], 500);
    }

    // --- Lógica para OpenAI ---
    if ($provider === 'openai') {
        $model = $model ?: 'gpt-3.5-turbo';
        $api_url = 'https://api.openai.com/v1/chat/completions';

        $response = wp_remote_post($api_url, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . trim($api_key),
            ],
            'body' => json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system_prompt],
                    ['role' => 'user', 'content' => $message]
                ],
            ]),
            'timeout' => 30, // <-- AUMENTAMOS EL TIMEOUT A 30 SEGUNDOS
        ]);

        if (is_wp_error($response)) {
            return new WP_REST_Response(['reply' => 'Error al conectar con OpenAI: ' . $response->get_error_message()], 500);
        }
        // ... resto del código de OpenAI ...
    }

    // --- Lógica para Gemini ---
    if ($provider === 'gemini') {
        $model = $model ?: 'gemini-pro';
        $api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . trim($api_key);

        $response = wp_remote_post($api_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $system_prompt]]],
                    ['role' => 'model', 'parts' => [['text' => "Entendido. Estoy listo para ayudar."]]],
                    ['role' => 'user', 'parts' => [['text' => $message]]],
                ]
            ]),
            'timeout' => 30, // <-- AUMENTAMOS EL TIMEOUT A 30 SEGUNDOS
        ]);

        if (is_wp_error($response)) {
            return new WP_REST_Response(['reply' => 'Error al conectar con Gemini: ' . $response->get_error_message()], 500);
        }
        // ... resto del código de Gemini ...
    }

    return new WP_REST_Response(['reply' => 'Proveedor de IA no soportado.'], 400);
}