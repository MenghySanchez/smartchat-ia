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

/**
 * Displays the SmartChat AI settings page in the WordPress admin area.
 *
 * This function outputs the HTML for the plugin's settings page, including
 * the form for updating plugin options. It utilizes WordPress settings API
 * functions to handle form fields and submission.
 *
 * @since 1.0.0
 *
 * @return void
 */
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

/**
 * Registra los ajustes, la sección y el campo del plugin SmartChat para los números de WhatsApp.
 *
 * - Registra la opción 'smartchat_whatsapp_numbers' en el grupo 'smartchat_settings_group'.
 * - Añade una sección de ajustes titulada 'Números de WhatsApp' en la página 'smartchat-settings'.
 * - Añade un campo de ajustes para ingresar los números de WhatsApp, separados por comas.
 *
 * @return void
 */
function smartchat_register_settings() {
    register_setting('smartchat_settings_group', 'smartchat_whatsapp_numbers');

    add_settings_section('main_section', 'Números de WhatsApp', null, 'smartchat-settings');

    add_settings_field(
        'whatsapp_numbers',
        'Números separados por coma',
        'smartchat_numbers_field_callback',
        'smartchat-settings',
        'main_section'
    );
}

/**
 * Función de callback para mostrar el campo de entrada de números de WhatsApp en la página de ajustes del plugin.
 *
 * Recupera los números de WhatsApp guardados en las opciones de WordPress y los muestra
 * en un campo de texto. También muestra una descripción con un ejemplo del formato requerido.
 *
 * @since 1.0.0
 * @return void
 */
function smartchat_numbers_field_callback() {
    $value = get_option('smartchat_whatsapp_numbers');
    echo '<input type="text" name="smartchat_whatsapp_numbers" value="' . esc_attr($value) . '" size="50" />';
    echo '<p class="description">Ej: +593123456789,+593987654321</p>';
}