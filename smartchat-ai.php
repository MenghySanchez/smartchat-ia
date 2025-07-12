<?php
/**
 * Plugin Name: SmartChat IA
 * Description: Chatbot inteligente con IA, recomendaciones de productos/servicios y notificaciones por WhatsApp.
 * Version: 1.0
 * Author:KongStudios | by Menghy Sánchez 
 */

// Bloquear acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Cargar archivos necesarios
require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php'; // settings-page.php: Archivo para la página de configuración del plugin en el área de administración.
require_once plugin_dir_path(__FILE__) . 'includes/whatsapp-handler.php';       // whatsapp-handler.php: Archivo para manejar las notificaciones por WhatsApp.

// Inyectar chatbot en el frontend
add_action('wp_footer', 'smartchat_render_chatbot'); // wp_footer: Hook para agregar HTML del chatbot al final del sitio.

function smartchat_render_chatbot() {
    ?>
    <div id="smartchat-bubble">💬</div>
    <div id="smartchat-window" style="display: none;"> <!-- // Contenedor del chatbot, inicialmente oculto.  -->
        <iframe srcdoc="Hola, soy tu asistente. ¿En qué puedo ayudarte?" style="width: 100%; height: 100%; border: none;"></iframe>
    </div>
    <?php
    wp_enqueue_style('smartchat-style', plugins_url('assets/css/chatbot.css', __FILE__));                    //wp_enqueue_style / script: Para cargar nuestros archivos JS y CSS.
    wp_enqueue_script('smartchat-js', plugins_url('assets/js/chatbot.js', __FILE__), array(), false, true); //wp_enqueue_script: Para cargar nuestro archivo JS del chatbot.
}