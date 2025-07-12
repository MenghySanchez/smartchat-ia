<?php
add_action('admin_menu', 'smartchat_admin_menu');

function smartchat_admin_menu() {
    add_menu_page(
        'SmartChat Settings',
        'SmartChat IA',
        'manage_options',
        'smartchat-settings',
        'smartchat_settings_page',
        'dashicons-format-chat'
    );
}

function smartchat_settings_page() {
    ?>
    <div class="wrap">
        <h1>Configuración de SmartChat IA</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('smartchat_settings_group');
                do_settings_sections('smartchat-settings');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'smartchat_register_settings');

function smartchat_register_settings() {
    // WhatsApp
    register_setting('smartchat_settings_group', 'smartchat_whatsapp_numbers');

    add_settings_section('main_section', 'Números de WhatsApp', null, 'smartchat-settings');

    add_settings_field(
        'whatsapp_numbers',
        'Números separados por coma',
        'smartchat_numbers_field_callback',
        'smartchat-settings',
        'main_section'
    );

    // IA
    register_setting('smartchat_settings_group', 'smartchat_ai_provider');
    register_setting('smartchat_settings_group', 'smartchat_ai_key');
    register_setting('smartchat_settings_group', 'smartchat_ai_model');

    add_settings_section('ai_section', 'Conexión con IA', null, 'smartchat-settings');

    add_settings_field(
        'ai_provider',
        'Proveedor de IA',
        function() {
            $value = get_option('smartchat_ai_provider', 'openai');
            echo '<select name="smartchat_ai_provider">
                    <option value="openai" ' . selected($value, 'openai', false) . '>OpenAI</option>
                    <option value="gemini" ' . selected($value, 'gemini', false) . '>Gemini</option>
                    <option value="otro" ' . selected($value, 'otro', false) . '>Otro</option>
                  </select>';
        },
        'smartchat-settings',
        'ai_section'
    );

    add_settings_field(
        'ai_key',
        'API Key de la IA',
        function() {
            $value = get_option('smartchat_ai_key');
            echo '<input type="text" name="smartchat_ai_key" value="' . esc_attr($value) . '" size="50" />';
        },
        'smartchat-settings',
        'ai_section'
    );

    add_settings_field(
        'ai_model',
        'Modelo (opcional)',
        function() {
            $value = get_option('smartchat_ai_model', 'gpt-3.5-turbo');
            echo '<input type="text" name="smartchat_ai_model" value="' . esc_attr($value) . '" size="30" />';
        },
        'smartchat-settings',
        'ai_section'
    );
}

function smartchat_numbers_field_callback() {
    $value = get_option('smartchat_whatsapp_numbers');
    echo '<input type="text" name="smartchat_whatsapp_numbers" value="' . esc_attr($value) . '" size="50" />';
    echo '<p class="description">Ej: +593123456789,+593987654321</p>';
}