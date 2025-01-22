<?php
namespace WhatsCommerce;

use Twilio\Rest\Client;
use Exception;

/**
 * Servicio de Twilio para WhatsCommerce
 *
 * Esta clase maneja todas las interacciones con la API de Twilio,
 * incluyendo el envío de mensajes y la validación de webhooks.
 *
 * @package WhatsCommerce
 * @subpackage Services
 * @since 1.0.0
 */
class TwilioService {
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
    private $whatsapp_number;

    /**
     * Constructor de la clase
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $account_sid SID de la cuenta de Twilio.
     * @param string $auth_token Token de autenticación de Twilio.
     * @param string $whatsapp_number Número de WhatsApp configurado en Twilio.
     * @throws Exception Si faltan credenciales o hay error de conexión.
     */
    public function __construct($account_sid, $auth_token, $whatsapp_number) {
        if (empty($account_sid) || empty($auth_token) || empty($whatsapp_number)) {
            throw new Exception('Faltan credenciales de Twilio');
        }

        try {
            $this->client = new Client($account_sid, $auth_token);
            $this->whatsapp_number = $whatsapp_number;
        } catch (Exception $e) {
            throw new Exception('Error al conectar con Twilio: ' . $e->getMessage());
        }
    }

    /**
     * Envía un mensaje de WhatsApp
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $to Número de teléfono del destinatario.
     * @param string $message Mensaje a enviar.
     * @return array Respuesta de Twilio.
     * @throws Exception Si hay error al enviar el mensaje.
     */
    public function send_message($to, $message) {
        try {
            WhatsCommerceLogger::get_instance()->info('Enviando mensaje', [
                'to' => $to,
                'message_length' => strlen($message)
            ]);

            $response = $this->client->messages->create(
                "whatsapp:$to",
                [
                    'from' => "whatsapp:{$this->whatsapp_number}",
                    'body' => $message
                ]
            );

            return [
                'success' => true,
                'message_id' => $response->sid,
                'status' => $response->status
            ];

        } catch (Exception $e) {
            WhatsCommerceLogger::get_instance()->error('Error enviando mensaje', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            throw new Exception('Error al enviar mensaje: ' . $e->getMessage());
        }
    }

    /**
     * Valida una solicitud de webhook de Twilio
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $signature Firma de la solicitud.
     * @param string $url URL del webhook.
     * @param array $params Parámetros de la solicitud.
     * @return bool True si la solicitud es válida.
     */
    public function validate_webhook_request($signature, $url, $params) {
        try {
            return $this->client->validateRequest(
                $signature,
                $url,
                $params
            );
        } catch (Exception $e) {
            WhatsCommerceLogger::get_instance()->error('Error validando webhook', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtiene la URL del webhook de Twilio
     *
     * @since 1.0.0
     * @access public
     *
     * @return string URL del webhook.
     */
    public function get_webhook_url() {
        return rest_url('whatscommerce/v1/webhook');
    }
}