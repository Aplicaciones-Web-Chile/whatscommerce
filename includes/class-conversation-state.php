<?php
namespace WhatsCommerce;

/**
 * Gestión del estado de conversaciones de WhatsCommerce
 *
 * Esta clase maneja el estado y contexto de las conversaciones
 * de WhatsApp con los usuarios, permitiendo mantener un flujo
 * coherente en la interacción.
 *
 * @package WhatsCommerce
 * @subpackage Conversation
 * @since 1.0.0
 */
class ConversationState {
    /**
     * Estados posibles de la conversación
     */
    const STATE_INITIAL = 'initial';
    const STATE_REGISTRATION = 'registration';
    const STATE_MENU = 'menu';
    const STATE_SEARCHING = 'searching';
    const STATE_SELECTING_PRODUCT = 'selecting_product';
    const STATE_CONFIRMING_ORDER = 'confirming_order';
    const STATE_PAYMENT = 'payment';

    /**
     * Nombre de la tabla de estados
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $table_name;

    /**
     * Constructor de la clase
     *
     * Inicializa la tabla de estados y la crea si no existe.
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'whatscommerce_conversation_states';
        $this->create_table();
    }

    /**
     * Crea la tabla de estados si no existe
     *
     * @since 1.0.0
     * @access private
     */
    private function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            phone_number varchar(20) NOT NULL,
            state varchar(50) NOT NULL DEFAULT 'initial',
            context longtext,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY phone_number (phone_number)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Obtiene el estado actual de una conversación
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $phone_number Número de teléfono del usuario.
     * @return array Estado actual y contexto de la conversación.
     */
    public function get_state($phone_number) {
        global $wpdb;

        WhatsCommerceLogger::get_instance()->info('Obteniendo estado de conversación', [
            'phone_number' => $phone_number
        ]);

        $state = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT state, context FROM {$this->table_name} WHERE phone_number = %s",
                $phone_number
            )
        );

        if (!$state) {
            $this->init_state($phone_number);
            return array(
                'state' => self::STATE_INITIAL,
                'context' => json_encode(array())
            );
        }

        return array(
            'state' => $state->state,
            'context' => json_decode($state->context, true) ?: array()
        );
    }

    /**
     * Inicializa el estado de una conversación
     *
     * @since 1.0.0
     * @access private
     *
     * @param string $phone_number Número de teléfono del usuario.
     */
    private function init_state($phone_number) {
        global $wpdb;

        WhatsCommerceLogger::get_instance()->info('Inicializando estado de conversación', [
            'phone_number' => $phone_number
        ]);

        $wpdb->insert(
            $this->table_name,
            array(
                'phone_number' => $phone_number,
                'state' => self::STATE_INITIAL,
                'context' => json_encode(array())
            ),
            array('%s', '%s', '%s')
        );
    }

    /**
     * Actualiza el estado de una conversación
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $phone_number Número de teléfono del usuario.
     * @param string $new_state Nuevo estado.
     * @param array $context Contexto adicional opcional.
     * @return bool True si se actualizó correctamente, false en caso contrario.
     */
    public function update_state($phone_number, $new_state, $context = null) {
        global $wpdb;

        WhatsCommerceLogger::get_instance()->info('Actualizando estado de conversación', [
            'phone_number' => $phone_number,
            'new_state' => $new_state,
            'context' => $context
        ]);

        $data = array(
            'state' => $new_state,
            'updated_at' => current_time('mysql')
        );

        if ($context !== null) {
            $data['context'] = json_encode($context);
        }

        $result = $wpdb->update(
            $this->table_name,
            $data,
            array('phone_number' => $phone_number),
            array('%s', '%s', '%s'),
            array('%s')
        );

        return $result !== false;
    }

    /**
     * Actualiza solo el contexto de una conversación
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $phone_number Número de teléfono del usuario.
     * @param array $context Nuevo contexto.
     * @return bool True si se actualizó correctamente, false en caso contrario.
     */
    public function update_context($phone_number, $context) {
        global $wpdb;

        WhatsCommerceLogger::get_instance()->info('Actualizando contexto de conversación', [
            'phone_number' => $phone_number,
            'context' => $context
        ]);

        $result = $wpdb->update(
            $this->table_name,
            array(
                'context' => json_encode($context),
                'updated_at' => current_time('mysql')
            ),
            array('phone_number' => $phone_number),
            array('%s', '%s'),
            array('%s')
        );

        return $result !== false;
    }

    /**
     * Verifica si un estado es válido
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $state Estado a verificar.
     * @return bool True si el estado es válido, false en caso contrario.
     */
    public function is_valid_state($state) {
        return in_array($state, array(
            self::STATE_INITIAL,
            self::STATE_REGISTRATION,
            self::STATE_MENU,
            self::STATE_SEARCHING,
            self::STATE_SELECTING_PRODUCT,
            self::STATE_CONFIRMING_ORDER,
            self::STATE_PAYMENT
        ));
    }
}