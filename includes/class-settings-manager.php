<?php
namespace WhatsCommerce;

/**
 * Gestión de configuraciones de WhatsCommerce
 *
 * Esta clase maneja todas las configuraciones del plugin WhatsCommerce,
 * incluyendo credenciales de Twilio, mensajes predeterminados y otras
 * opciones de configuración.
 *
 * @package WhatsCommerce
 * @subpackage Settings
 * @since 1.0.0
 */
class SettingsManager {
    /**
     * Grupo de opciones para el plugin
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $option_group = 'whatscommerce_settings';

    /**
     * Página de opciones del plugin
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $page = 'whatscommerce';

    /**
     * Constructor de la clase
     *
     * Inicializa los hooks necesarios para la página de configuración.
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Agrega la página de configuración al menú de WordPress
     *
     * @since 1.0.0
     * @access public
     */
    public function add_settings_page() {
        add_menu_page(
            'WhatsCommerce',
            'WhatsCommerce',
            'manage_options',
            $this->page,
            array($this, 'render_settings_page'),
            'dashicons-whatsapp',
            30
        );
    }

    /**
     * Registra todas las configuraciones del plugin
     *
     * @since 1.0.0
     * @access public
     */
    public function register_settings() {
        // Sección de Twilio
        add_settings_section(
            'whatscommerce_twilio_section',
            'Configuración de Twilio',
            array($this, 'render_twilio_section'),
            $this->page
        );

        // Campos de Twilio
        $this->add_field('twilio_account_sid', 'Account SID', 'text');
        $this->add_field('twilio_auth_token', 'Auth Token', 'password');
        $this->add_field('twilio_phone_number', 'Número de WhatsApp', 'text');

        // Sección de Mensajes
        add_settings_section(
            'whatscommerce_messages_section',
            'Mensajes Personalizados',
            array($this, 'render_messages_section'),
            $this->page
        );

        // Campos de mensajes personalizables
        $message_manager = new MessageManager();
        $default_messages = $message_manager->get_all_messages();

        foreach ($default_messages as $key => $default_message) {
            $this->add_field(
                'message_' . $key,
                ucfirst(str_replace('_', ' ', $key)),
                'textarea',
                $default_message
            );
        }

        // Sección de Configuración General
        add_settings_section(
            'whatscommerce_general_section',
            'Configuración General',
            array($this, 'render_general_section'),
            $this->page
        );

        // Campos generales
        $this->add_field('enable_logging', 'Activar registro de eventos', 'checkbox');
        $this->add_field('log_retention_days', 'Días de retención de logs', 'number', '30');
        $this->add_field('max_products_search', 'Máximo de productos en búsqueda', 'number', '5');
    }

    /**
     * Agrega un campo de configuración
     *
     * @since 1.0.0
     * @access private
     *
     * @param string $id ID del campo.
     * @param string $label Etiqueta del campo.
     * @param string $type Tipo de campo (text, password, textarea, etc.).
     * @param string $default Valor predeterminado.
     */
    private function add_field($id, $label, $type, $default = '') {
        register_setting(
            $this->option_group,
            'whatscommerce_' . $id,
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_field'),
                'default' => $default
            )
        );

        add_settings_field(
            'whatscommerce_' . $id,
            $label,
            array($this, 'render_field'),
            $this->page,
            'whatscommerce_' . explode('_', $id)[0] . '_section',
            array(
                'id' => 'whatscommerce_' . $id,
                'type' => $type,
                'label_for' => 'whatscommerce_' . $id,
                'default' => $default
            )
        );
    }

    /**
     * Renderiza la sección de Twilio
     *
     * @since 1.0.0
     * @access public
     */
    public function render_twilio_section() {
        echo '<p>Configura tus credenciales de Twilio para habilitar la comunicación por WhatsApp.</p>';
    }

    /**
     * Renderiza la sección de mensajes
     *
     * @since 1.0.0
     * @access public
     */
    public function render_messages_section() {
        echo '<p>Personaliza los mensajes que se enviarán a tus clientes.</p>';
    }

    /**
     * Renderiza la sección general
     *
     * @since 1.0.0
     * @access public
     */
    public function render_general_section() {
        echo '<p>Configuraciones generales del plugin.</p>';
    }

    /**
     * Renderiza un campo de configuración
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $args Argumentos del campo.
     */
    public function render_field($args) {
        $id = $args['id'];
        $type = $args['type'];
        $value = get_option($id, $args['default']);

        switch ($type) {
            case 'textarea':
                printf(
                    '<textarea class="large-text" id="%s" name="%s" rows="3">%s</textarea>',
                    esc_attr($id),
                    esc_attr($id),
                    esc_textarea($value)
                );
                break;

            case 'checkbox':
                printf(
                    '<input type="checkbox" id="%s" name="%s" value="1" %s>',
                    esc_attr($id),
                    esc_attr($id),
                    checked(1, $value, false)
                );
                break;

            default:
                printf(
                    '<input type="%s" class="regular-text" id="%s" name="%s" value="%s">',
                    esc_attr($type),
                    esc_attr($id),
                    esc_attr($id),
                    esc_attr($value)
                );
        }
    }

    /**
     * Renderiza la página de configuración
     *
     * @since 1.0.0
     * @access public
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'whatscommerce_messages',
                'whatscommerce_message',
                'Configuración guardada',
                'updated'
            );
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php settings_errors('whatscommerce_messages'); ?>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->option_group);
                do_settings_sections($this->page);
                submit_button('Guardar cambios');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitiza los valores de los campos
     *
     * @since 1.0.0
     * @access public
     *
     * @param mixed $value Valor a sanitizar.
     * @return mixed Valor sanitizado.
     */
    public function sanitize_field($value) {
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }
        return sanitize_text_field($value);
    }

    /**
     * Obtiene una configuración específica
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $key Clave de la configuración.
     * @param mixed $default Valor predeterminado.
     * @return mixed Valor de la configuración.
     */
    public function get_setting($key, $default = '') {
        return get_option('whatscommerce_' . $key, $default);
    }

    /**
     * Actualiza una configuración específica
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $key Clave de la configuración.
     * @param mixed $value Nuevo valor.
     * @return bool True si se actualizó correctamente.
     */
    public function update_setting($key, $value) {
        return update_option('whatscommerce_' . $key, $value);
    }

    /**
     * Elimina una configuración específica
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $key Clave de la configuración.
     * @return bool True si se eliminó correctamente.
     */
    public function delete_setting($key) {
        return delete_option('whatscommerce_' . $key);
    }
}
