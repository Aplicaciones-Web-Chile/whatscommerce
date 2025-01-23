<?php
namespace WhatsCommerce;

use WhatsCommerce\MessageManager;

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
class SettingsManager
{
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
     * Instancia de MessageManager
     *
     * @since 1.0.0
     * @access private
     * @var MessageManager
     */
    private $message_manager;

    /**
     * Constructor de la clase
     *
     * Inicializa los hooks necesarios para la página de configuración.
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct()
    {
        $this->message_manager = MessageManager::get_instance();
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Registra todas las configuraciones del plugin
     *
     * @since 1.0.0
     * @access public
     */
    public function register_settings()
    {
        register_setting($this->option_group, $this->option_group);

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

        // Sección de Mensajes Personalizados
        add_settings_section(
            'whatscommerce_messages_section',
            'Mensajes Personalizados',
            array($this, 'render_messages_section'),
            $this->page
        );

        // Obtener y registrar campos para cada mensaje personalizable
        $default_messages = $this->message_manager->get_all_templates();
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
        $this->add_field('max_products_search', 'Máximo de productos en búsqueda', 'number', '5');
        $this->add_field('order_expiration_time', 'Tiempo de expiración de pedidos (minutos)', 'number', '30');
    }

    /**
     * Renderiza la sección de Twilio
     */
    public function render_twilio_section()
    {
        echo '<p>Configura tus credenciales de Twilio para habilitar la comunicación por WhatsApp.</p>';
    }

    /**
     * Renderiza la sección de mensajes
     */
    public function render_messages_section()
    {
        echo '<p>Personaliza los mensajes que se enviarán a tus clientes.</p>';
    }

    /**
     * Renderiza la sección general
     */
    public function render_general_section()
    {
        echo '<p>Configuración general del plugin.</p>';
    }

    /**
     * Agrega un campo de configuración
     */
    private function add_field($id, $title, $type = 'text', $default = '')
    {
        $field_id = $this->option_group . '_' . $id;
        
        add_settings_field(
            $field_id,
            $title,
            array($this, 'render_field'),
            $this->page,
            'whatscommerce_' . explode('_', $id)[0] . '_section',
            array(
                'id' => $id,
                'type' => $type,
                'default' => $default
            )
        );
    }

    /**
     * Renderiza un campo de configuración
     */
    public function render_field($args)
    {
        $id = $args['id'];
        $type = $args['type'];
        $default = $args['default'];
        $value = $this->get_option($id, $default);
        $name = $this->option_group . '[' . $id . ']';

        switch ($type) {
            case 'textarea':
                printf(
                    '<textarea class="large-text" rows="3" id="%s" name="%s">%s</textarea>',
                    esc_attr($id),
                    esc_attr($name),
                    esc_textarea($value)
                );
                break;
            
            case 'password':
                printf(
                    '<input type="password" class="regular-text" id="%s" name="%s" value="%s" autocomplete="new-password" />',
                    esc_attr($id),
                    esc_attr($name),
                    esc_attr($value)
                );
                break;
            
            case 'number':
                printf(
                    '<input type="number" class="small-text" id="%s" name="%s" value="%s" />',
                    esc_attr($id),
                    esc_attr($name),
                    esc_attr($value)
                );
                break;
            
            default:
                printf(
                    '<input type="text" class="regular-text" id="%s" name="%s" value="%s" />',
                    esc_attr($id),
                    esc_attr($name),
                    esc_attr($value)
                );
        }
    }

    /**
     * Obtiene una opción específica
     *
     * @param string $key Clave de la opción
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    public function get_option($key, $default = '')
    {
        $options = get_option($this->option_group, array());
        return isset($options[$key]) ? $options[$key] : $default;
    }

    /**
     * Renderiza la página de configuración
     *
     * @since 1.0.0
     * @access public
     */
    public function render_settings_page()
    {
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
     * Agrega la página de configuración al menú de WordPress
     *
     * @since 1.0.0
     * @access public
     */
    public function add_settings_page()
    {
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
}