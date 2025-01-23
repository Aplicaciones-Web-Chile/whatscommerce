<?php
namespace WhatsCommerce;

/**
 * Clase principal del plugin WhatsCommerce
 *
 * Esta es la clase principal que inicializa y coordina todas las
 * funcionalidades del plugin WhatsCommerce. Maneja la carga de
 * dependencias, inicialización de servicios y gestión del ciclo
 * de vida del plugin.
 *
 * @package WhatsCommerce
 * @since 1.0.0
 */
class WhatsCommerce {
    /**
     * Instancia única del plugin
     *
     * @since 1.0.0
     * @access private
     * @var WhatsCommerce
     */
    private static $instance = null;

    /**
     * Gestor de configuraciones
     *
     * @since 1.0.0
     * @access private
     * @var SettingsManager
     */
    private $settings_manager;

    /**
     * Gestor de productos
     *
     * @since 1.0.0
     * @access private
     * @var ProductManager
     */
    private $product_manager;

    /**
     * Gestor de pedidos
     *
     * @since 1.0.0
     * @access private
     * @var OrderManager
     */
    private $order_manager;

    /**
     * Servicio de Twilio
     *
     * @since 1.0.0
     * @access private
     * @var TwilioService
     */
    private $twilio_service;

    /**
     * Manejador de webhooks
     *
     * @since 1.0.0
     * @access private
     * @var WhatsCommerceWebhookHandler
     */
    private $webhook_handler;

    /**
     * Constructor de la clase
     *
     * @since 1.0.0
     * @access private
     * @param TwilioService $twilio_service Servicio de Twilio (opcional).
     */
    private function __construct(TwilioService $twilio_service = null) {
        if ($twilio_service) {
            $this->twilio_service = $twilio_service;
        }
    }

    /**
     * Obtiene la instancia única del plugin
     *
     * @since 1.0.0
     * @access public
     *
     * @param TwilioService $twilio_service Servicio de Twilio (opcional).
     * @return WhatsCommerce Instancia del plugin.
     */
    public static function get_instance($twilio_service = null) {
        if (null === self::$instance) {
            self::$instance = new self($twilio_service);
        } elseif ($twilio_service !== null) {
            self::$instance->twilio_service = $twilio_service;
        }
        return self::$instance;
    }

    /**
     * Establece el servicio de Twilio
     *
     * @since 1.0.0
     * @access public
     *
     * @param TwilioService $twilio_service Servicio de Twilio.
     */
    public function set_twilio_service(TwilioService $twilio_service) {
        $this->twilio_service = $twilio_service;
    }

    /**
     * Inicializa el plugin
     *
     * Este método se llama después de que WordPress está listo.
     * Configura los hooks y carga las dependencias necesarias.
     *
     * @since 1.0.0
     * @access public
     */
    public function init() {
        $this->load_dependencies();
        $this->initialize_components();
        $this->setup_hooks();
    }

    /**
     * Carga las dependencias necesarias
     *
     * @since 1.0.0
     * @access private
     */
    private function load_dependencies() {
        // 1. Cargar el logger primero ya que otros componentes lo usan
        require_once plugin_dir_path(__FILE__) . 'class-whatscommerce-logger.php';
        
        // 2. Cargar clases base que no tienen dependencias
        require_once plugin_dir_path(__FILE__) . 'class-message-manager.php';
        require_once plugin_dir_path(__FILE__) . 'class-conversation-state.php';
        require_once plugin_dir_path(__FILE__) . 'class-user-manager.php';
        
        // 3. Cargar el gestor de configuraciones que depende de MessageManager
        require_once plugin_dir_path(__FILE__) . 'class-settings-manager.php';
        
        // 4. Cargar servicios que dependen de la configuración
        require_once plugin_dir_path(__FILE__) . 'class-twilio-service.php';
        
        // 5. Cargar gestores que dependen de los servicios
        require_once plugin_dir_path(__FILE__) . 'class-product-manager.php';
        require_once plugin_dir_path(__FILE__) . 'class-order-manager.php';
        require_once plugin_dir_path(__FILE__) . 'class-webhook-handler.php';
    }

    /**
     * Inicializa los componentes del plugin
     *
     * @since 1.0.0
     * @access private
     */
    private function initialize_components() {
        try {
            // 1. Inicializar el logger primero
            WhatsCommerceLogger::get_instance();

            // 2. Inicializar los componentes base
            $message_manager = MessageManager::get_instance();

            // 3. Inicializar el gestor de configuraciones
            $this->settings_manager = new SettingsManager();

            // 4. Obtener e inicializar el servicio de Twilio
            $account_sid = $this->settings_manager->get_option('twilio_account_sid');
            $auth_token = $this->settings_manager->get_option('twilio_auth_token');
            $whatsapp_number = $this->settings_manager->get_option('whatsapp_number');
            
            if (!empty($account_sid) && !empty($auth_token) && !empty($whatsapp_number)) {
                $this->twilio_service = new TwilioService($account_sid, $auth_token, $whatsapp_number);
            }

            // 5. Inicializar los gestores que dependen de otros servicios
            $this->product_manager = new ProductManager();
            $this->order_manager = new OrderManager();
            $this->webhook_handler = new WhatsCommerceWebhookHandler($this, $this->twilio_service);

        } catch (\Exception $e) {
            error_log('WhatsCommerce Error: Error al inicializar componentes - ' . $e->getMessage());
            
            add_action('admin_notices', function() use ($e) {
                echo '<div class="error"><p>';
                echo esc_html__('Error al inicializar WhatsCommerce: ', 'whatscommerce') . esc_html($e->getMessage());
                echo '</p></div>';
            });
        }
    }

    /**
     * Configura los hooks de WordPress
     *
     * @since 1.0.0
     * @access private
     */
    private function setup_hooks() {
        // Hooks de activación y desactivación
        register_activation_hook(WHATSCOMMERCE_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(WHATSCOMMERCE_PLUGIN_FILE, array($this, 'deactivate'));

        // Hooks de administración
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this->settings_manager, 'register_settings'));

        // Hooks de webhook
        add_action('rest_api_init', array($this->webhook_handler, 'register_webhook_endpoint'));
    }

    /**
     * Activa el plugin
     *
     * @since 1.0.0
     * @access public
     */
    public function activate() {
        error_log('WhatsCommerce: Plugin activado');
        
        // Crear tablas personalizadas si es necesario
        $this->create_custom_tables();
        
        // Configurar roles y capacidades
        $this->setup_roles_capabilities();
        
        // Crear páginas necesarias
        $this->create_plugin_pages();
        
        // Limpiar caché de rutas
        flush_rewrite_rules();
    }

    /**
     * Desactiva el plugin
     *
     * @since 1.0.0
     * @access public
     */
    public function deactivate() {
        error_log('WhatsCommerce: Plugin desactivado');
        
        // Limpiar datos temporales
        $this->cleanup_temporary_data();
        
        // Limpiar caché de rutas
        flush_rewrite_rules();
    }

    /**
     * Agrega el menú de administración
     *
     * @since 1.0.0
     * @access public
     */
    public function add_admin_menu() {
        add_menu_page(
            'WhatsCommerce',
            'WhatsCommerce',
            'manage_options',
            'whatscommerce',
            array($this->settings_manager, 'render_settings_page'),
            'dashicons-whatsapp',
            56
        );
    }

    /**
     * Renderiza la página principal de administración
     *
     * @since 1.0.0
     * @access public
     */
    public function render_admin_page() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }

        // Incluir template
        require_once WHATSCOMMERCE_PLUGIN_DIR . 'admin/templates/admin-page.php';
    }

    /**
     * Renderiza la página de configuración
     *
     * @since 1.0.0
     * @access public
     */
    public function render_settings_page() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }

        // Incluir template
        require_once WHATSCOMMERCE_PLUGIN_DIR . 'admin/templates/settings-page.php';
    }

    /**
     * Registra las configuraciones de WordPress
     *
     * @since 1.0.0
     * @access public
     */
    public function register_settings() {
        $this->settings_manager->register_settings();
    }

    /**
     * Crea las tablas personalizadas necesarias
     *
     * @since 1.0.0
     * @access private
     */
    private function create_custom_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de conversaciones
        $table_name = $wpdb->prefix . 'whatscommerce_conversations';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            phone_number varchar(20) NOT NULL,
            state varchar(50) NOT NULL DEFAULT 'initial',
            context longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY phone_number (phone_number)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Configura roles y capacidades
     *
     * @since 1.0.0
     * @access private
     */
    private function setup_roles_capabilities() {
        // Añadir capacidades al rol de administrador
        $role = get_role('administrator');
        $role->add_cap('manage_whatscommerce');
        $role->add_cap('view_whatscommerce_reports');
    }

    /**
     * Crea las páginas necesarias del plugin
     *
     * @since 1.0.0
     * @access private
     */
    private function create_plugin_pages() {
        // Crear página de términos y condiciones si no existe
        if (null === get_page_by_path('whatscommerce-terms')) {
            wp_insert_post(array(
                'post_title' => 'Términos y Condiciones de WhatsCommerce',
                'post_name' => 'whatscommerce-terms',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => 'Términos y condiciones del servicio de WhatsCommerce...'
            ));
        }
    }

    /**
     * Limpia datos temporales
     *
     * @since 1.0.0
     * @access private
     */
    private function cleanup_temporary_data() {
        global $wpdb;
        
        // Limpiar caché
        wp_cache_flush();
        
        // Limpiar datos temporales de la base de datos
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%_whatscommerce_temp_%'");
    }

    /**
     * Obtiene el gestor de configuraciones
     *
     * @since 1.0.0
     * @access public
     *
     * @return SettingsManager Instancia del gestor de configuraciones.
     */
    public function get_settings_manager() {
        return $this->settings_manager;
    }

    /**
     * Obtiene el gestor de productos
     *
     * @since 1.0.0
     * @access public
     *
     * @return ProductManager Instancia del gestor de productos.
     */
    public function get_product_manager() {
        return $this->product_manager;
    }

    /**
     * Obtiene el gestor de pedidos
     *
     * @since 1.0.0
     * @access public
     *
     * @return OrderManager Instancia del gestor de pedidos.
     */
    public function get_order_manager() {
        return $this->order_manager;
    }

    /**
     * Obtiene el servicio de Twilio
     *
     * @since 1.0.0
     * @access public
     *
     * @return TwilioService Instancia del servicio de Twilio.
     */
    public function get_twilio_service() {
        return $this->twilio_service;
    }

    /**
     * Obtiene el manejador de webhooks
     *
     * @since 1.0.0
     * @access public
     *
     * @return WhatsCommerceWebhookHandler Instancia del manejador de webhooks.
     */
    public function get_webhook_handler() {
        return $this->webhook_handler;
    }
}