<?php
/**
 * Plugin Name: WhatsCommerce
 * Description: Integración de WooCommerce con WhatsApp
 * Version: 1.7.6
 * Author: AplicacionesWeb.cl
 * Author URI: https://www.aplicacionesweb.cl
 * Text Domain: whatscommerce
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WHATSCOMMERCE_VERSION', '1.7.6');
define('WHATSCOMMERCE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WHATSCOMMERCE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Cargar el composer autoloader si existe
$composer_autoload = WHATSCOMMERCE_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
}

// Autoloader para las clases del plugin
spl_autoload_register(function ($class_name) {
    // Lista de prefijos de namespace a buscar
    $prefixes = array(
        'WhatsCommerce\\' => WHATSCOMMERCE_PLUGIN_DIR . 'includes/'
    );

    foreach ($prefixes as $prefix => $base_dir) {
        // Verificar si la clase usa el prefijo
        $len = strlen($prefix);
        if (strncmp($prefix, $class_name, $len) !== 0) {
            continue;
        }

        // Obtener el nombre relativo de la clase
        $relative_class = substr($class_name, $len);

        // Reemplazar el namespace con directorios
        $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }

    return false;
});

// Cargar archivos de clases principales
require_once WHATSCOMMERCE_PLUGIN_DIR . 'includes/class-twilio-service.php';
require_once WHATSCOMMERCE_PLUGIN_DIR . 'includes/class-whatscommerce.php';
require_once WHATSCOMMERCE_PLUGIN_DIR . 'includes/class-user-manager.php';
require_once WHATSCOMMERCE_PLUGIN_DIR . 'includes/class-conversation-state.php';

// Función de activación del plugin
function whatscommerce_activate() {
    // Crear tablas necesarias
    if (class_exists('WhatsCommerce\UserManager')) {
        $user_manager = new WhatsCommerce\UserManager();
    }
    if (class_exists('WhatsCommerce\ConversationState')) {
        $conversation_state = new WhatsCommerce\ConversationState();
    }
    
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

// Cargar traducciones
function whatscommerce_load_textdomain() {
    load_plugin_textdomain('whatscommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'whatscommerce_load_textdomain');

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

    // Obtener configuración de Twilio
    $options = get_option('whatscommerce_options', array());
    $twilio_sid = get_option('whatscommerce_twilio_sid');
    $twilio_token = get_option('whatscommerce_twilio_token');
    $twilio_number = get_option('whatscommerce_twilio_number');

    // Inicializar servicios
    try {
        if (!class_exists('WhatsCommerce\TwilioService')) {
            throw new Exception('No se pudo cargar la clase TwilioService');
        }
        
        $twilio_service = new WhatsCommerce\TwilioService($twilio_sid, $twilio_token, $twilio_number);
        $whatscommerce = new WhatsCommerce\WhatsCommerce($twilio_service);
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