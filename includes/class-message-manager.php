<?php
/**
 * Gestión de mensajes de WhatsCommerce
 *
 * Esta clase maneja todos los mensajes y plantillas utilizados
 * en la comunicación con los usuarios a través de WhatsApp,
 * permitiendo personalización y traducción de mensajes.
 *
 * @package WhatsCommerce
 * @subpackage Messages
 * @since 1.0.0
 */
class MessageManager {
    /**
     * Plantillas de mensajes predefinidos
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $templates = array();

    /**
     * Constructor de la clase
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        $this->load_default_templates();
    }

    /**
     * Carga las plantillas predeterminadas
     *
     * @since 1.0.0
     * @access private
     */
    private function load_default_templates() {
        $this->templates = array(
            'welcome' => '¡Bienvenido a nuestra tienda! 🛍️\n\n' .
                        '1️⃣ Repetir último pedido\n' .
                        '2️⃣ Buscar productos\n' .
                        '3️⃣ Ver estado de mi pedido\n' .
                        '4️⃣ Hablar con un asesor',

            'registration_needed' => 'Para continuar, necesitamos registrar tu número. ' .
                                   '¿Aceptas nuestros términos y condiciones? (Si/No)',

            'registration_success' => '¡Gracias por registrarte! 🎉',

            'main_menu' => '¿Qué te gustaría hacer?\n\n' .
                          '1️⃣ Hacer un pedido\n' .
                          '2️⃣ Ver mi último pedido\n' .
                          '3️⃣ Contactar soporte',

            'no_previous_order' => 'No encontramos pedidos anteriores. ' .
                                 '¿Qué producto te gustaría buscar?',

            'previous_order_found' => "Aquí está tu último pedido:\n\n{order_summary}\n\n" .
                                    '¿Te gustaría repetir este pedido? (Si/No)',

            'product_search' => '¿Qué producto estás buscando? ' .
                              'Puedes escribir el nombre o una descripción.',

            'no_products_found' => 'Lo siento, no encontramos productos que coincidan ' .
                                 'con tu búsqueda. ¿Podrías intentar con otros términos?',

            'add_to_cart_success' => '✅ ¡Producto agregado al carrito!',

            'add_to_cart_error' => '❌ Lo siento, no pudimos agregar el producto. ' .
                                 'Por favor, intenta nuevamente.',

            'confirm_order' => "Por favor revisa tu pedido:\n\n{order_summary}\n\n" .
                             '¿Deseas confirmar este pedido? (Si/No)',

            'order_confirmed' => "🎉 ¡Tu pedido #{order_number} ha sido confirmado!\n\n",

            'payment_instructions' => "Para completar tu pedido, realiza el pago mediante:\n\n" .
                                   "1️⃣ Transferencia bancaria\n" .
                                   "2️⃣ Pago en línea\n" .
                                   "3️⃣ Pago contra entrega",

            'order_complete' => '¡Gracias por tu compra! 🙏\n' .
                              'Te notificaremos cuando tu pedido esté en camino.',

            'error' => 'Lo siento, ocurrió un error. Por favor, intenta nuevamente ' .
                      'o contacta a nuestro soporte.'
        );
    }

    /**
     * Obtiene un mensaje por su clave
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $key Clave del mensaje.
     * @param array $params Parámetros para reemplazar en el mensaje.
     * @return string Mensaje formateado.
     */
    public function get_message($key, $params = array()) {
        WhatsCommerceLogger::get_instance()->debug('Obteniendo mensaje', [
            'key' => $key,
            'params' => $params
        ]);

        if (!isset($this->templates[$key])) {
            WhatsCommerceLogger::get_instance()->warning('Plantilla de mensaje no encontrada', [
                'key' => $key
            ]);
            return '';
        }

        $message = $this->templates[$key];

        // Reemplazar parámetros
        foreach ($params as $param => $value) {
            $message = str_replace('{' . $param . '}', $value, $message);
        }

        return $message;
    }

    /**
     * Establece una plantilla de mensaje personalizada
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $key Clave del mensaje.
     * @param string $template Plantilla del mensaje.
     * @return bool True si se estableció correctamente.
     */
    public function set_template($key, $template) {
        WhatsCommerceLogger::get_instance()->info('Estableciendo plantilla de mensaje', [
            'key' => $key
        ]);

        if (empty($key) || empty($template)) {
            WhatsCommerceLogger::get_instance()->warning('Intento de establecer plantilla inválida', [
                'key' => $key
            ]);
            return false;
        }

        $this->templates[$key] = $template;
        return true;
    }

    /**
     * Obtiene todas las plantillas de mensajes
     *
     * @since 1.0.0
     * @access public
     *
     * @return array Lista de plantillas.
     */
    public function get_all_templates() {
        return $this->templates;
    }

    /**
     * Restaura las plantillas predeterminadas
     *
     * @since 1.0.0
     * @access public
     */
    public function restore_default_templates() {
        WhatsCommerceLogger::get_instance()->info('Restaurando plantillas predeterminadas');
        $this->load_default_templates();
    }
}
