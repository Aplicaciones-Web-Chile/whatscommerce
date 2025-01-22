<?php
namespace WhatsCommerce;

/**
 * Manejador de webhooks de WhatsCommerce
 *
 * Esta clase maneja todas las solicitudes webhook entrantes
 * de Twilio y procesa las respuestas de los usuarios.
 *
 * @package WhatsCommerce
 * @subpackage Webhooks
 * @since 1.0.0
 */
class WhatsCommerceWebhookHandler
{
    /**
     * Instancia principal del plugin
     *
     * @since 1.0.0
     * @access private
     * @var WhatsCommerce
     */
    private $whatscommerce;

    /**
     * Servicio de Twilio
     *
     * @since 1.0.0
     * @access private
     * @var TwilioService
     */
    private $twilio_service;

    /**
     * Constructor de la clase
     *
     * @since 1.0.0
     * @access public
     *
     * @param WhatsCommerce $whatscommerce Instancia principal del plugin.
     * @param TwilioService $twilio_service Servicio de Twilio.
     */
    public function __construct(WhatsCommerce $whatscommerce, TwilioService $twilio_service)
    {
        $this->whatscommerce = $whatscommerce;
        $this->twilio_service = $twilio_service;
    }

    /**
     * Registra el endpoint del webhook en WordPress
     *
     * @since 1.0.0
     * @access public
     */
    public function register_webhook()
    {
        add_action('rest_api_init', function () {
            register_rest_route('whatscommerce/v1', '/webhook', [
                'methods' => 'POST',
                'callback' => [$this, 'handle_webhook'],
                'permission_callback' => [$this, 'verify_webhook']
            ]);
        });
    }

    /**
     * Verifica la autenticidad de la solicitud de Twilio
     *
     * @since 1.0.0
     * @access public
     *
     * @param WP_REST_Request $request Solicitud REST de WordPress.
     * @return bool True si la solicitud es válida.
     */
    public function verify_webhook(WP_REST_Request $request)
    {
        $signature = $request->get_header('X-Twilio-Signature');
        $url = $request->get_url();
        $params = $request->get_params();

        return $this->twilio_service->validate_request($signature, $url, $params);
    }

    /**
     * Procesa los mensajes entrantes de WhatsApp
     *
     * @since 1.0.0
     * @access public
     *
     * @param WP_REST_Request $request Solicitud REST de WordPress.
     * @return WP_REST_Response Respuesta REST de WordPress.
     */
    public function handle_webhook(WP_REST_Request $request)
    {
        $body = $request->get_json_params();
        $sender = $body['From'];
        $message = $body['Body'];

        try {
            $user_id = $this->whatscommerce->check_user_in_database($sender);
            $conversation = new ConversationState($user_id);

            if ($conversation->is_expired()) {
                $conversation->clear_state();
                $this->whatscommerce->show_main_menu($user_id);
                return new WP_REST_Response(['status' => 'success'], 200);
            }

            $this->whatscommerce->process_message($user_id, $message, $conversation);
            return new WP_REST_Response(['status' => 'success'], 200);

        } catch (Exception $e) {
            error_log("Error WhatsCommerce Webhook: " . $e->getMessage());
            return new WP_Error('webhook_error', $e->getMessage(), ['status' => 500]);
        }
    }
}