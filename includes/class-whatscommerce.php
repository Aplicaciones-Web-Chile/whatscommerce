<?php
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
     */
    private function __construct() {
        $this->load_dependencies();
        $this->initialize_components();
        $this->setup_hooks();
    }

    /**
     * Obtiene la instancia única del plugin
     *
     * @since 1.0.0
     * @access public
     *
     * @return WhatsCommerce Instancia del plugin.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Carga las dependencias necesarias
     *
     * @since 1.0.0
     * @access private
     */
    private function load_dependencies() {
        require_once plugin_dir_path(__FILE__) . 'class-settings-manager.php';
        require_once plugin_dir_path(__FILE__) . 'class-product-manager.php';
        require_once plugin_dir_path(__FILE__) . 'class-order-manager.php';
        require_once plugin_dir_path(__FILE__) . 'class-twilio-service.php';
        require_once plugin_dir_path(__FILE__) . 'class-webhook-handler.php';
        require_once plugin_dir_path(__FILE__) . 'class-conversation-state.php';
        require_once plugin_dir_path(__FILE__) . 'class-logger.php';
    }

    /**
     * Inicializa los componentes del plugin
     *
     * @since 1.0.0
     * @access private
     */
    private function initialize_components() {
        // Inicializar el logger primero
        WhatsCommerceLogger::get_instance();

        // Inicializar componentes principales
        $this->settings_manager = new SettingsManager();
        $this->product_manager = new ProductManager();
        $this->order_manager = new OrderManager();
        $this->twilio_service = new TwilioService($this->settings_manager);
        $this->webhook_handler = new WhatsCommerceWebhookHandler($this->twilio_service);

        WhatsCommerceLogger::get_instance()->info('Componentes inicializados');
    }

    /**
     * Configura los hooks de WordPress
     *
     * @since 1.0.0
     * @access private
     */
    private function setup_hooks() {
        // Hooks de activación/desactivación
        register_activation_hook(WHATSCOMMERCE_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(WHATSCOMMERCE_PLUGIN_FILE, array($this, 'deactivate'));

        // Hooks de admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Hooks de REST API
        add_action('rest_api_init', array($this->webhook_handler, 'register_routes'));

        WhatsCommerceLogger::get_instance()->info('Hooks configurados');
    }

    /**
     * Activa el plugin
     *
     * @since 1.0.0
     * @access public
     */
    public function activate() {
        WhatsCommerceLogger::get_instance()->info('Plugin activado');
        
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
        WhatsCommerceLogger::get_instance()->info('Plugin desactivado');
        
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
            array($this, 'render_admin_page'),
            'dashicons-whatsapp',
            56
        );

        add_submenu_page(
            'whatscommerce',
            'Configuración',
            'Configuración',
            'manage_options',
            'whatscommerce-settings',
            array($this, 'render_settings_page')
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
        require_once plugin_dir_path(WHATSCOMMERCE_PLUGIN_FILE) . 'admin/templates/admin-page.php';
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
        require_once plugin_dir_path(WHATSCOMMERCE_PLUGIN_FILE) . 'admin/templates/settings-page.php';
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