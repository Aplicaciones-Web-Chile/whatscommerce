<?php
use Twilio\Rest\Client;

/**
 * Servicio de integración con Twilio
 *
 * Esta clase maneja toda la comunicación con la API de Twilio,
 * incluyendo el envío de mensajes, validación de webhooks y
 * gestión de respuestas.
 *
 * @package WhatsCommerce
 * @subpackage Integration
 * @since 1.0.0
 */
class TwilioService
{
    /**
     * Cliente de Twilio
     *
     * @since 1.0.0
     * @access private
     * @var Client
     */
    private $client;

    /**
     * Número de WhatsApp de Twilio
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $twilio_number;

    /**
     * Token de autenticación de Twilio
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $twilio_token;

    /**
     * URL del webhook
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $webhook_url;

    /**
     * Constructor de la clase
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $sid Account SID de Twilio.
     * @param string $token Token de autenticación de Twilio.
     * @param string $number Número de WhatsApp de Twilio.
     */
    public function __construct($sid, $token, $number)
    {
        $this->client = new Client($sid, $token);
        $this->twilio_number = $number;
        $this->twilio_token = $token;
    }

    /**
     * Obtiene la URL del webhook
     *
     * @since 1.0.0
     * @access private
     *
     * @return string URL del webhook.
     */
    private function get_webhook_url()
    {
        if (!$this->webhook_url) {
            $this->webhook_url = rest_url('whatscommerce/v1/webhook');
        }
        return $this->webhook_url;
    }

    /**
     * Envía un mensaje a través de WhatsApp
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $to Número de teléfono del destinatario.
     * @param string $message Contenido del mensaje.
     * @return mixed Respuesta de Twilio o false en caso de error.
     */
    public function send_message($to, $message)
    {
        try {
            return $this->client->messages->create(
                "whatsapp:$to",
                [
                    'from' => "whatsapp:{$this->twilio_number}",
                    'body' => $message
                ]
            );
        } catch (Exception $e) {
            error_log("Error WhatsCommerce: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Esperar respuesta del usuario
     */
    public function wait_for_response()
    {
        // Implementación de lógica para recibir respuesta del usuario
    }

    /**
     * Valida la firma de una solicitud de Twilio
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $signature Firma X-Twilio-Signature.
     * @param string $url URL del webhook.
     * @param array $params Parámetros de la solicitud.
     * @return bool True si la firma es válida.
     */
    public function validate_request($signature, $url, $params)
    {
        $validator = new Twilio\Security\RequestValidator($this->twilio_token);
        return $validator->validate($signature, $url, $params);
    }
}