<?php
namespace WhatsCommerce;

/**
 * Sistema de logging para WhatsCommerce
 *
 * Esta clase proporciona funcionalidades de logging para el plugin,
 * permitiendo registrar eventos, errores y depuración.
 *
 * @package WhatsCommerce
 * @since 1.0.0
 */
class WhatsCommerceLogger {
    /**
     * Instancia única de la clase
     *
     * @since 1.0.0
     * @access private
     * @var WhatsCommerceLogger
     */
    private static $instance = null;

    /**
     * Constructor privado para el patrón singleton
     */
    private function __construct() {
        // Asegurarse de que el directorio de logs existe
        $this->ensure_log_directory();
    }

    /**
     * Obtiene la instancia única de la clase
     *
     * @since 1.0.0
     * @access public
     * @static
     *
     * @return WhatsCommerceLogger Instancia única de la clase.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Se asegura de que el directorio de logs existe
     */
    private function ensure_log_directory() {
        $log_dir = WHATSCOMMERCE_PLUGIN_DIR . 'logs';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            file_put_contents($log_dir . '/.htaccess', 'deny from all');
            file_put_contents($log_dir . '/index.php', '<?php // Silence is golden');
        }
    }

    /**
     * Registra un mensaje informativo
     *
     * @param string $message Mensaje a registrar
     * @param array $context Contexto adicional
     */
    public function info($message, $context = array()) {
        $this->log('INFO', $message, $context);
    }

    /**
     * Registra un mensaje de error
     *
     * @param string $message Mensaje a registrar
     * @param array $context Contexto adicional
     */
    public function error($message, $context = array()) {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Registra un mensaje de depuración
     *
     * @param string $message Mensaje a registrar
     * @param array $context Contexto adicional
     */
    public function debug($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log('DEBUG', $message, $context);
        }
    }

    /**
     * Registra un mensaje
     *
     * @param string $level Nivel del mensaje
     * @param string $message Mensaje a registrar
     * @param array $context Contexto adicional
     */
    private function log($level, $message, $context = array()) {
        $log_entry = sprintf(
            '[%s] [%s] %s %s' . PHP_EOL,
            current_time('Y-m-d H:i:s'),
            $level,
            $message,
            !empty($context) ? json_encode($context) : ''
        );

        $log_file = WHATSCOMMERCE_PLUGIN_DIR . 'logs/whatscommerce-' . current_time('Y-m-d') . '.log';
        error_log($log_entry, 3, $log_file);

        // Si es un error, también lo registramos en el log de WordPress
        if ($level === 'ERROR') {
            error_log('WhatsCommerce Error: ' . $message . (!empty($context) ? ' Context: ' . json_encode($context) : ''));
        }
    }
}
