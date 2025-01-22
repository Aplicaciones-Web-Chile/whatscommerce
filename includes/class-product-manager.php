<?php
/**
 * Gestión de productos de WhatsCommerce
 *
 * Esta clase maneja todas las operaciones relacionadas con productos
 * de WooCommerce, incluyendo búsqueda, verificación de stock y
 * formateo de información para WhatsApp.
 *
 * @package WhatsCommerce
 * @subpackage Products
 * @since 1.0.0
 */
class ProductManager {
    /**
     * Máximo número de productos a mostrar
     *
     * @since 1.0.0
     * @access private
     * @var int
     */
    private $max_products;

    /**
     * Constructor de la clase
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        $settings = new SettingsManager();
        $this->max_products = (int) $settings->get_setting('max_products_search', 5);
    }

    /**
     * Busca productos por término de búsqueda
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $search_term Término de búsqueda.
     * @return array Lista de productos encontrados.
     */
    public function search_products($search_term) {
        WhatsCommerceLogger::get_instance()->info('Buscando productos', [
            'search_term' => $search_term
        ]);

        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $this->max_products,
            's' => $search_term,
            'orderby' => 'relevance',
            'order' => 'DESC'
        );

        $query = new WP_Query($args);
        $products = array();

        foreach ($query->posts as $post) {
            $product = wc_get_product($post);
            if ($product && $product->is_visible()) {
                $products[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price(),
                    'stock' => $product->get_stock_quantity(),
                    'url' => $product->get_permalink()
                );
            }
        }

        WhatsCommerceLogger::get_instance()->debug('Productos encontrados', [
            'count' => count($products)
        ]);

        return $products;
    }

    /**
     * Verifica si un producto está en stock
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $product_id ID del producto.
     * @return bool True si el producto está en stock.
     */
    public function check_stock($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            WhatsCommerceLogger::get_instance()->warning('Producto no encontrado', [
                'product_id' => $product_id
            ]);
            return false;
        }

        $in_stock = $product->is_in_stock();
        
        WhatsCommerceLogger::get_instance()->debug('Verificando stock', [
            'product_id' => $product_id,
            'in_stock' => $in_stock
        ]);

        return $in_stock;
    }

    /**
     * Obtiene la cantidad en stock de un producto
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $product_id ID del producto.
     * @return int|null Cantidad en stock o null si no se encuentra el producto.
     */
    public function get_stock_quantity($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            WhatsCommerceLogger::get_instance()->warning('Producto no encontrado', [
                'product_id' => $product_id
            ]);
            return null;
        }

        return $product->get_stock_quantity();
    }

    /**
     * Formatea la información de un producto para WhatsApp
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $product_id ID del producto.
     * @return string Información formateada del producto.
     */
    public function format_product_info($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            WhatsCommerceLogger::get_instance()->warning('Producto no encontrado', [
                'product_id' => $product_id
            ]);
            return 'Producto no encontrado';
        }

        $stock_status = $product->is_in_stock() ? '✅ En stock' : '❌ Sin stock';
        if ($product->get_stock_quantity()) {
            $stock_status .= " ({$product->get_stock_quantity()} unidades)";
        }

        $info = "*{$product->get_name()}*\n";
        $info .= "Precio: " . wc_price($product->get_price()) . "\n";
        $info .= "Estado: {$stock_status}\n";
        
        if ($product->get_short_description()) {
            $info .= "\n" . wp_strip_all_tags($product->get_short_description()) . "\n";
        }
        
        $info .= "\nVer más: {$product->get_permalink()}";

        return $info;
    }

    /**
     * Formatea una lista de productos para WhatsApp
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $products Lista de productos.
     * @return string Lista formateada de productos.
     */
    public function format_product_list($products) {
        if (empty($products)) {
            return "No se encontraron productos.";
        }

        $message = "Productos encontrados:\n\n";
        foreach ($products as $index => $product) {
            $number = $index + 1;
            $price = wc_price($product['price']);
            $stock = $product['stock'] ? "({$product['stock']} disponibles)" : '';
            
            $message .= "{$number}. *{$product['name']}*\n";
            $message .= "   Precio: {$price} {$stock}\n\n";
        }

        $message .= "Envía el número del producto que te interesa para ver más detalles.";
        return $message;
    }

    /**
     * Obtiene un producto por su ID
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $product_id ID del producto.
     * @return WC_Product|null Producto o null si no se encuentra.
     */
    public function get_product($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            WhatsCommerceLogger::get_instance()->warning('Producto no encontrado', [
                'product_id' => $product_id
            ]);
            return null;
        }

        return $product;
    }
}
