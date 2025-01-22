<?php
/**
 * Plugin Name: WhatsCommerce
 * Description: Integración de WooCommerce con WhatsApp
 * Version: 1.7.3
 * Author: AplicacionesWeb.cl
 * Author URI: https://www.aplicacionesweb.cl
 */

if (!defined('ABSPATH')) {
    exit;
}

// Autoloader para las clases del plugin
spl_autoload_register(function ($class_name) {
    $classes_dir = plugin_dir_path(__FILE__) . 'includes/';
    $class_file = $classes_dir . 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
    
    if (file_exists($class_file)) {
        require_once $class_file;
    }
});

// Cargar el composer autoloader
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Función de activación del plugin
function whatscommerce_activate() {
    // Crear tablas necesarias
    $user_manager = new UserManager();
    $conversation_state = new ConversationState();
    
    // Crear opciones por defecto
    $default_options = array(
        'auto_create_users' => true,
        'session_timeout' => 15,
    );
    
    add_option('whatscommerce_options', $default_options);
    
    // Limpiar el caché de rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'whatscommerce_activate');

// Función de desactivación del plugin
function whatscommerce_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'whatscommerce_deactivate');

// Inicializar el plugin
function whatscommerce_init() {
    // Verificar si WooCommerce está activo
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p>';
            echo __('WhatsCommerce requiere que WooCommerce esté instalado y activado.', 'whatscommerce');
            echo '</p></div>';
        });
        return;
    }

    // Cargar traducciones
    load_plugin_textdomain('whatscommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Obtener configuración de Twilio
    $options = get_option('whatscommerce_options', array());
    $twilio_sid = get_option('whatscommerce_twilio_sid');
    $twilio_token = get_option('whatscommerce_twilio_token');
    $twilio_number = get_option('whatscommerce_twilio_number');

    // Inicializar servicios
    try {
        $twilio_service = new TwilioService($twilio_sid, $twilio_token, $twilio_number);
        $whatscommerce = new WhatsCommerce($twilio_service);
        $whatscommerce->init();
    } catch (Exception $e) {
        error_log('WhatsCommerce Error: ' . $e->getMessage());
        add_action('admin_notices', function () use ($e) {
            echo '<div class="error"><p>';
            echo __('Error al inicializar WhatsCommerce: ', 'whatscommerce') . esc_html($e->getMessage());
            echo '</p></div>';
        });
    }
}

// Inicializar el plugin después de que todos los plugins estén cargados
add_action('plugins_loaded', 'whatscommerce_init', 20);

// Agregar enlace de configuración en la lista de plugins
function whatscommerce_plugin_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=whatscommerce_settings') . '">' . 
                     __('Configuración', 'whatscommerce') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'whatscommerce_plugin_links');

// Agregar un hook para mostrar un mensaje de error si el usuario no tiene permisos de administrador
add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) {
        echo '<div class="error"><p>' . __('WhatsCommerce requiere permisos de administrador.', 'whatscommerce') . '</p></div>';
    }
});