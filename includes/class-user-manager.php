<?php
/**
 * Gestión de usuarios de WhatsCommerce
 *
 * Esta clase maneja todas las operaciones relacionadas con usuarios
 * de WhatsApp, incluyendo la creación, actualización y vinculación
 * con usuarios de WooCommerce.
 *
 * @package WhatsCommerce
 * @subpackage Users
 * @since 1.0.0
 */
class UserManager {
    /**
     * Prefijo para los metadatos de usuario
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $meta_prefix = 'whatscommerce_';

    /**
     * Constructor de la clase
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        // Constructor vacío por ahora
    }

    /**
     * Busca o crea un usuario por su número de teléfono
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $phone_number Número de teléfono del usuario.
     * @return array|WP_Error Datos del usuario o error.
     */
    public function find_or_create_user($phone_number) {
        WhatsCommerceLogger::get_instance()->info('Buscando usuario por teléfono', [
            'phone_number' => $phone_number
        ]);

        $user = $this->get_user_by_phone($phone_number);
        
        if ($user) {
            WhatsCommerceLogger::get_instance()->debug('Usuario encontrado', [
                'user_id' => $user->ID
            ]);
            return array(
                'user_id' => $user->ID,
                'is_new' => false,
                'customer_id' => $this->get_customer_id($user->ID)
            );
        }

        // Crear nuevo usuario
        $username = 'whatsapp_' . preg_replace('/[^0-9]/', '', $phone_number);
        $email = $username . '@whatscommerce.local';
        
        $user_id = wp_create_user($username, wp_generate_password(), $email);

        if (is_wp_error($user_id)) {
            WhatsCommerceLogger::get_instance()->error('Error creando usuario', [
                'error' => $user_id->get_error_message()
            ]);
            return $user_id;
        }

        // Guardar metadatos
        update_user_meta($user_id, $this->meta_prefix . 'phone', $phone_number);
        update_user_meta($user_id, $this->meta_prefix . 'source', 'whatsapp');
        
        // Crear cliente de WooCommerce
        $customer_id = $this->create_wc_customer($user_id, $phone_number);

        WhatsCommerceLogger::get_instance()->info('Usuario creado exitosamente', [
            'user_id' => $user_id,
            'customer_id' => $customer_id
        ]);

        return array(
            'user_id' => $user_id,
            'is_new' => true,
            'customer_id' => $customer_id
        );
    }

    /**
     * Obtiene un usuario por su número de teléfono
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $phone_number Número de teléfono del usuario.
     * @return WP_User|null Usuario encontrado o null.
     */
    public function get_user_by_phone($phone_number) {
        $users = get_users(array(
            'meta_key' => $this->meta_prefix . 'phone',
            'meta_value' => $phone_number,
            'number' => 1
        ));

        return !empty($users) ? $users[0] : null;
    }

    /**
     * Crea un cliente de WooCommerce
     *
     * @since 1.0.0
     * @access private
     *
     * @param int $user_id ID del usuario de WordPress.
     * @param string $phone_number Número de teléfono del usuario.
     * @return int ID del cliente de WooCommerce.
     */
    private function create_wc_customer($user_id, $phone_number) {
        $customer = new WC_Customer($user_id);
        
        // Configurar datos básicos
        $customer->set_billing_phone($phone_number);
        $customer->set_role('customer');
        
        $customer->save();
        
        return $customer->get_id();
    }

    /**
     * Obtiene el ID de cliente de WooCommerce
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $user_id ID del usuario de WordPress.
     * @return int|null ID del cliente o null si no existe.
     */
    public function get_customer_id($user_id) {
        $customer = new WC_Customer($user_id);
        return $customer->get_id() ? $customer->get_id() : null;
    }

    /**
     * Actualiza los datos de un usuario
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $user_id ID del usuario.
     * @param array $data Datos a actualizar.
     * @return bool True si se actualizó correctamente.
     */
    public function update_user_data($user_id, $data) {
        WhatsCommerceLogger::get_instance()->info('Actualizando datos de usuario', [
            'user_id' => $user_id
        ]);

        $customer = new WC_Customer($user_id);

        if (isset($data['first_name'])) {
            $customer->set_first_name($data['first_name']);
        }
        
        if (isset($data['last_name'])) {
            $customer->set_last_name($data['last_name']);
        }
        
        if (isset($data['email'])) {
            $customer->set_email($data['email']);
        }
        
        if (isset($data['phone'])) {
            $customer->set_billing_phone($data['phone']);
        }

        try {
            $customer->save();
            return true;
        } catch (Exception $e) {
            WhatsCommerceLogger::get_instance()->error('Error actualizando usuario', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Verifica si un usuario existe
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $phone_number Número de teléfono del usuario.
     * @return bool True si el usuario existe.
     */
    public function user_exists($phone_number) {
        return $this->get_user_by_phone($phone_number) !== null;
    }

    /**
     * Obtiene los metadatos de un usuario
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $user_id ID del usuario.
     * @return array Metadatos del usuario.
     */
    public function get_user_metadata($user_id) {
        $metadata = array();
        $user_meta = get_user_meta($user_id);

        foreach ($user_meta as $key => $value) {
            if (strpos($key, $this->meta_prefix) === 0) {
                $clean_key = str_replace($this->meta_prefix, '', $key);
                $metadata[$clean_key] = $value[0];
            }
        }

        return $metadata;
    }
}
