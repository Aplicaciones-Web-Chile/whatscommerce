<?php
/**
 * Gestión de pedidos de WhatsCommerce
 *
 * Esta clase maneja todas las operaciones relacionadas con pedidos
 * de WooCommerce, incluyendo creación, actualización y consulta
 * de pedidos a través de WhatsApp.
 *
 * @package WhatsCommerce
 * @subpackage Orders
 * @since 1.0.0
 */
class OrderManager {
    /**
     * Carrito temporal del usuario actual
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $cart = array();

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
     * Obtiene el último pedido de un usuario
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $user_id ID del usuario.
     * @return WC_Order|null Último pedido o null si no hay pedidos.
     */
    public function get_last_order($user_id) {
        WhatsCommerceLogger::get_instance()->info('Buscando último pedido', [
            'user_id' => $user_id
        ]);

        $orders = wc_get_orders(array(
            'customer' => $user_id,
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        if (!empty($orders)) {
            WhatsCommerceLogger::get_instance()->debug('Último pedido encontrado', [
                'order_id' => $orders[0]->get_id()
            ]);
            return $orders[0];
        }

        WhatsCommerceLogger::get_instance()->debug('No se encontraron pedidos');
        return null;
    }

    /**
     * Crea un nuevo pedido para un usuario
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $user_id ID del usuario.
     * @param array $products Lista de productos y cantidades.
     * @return WC_Order|WP_Error Pedido creado o error.
     */
    public function create_order($user_id) {
        WhatsCommerceLogger::get_instance()->info('Creando nuevo pedido', [
            'user_id' => $user_id,
            'cart' => $this->cart
        ]);

        try {
            if (empty($this->cart)) {
                throw new Exception('El carrito está vacío');
            }

            $order = wc_create_order(array(
                'customer_id' => $user_id,
                'created_via' => 'whatsapp'
            ));

            foreach ($this->cart as $product_id => $quantity) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $order->add_product($product, $quantity);
                }
            }

            $order->calculate_totals();
            $order->save();

            WhatsCommerceLogger::get_instance()->info('Pedido creado exitosamente', [
                'order_id' => $order->get_id()
            ]);

            $this->clear_cart();
            return $order;

        } catch (Exception $e) {
            WhatsCommerceLogger::get_instance()->error('Error creando pedido', [
                'error' => $e->getMessage()
            ]);
            return new WP_Error('order_creation_failed', $e->getMessage());
        }
    }

    /**
     * Agrega un producto al carrito temporal
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $product_id ID del producto.
     * @param int $quantity Cantidad a agregar.
     * @return bool True si se agregó correctamente.
     */
    public function add_to_cart($product_id, $quantity = 1) {
        WhatsCommerceLogger::get_instance()->info('Agregando producto al carrito', [
            'product_id' => $product_id,
            'quantity' => $quantity
        ]);

        $product = wc_get_product($product_id);
        if (!$product) {
            WhatsCommerceLogger::get_instance()->warning('Producto no encontrado');
            return false;
        }

        if (!$product->is_in_stock() || $product->get_stock_quantity() < $quantity) {
            WhatsCommerceLogger::get_instance()->warning('Stock insuficiente', [
                'stock_quantity' => $product->get_stock_quantity()
            ]);
            return false;
        }

        if (isset($this->cart[$product_id])) {
            $this->cart[$product_id] += $quantity;
        } else {
            $this->cart[$product_id] = $quantity;
        }

        return true;
    }

    /**
     * Elimina un producto del carrito temporal
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $product_id ID del producto.
     * @return bool True si se eliminó correctamente.
     */
    public function remove_from_cart($product_id) {
        WhatsCommerceLogger::get_instance()->info('Eliminando producto del carrito', [
            'product_id' => $product_id
        ]);

        if (isset($this->cart[$product_id])) {
            unset($this->cart[$product_id]);
            return true;
        }

        return false;
    }

    /**
     * Limpia el carrito temporal
     *
     * @since 1.0.0
     * @access public
     */
    public function clear_cart() {
        WhatsCommerceLogger::get_instance()->info('Limpiando carrito');
        $this->cart = array();
    }

    /**
     * Obtiene el contenido del carrito temporal
     *
     * @since 1.0.0
     * @access public
     *
     * @return array Contenido del carrito.
     */
    public function get_cart() {
        return $this->cart;
    }

    /**
     * Formatea el resumen de un pedido para WhatsApp
     *
     * @since 1.0.0
     * @access public
     *
     * @param WC_Order $order Pedido a formatear.
     * @return string Resumen formateado del pedido.
     */
    public function format_order_summary($order) {
        if (!$order) {
            return 'Pedido no encontrado';
        }

        $items = $order->get_items();
        $summary = "*Resumen del Pedido #{$order->get_id()}*\n\n";

        foreach ($items as $item) {
            $product = $item->get_product();
            if ($product) {
                $summary .= sprintf(
                    "%s x%d - %s\n",
                    $item->get_name(),
                    $item->get_quantity(),
                    wc_price($item->get_total())
                );
            }
        }

        $summary .= "\n*Total:* " . wc_price($order->get_total());
        $summary .= "\n*Estado:* " . wc_get_order_status_name($order->get_status());

        return $summary;
    }

    /**
     * Formatea el contenido del carrito para WhatsApp
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Contenido formateado del carrito.
     */
    public function format_cart() {
        if (empty($this->cart)) {
            return "El carrito está vacío";
        }

        $summary = "*Carrito Actual*\n\n";
        $total = 0;

        foreach ($this->cart as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            if ($product) {
                $subtotal = $product->get_price() * $quantity;
                $total += $subtotal;
                
                $summary .= sprintf(
                    "%s x%d - %s\n",
                    $product->get_name(),
                    $quantity,
                    wc_price($subtotal)
                );
            }
        }

        $summary .= "\n*Total:* " . wc_price($total);
        $summary .= "\n\n1️⃣ Confirmar pedido\n2️⃣ Modificar pedido\n3️⃣ Cancelar pedido";

        return $summary;
    }
}
