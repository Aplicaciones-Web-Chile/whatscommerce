<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Sistema de logging de WhatsCommerce
 *
 * Esta clase implementa un sistema de logging robusto usando Monolog.
 * Proporciona diferentes niveles de logging, rotación de archivos
 * y formateo personalizado de mensajes.
 *
 * @package WhatsCommerce
 * @subpackage Logging
 * @since 1.0.0
 */
class WhatsCommerceLogger {
    /**
     * Instancia única del logger
     *
     * @since 1.0.0
     * @access private
     * @var WhatsCommerceLogger
     */
    private static $instance = null;

    /**
     * Instancia de Monolog Logger
     *
     * @since 1.0.0
     * @access private
     * @var Logger
     */
    private $logger;

    /**
     * Constructor privado para Singleton
     *
     * @since 1.0.0
     * @access private
     */
    private function __construct() {
        $this->initialize_logger();
    }

    /**
     * Obtiene la instancia única del logger
     *
     * @since 1.0.0
     * @access public
     *
     * @return WhatsCommerceLogger Instancia del logger.
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializa el logger con sus manejadores
     *
     * @since 1.0.0
     * @access private
     */
    private function initialize_logger() {
        // Crear el logger
        $this->logger = new Logger('whatscommerce');

        // Definir el formato de los logs
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output);

        // Directorio de logs
        $log_dir = WP_CONTENT_DIR . '/uploads/whatscommerce/logs';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        // Manejador para errores críticos
        $error_handler = new StreamHandler(
            $log_dir . '/error.log',
            Logger::ERROR
        );
        $error_handler->setFormatter($formatter);
        $this->logger->pushHandler($error_handler);

        // Manejador para logs generales con rotación
        $settings = new SettingsManager();
        $retention_days = (int) $settings->get_setting('log_retention_days', 30);
        
        $info_handler = new RotatingFileHandler(
            $log_dir . '/whatscommerce.log',
            $retention_days,
            Logger::INFO
        );
        $info_handler->setFormatter($formatter);
        $this->logger->pushHandler($info_handler);
    }

    /**
     * Registra un mensaje de error
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $message Mensaje de error.
     * @param array $context Contexto adicional.
     */
    public function error($message, array $context = array()) {
        $this->logger->error($message, $context);
    }

    /**
     * Registra un mensaje de advertencia
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $message Mensaje de advertencia.
     * @param array $context Contexto adicional.
     */
    public function warning($message, array $context = array()) {
        $this->logger->warning($message, $context);
    }

    /**
     * Registra un mensaje informativo
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $message Mensaje informativo.
     * @param array $context Contexto adicional.
     */
    public function info($message, array $context = array()) {
        $this->logger->info($message, $context);
    }

    /**
     * Registra un mensaje de depuración
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $message Mensaje de depuración.
     * @param array $context Contexto adicional.
     */
    public function debug($message, array $context = array()) {
        $this->logger->debug($message, $context);
    }

    /**
     * Registra un mensaje crítico
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $message Mensaje crítico.
     * @param array $context Contexto adicional.
     */
    public function critical($message, array $context = array()) {
        $this->logger->critical($message, $context);
    }

    /**
     * Obtiene la lista de archivos de log
     *
     * @since 1.0.0
     * @access public
     *
     * @return array Lista de archivos con sus detalles.
     */
    public function get_log_files() {
        $log_dir = WP_CONTENT_DIR . '/uploads/whatscommerce/logs';
        $files = glob($log_dir . '/*.log');
        $log_files = array();

        foreach ($files as $file) {
            $log_files[] = array(
                'name' => basename($file),
                'size' => size_format(filesize($file)),
                'modified' => date('Y-m-d H:i:s', filemtime($file))
            );
        }

        return $log_files;
    }

    /**
     * Lee el contenido de un archivo de log
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $filename Nombre del archivo.
     * @return string Contenido del archivo.
     */
    public function read_log_file($filename) {
        $log_dir = WP_CONTENT_DIR . '/uploads/whatscommerce/logs';
        $file_path = $log_dir . '/' . basename($filename);
        
        if (!file_exists($file_path)) {
            return '';
        }

        return file_get_contents($file_path);
    }

    /**
     * Limpia los archivos de log antiguos
     *
     * @since 1.0.0
     * @access public
     */
    public function cleanup_old_logs() {
        $settings = new SettingsManager();
        $retention_days = (int) $settings->get_setting('log_retention_days', 30);
        
        if ($retention_days <= 0) {
            return;
        }

        $log_dir = WP_CONTENT_DIR . '/uploads/whatscommerce/logs';
        $files = glob($log_dir . '/*.log');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= $retention_days * 86400) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Registra una solicitud entrante
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $request_data Datos de la solicitud.
     */
    public function log_request($request_data) {
        $this->info('Incoming WhatsApp request', [
            'from' => $request_data['From'] ?? 'unknown',
            'body' => $request_data['Body'] ?? '',
            'timestamp' => current_time('mysql')
        ]);
    }

    /**
     * Registra una respuesta saliente
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $phone_number Número de teléfono del destinatario.
     * @param string $message Mensaje de respuesta.
     */
    public function log_response($phone_number, $message) {
        $this->info('Outgoing WhatsApp message', [
            'to' => $phone_number,
            'message' => $message,
            'timestamp' => current_time('mysql')
        ]);
    }

    /**
     * Registra la creación de un pedido
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $order_id ID del pedido.
     * @param int $customer_id ID del cliente.
     */
    public function log_order_creation($order_id, $customer_id) {
        $this->info('New order created', [
            'order_id' => $order_id,
            'customer_id' => $customer_id,
            'timestamp' => current_time('mysql')
        ]);
    }

    /**
     * Registra un error de WhatsCommerce
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $error_message Mensaje de error.
     * @param array $context Contexto adicional.
     */
    public function log_error($error_message, $context = []) {
        $this->error('WhatsCommerce error', array_merge(
            ['error_message' => $error_message],
            $context
        ));
    }
}
