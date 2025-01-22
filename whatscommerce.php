<?php
/**
 * Plugin Name: WhatsCommerce
 * Description: Integración de WooCommerce con WhatsApp
 * Version: 1.7.7
 * Author: AplicacionesWeb.cl
 * Author URI: https://www.aplicacionesweb.cl
 * Text Domain: whatscommerce
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WHATSCOMMERCE_VERSION', '1.7.7');
define('WHATSCOMMERCE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WHATSCOMMERCE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Cargar el autoloader de Composer
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

// Cargar archivos principales
require_once WHATSCOMMERCE_PLUGIN_DIR . 'includes/class-whatscommerce.php';
require_once WHATSCOMMERCE_PLUGIN_DIR . 'includes/class-twilio-service.php';
require_once WHATSCOMMERCE_PLUGIN_DIR . 'includes/class-user-manager.php';
require_once WHATSCOMMERCE_PLUGIN_DIR . 'includes/class-conversation-state.php';

use WhatsCommerce\WhatsCommerce;
use WhatsCommerce\TwilioService;

/**
 * Función de inicialización del plugin
 */
function whatscommerce_init() {
    // Cargar traducciones
    load_plugin_textdomain('whatscommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    try {
        // Inicializar el plugin
        $plugin = WhatsCommerce::get_instance();
        $plugin->init();
    } catch (Exception $e) {
        // Registrar el error
        error_log('WhatsCommerce Error: ' . $e->getMessage());
        
        // Mostrar mensaje de error en el admin
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error">
                <p><?php echo esc_html('Error al inicializar WhatsCommerce: ' . $e->getMessage()); ?></p>
            </div>
            <?php
        });
    }
}

// Inicializar el plugin después de que WordPress esté listo
add_action('init', 'whatscommerce_init');

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