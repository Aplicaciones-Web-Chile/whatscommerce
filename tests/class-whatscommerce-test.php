<?php
namespace WhatsCommerce\Tests;

/**
 * Clase de pruebas para WhatsCommerce
 */
class WhatsCommerceTest {
    /**
     * Verifica todas las dependencias del plugin
     *
     * @return array Array con errores encontrados
     */
    public static function verify_dependencies() {
        $errors = [];
        
        // Verificar clases requeridas
        $required_classes = [
            'WhatsCommerce\WhatsCommerce',
            'WhatsCommerce\TwilioService',
            'WhatsCommerce\WhatsCommerceLogger',
            'WhatsCommerce\SettingsManager',
            'WhatsCommerce\ProductManager',
            'WhatsCommerce\OrderManager',
            'WhatsCommerce\WhatsCommerceWebhookHandler',
            'WhatsCommerce\ConversationState',
            'WhatsCommerce\UserManager'
        ];

        foreach ($required_classes as $class) {
            if (!class_exists($class)) {
                $errors[] = "Clase no encontrada: $class";
            }
        }

        // Verificar archivos requeridos
        $required_files = [
            'vendor/autoload.php' => 'Composer autoloader',
            'includes/class-whatscommerce.php' => 'Clase principal WhatsCommerce',
            'includes/class-twilio-service.php' => 'Servicio de Twilio',
            'includes/class-whatscommerce-logger.php' => 'Sistema de logging',
            'includes/class-settings-manager.php' => 'Gestor de configuraciones',
            'includes/class-product-manager.php' => 'Gestor de productos',
            'includes/class-order-manager.php' => 'Gestor de órdenes',
            'includes/class-webhook-handler.php' => 'Manejador de webhooks',
            'includes/class-conversation-state.php' => 'Estado de conversación',
            'includes/class-user-manager.php' => 'Gestor de usuarios'
        ];

        foreach ($required_files as $file => $description) {
            if (!file_exists(WHATSCOMMERCE_PLUGIN_DIR . $file)) {
                $errors[] = "Archivo no encontrado: $file ($description)";
            }
        }

        // Verificar dependencias de Composer
        if (file_exists(WHATSCOMMERCE_PLUGIN_DIR . 'vendor/autoload.php')) {
            $composer_json = json_decode(file_get_contents(WHATSCOMMERCE_PLUGIN_DIR . 'composer.json'), true);
            if (isset($composer_json['require'])) {
                foreach ($composer_json['require'] as $package => $version) {
                    if (!class_exists(str_replace('/', '\\', $package))) {
                        $errors[] = "Dependencia de Composer no encontrada: $package";
                    }
                }
            }
        }

        // Verificar directorio de traducciones
        if (!is_dir(WHATSCOMMERCE_PLUGIN_DIR . 'languages')) {
            $errors[] = "Directorio de traducciones no encontrado";
        }

        return $errors;
    }

    /**
     * Verifica la configuración del plugin
     *
     * @return array Array con errores encontrados
     */
    public static function verify_configuration() {
        $errors = [];

        // Verificar opciones requeridas
        $required_options = [
            'whatscommerce_twilio_sid' => 'Twilio Account SID',
            'whatscommerce_twilio_token' => 'Twilio Auth Token',
            'whatscommerce_twilio_number' => 'Twilio WhatsApp Number'
        ];

        foreach ($required_options as $option => $description) {
            if (!get_option($option)) {
                $errors[] = "Opción no configurada: $description";
            }
        }

        // Verificar WooCommerce
        if (!class_exists('WooCommerce')) {
            $errors[] = "WooCommerce no está instalado o activado";
        }

        return $errors;
    }

    /**
     * Ejecuta todas las verificaciones
     *
     * @return array Array con todos los errores encontrados
     */
    public static function run_all_tests() {
        $all_errors = [];
        
        // Verificar dependencias
        $dependency_errors = self::verify_dependencies();
        if (!empty($dependency_errors)) {
            $all_errors['dependencies'] = $dependency_errors;
        }

        // Verificar configuración
        $config_errors = self::verify_configuration();
        if (!empty($config_errors)) {
            $all_errors['configuration'] = $config_errors;
        }

        return $all_errors;
    }
}
