<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value)
    {
        return $value;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
    {
        return true;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($value)
    {
        return $value;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value)
    {
        if (is_array($value)) {
            return '';
        }
        return trim((string)$value);
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($key, $value = null, $url = '')
    {
        if (is_array($key)) {
            $query = http_build_query($key);
            return $url . (str_contains($url, '?') ? '&' : '?') . $query;
        }

        $query = http_build_query([$key => $value]);
        return $url . (str_contains($url, '?') ? '&' : '?') . $query;
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($value)
    {
        return 'https://example.test/page/' . $value;
    }
}

if (!function_exists('wp_redirect')) {
    function wp_redirect($url)
    {
        $GLOBALS['wp_redirect_url'] = $url;
        return true;
    }
}

if (!function_exists('wc_get_checkout_url')) {
    function wc_get_checkout_url()
    {
        return 'https://epayco-checkout-testing.s3.amazonaws.com/checkout.preprod-v2.js';
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($url)
    {
        $GLOBALS['wp_safe_redirect_url'] = $url;
        return true;
    }
}

if (!function_exists('get_option')) {
    function get_option($key)
    {
        $options = $GLOBALS['wp_options'] ?? [];
        return $options[$key] ?? null;
    }
}

if (!function_exists('update_option')) {
    function update_option($key, $value)
    {
        $GLOBALS['wp_options'][$key] = $value;
        return true;
    }
}

if (!function_exists('get_woocommerce_currency')) {
    function get_woocommerce_currency()
    {
        return 'COP';
    }
}

if (!function_exists('get_pages')) {
    function get_pages($args = '')
    {
        return [];
    }
}

if (!function_exists('wc_update_product_stock')) {
    function wc_update_product_stock($product, $qty, $direction)
    {
        $GLOBALS['stock_updates'][] = [
            'product' => $product,
            'qty' => $qty,
            'direction' => $direction,
        ];
    }
}

if (!function_exists('wc_get_order')) {
    function wc_get_order($orderId)
    {
        $orders = $GLOBALS['wc_orders'] ?? [];
        return $orders[$orderId] ?? null;
    }
}

if (!function_exists('wc_get_logger')) {
    function wc_get_logger()
    {
        return new WC_Logger();
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private string $message;

        public function __construct(string $code = '', string $message = '')
        {
            $this->message = $message;
        }

        public function get_error_message()
        {
            return $this->message;
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($value)
    {
        return $value instanceof WP_Error;
    }
}

if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url)
    {
        $responses = $GLOBALS['wp_remote_get_map'] ?? [];
        return $responses[$url] ?? false;
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response)
    {
        if (is_array($response) && isset($response['body'])) {
            return $response['body'];
        }
        return '';
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response)
    {
        if (is_array($response) && isset($response['response']['code'])) {
            return (int) $response['response']['code'];
        }
        return 0;
    }
}

class WC_Logger
{
    public function add($source, $message)
    {
        $GLOBALS['wc_logs'][] = ['source' => $source, 'message' => $message];
    }
}

class WC_Payment_Gateway
{
    public array $settings = [];
    public array $form_fields = [];

    public function init_settings()
    {
        if (is_array($this->settings) && $this->settings !== []) {
            return;
        }

        $optionKey = 'woocommerce_' . ($this->id ?? 'gateway') . '_settings';
        $savedSettings = get_option($optionKey);
        $this->settings = is_array($savedSettings) ? $savedSettings : [];
    }

    public function init_form_fields()
    {
        $this->form_fields = [];
    }

    public function process_admin_options()
    {
        return true;
    }

    public function get_option($key)
    {
        return $this->settings[$key] ?? 0;
    }
}

class EpaycoOrder
{
    public static array $stockDiscountByOrder = [];

    public static function ifStockDiscount($orderId)
    {
        return !empty(self::$stockDiscountByOrder[$orderId]);
    }

    public static function updateStockDiscount($orderId, $value)
    {
        self::$stockDiscountByOrder[$orderId] = $value;
    }

    public static function ifExist($orderId)
    {
        return true;
    }

    public static function create($orderId, $value)
    {
        self::$stockDiscountByOrder[$orderId] = $value;
    }
}

class FakeProduct
{
    public string $sku;

    public function __construct(string $sku)
    {
        $this->sku = $sku;
    }
}

class FakeOrderItem
{
    private FakeProduct $product;
    private int $quantity;

    public function __construct(FakeProduct $product, int $quantity)
    {
        $this->product = $product;
        $this->quantity = $quantity;
    }

    public function get_product()
    {
        return $this->product;
    }

    public function get_quantity()
    {
        return $this->quantity;
    }
}

class WC_Order
{
    private int $id;
    private string $status;
    private array $meta = [];
    private array $items;
    private float $total;
    private ?WC_Order $linkedOrder = null;
    public ?string $paymentCompleteRef = null;

    public function __construct(int $id, string $status = 'pending', array $items = [], float $total = 0.0)
    {
        $orders = $GLOBALS['wc_orders'] ?? [];
        if (isset($orders[$id]) && $orders[$id] instanceof self && $orders[$id] !== $this) {
            $existing = $orders[$id];
            $this->linkedOrder = $existing;
            $this->id = $existing->id;
            $this->status = $existing->status;
            $this->meta = $existing->meta;
            $this->items = $existing->items;
            $this->total = $existing->total;
            $this->paymentCompleteRef = $existing->paymentCompleteRef;
            return;
        }

        $this->id = $id;
        $this->status = $status;
        $this->items = $items;
        $this->total = $total;
    }

    public function get_id()
    {
        return $this->id;
    }

    public function get_status()
    {
        return $this->status;
    }

    public function update_status($status)
    {
        $this->status = $status;
        if ($this->linkedOrder instanceof self) {
            $this->linkedOrder->status = $status;
        }
    }

    public function get_meta($key)
    {
        return $this->meta[$key] ?? '';
    }

    public function update_meta_data($key, $value)
    {
        $this->meta[$key] = $value;
        if ($this->linkedOrder instanceof self) {
            $this->linkedOrder->meta[$key] = $value;
        }
    }

    public function add_meta_data($key, $value)
    {
        $this->meta[$key] = $value;
        if ($this->linkedOrder instanceof self) {
            $this->linkedOrder->meta[$key] = $value;
        }
    }

    public function payment_complete($reference = null)
    {
        $this->paymentCompleteRef = $reference;
        if ($this->linkedOrder instanceof self) {
            $this->linkedOrder->paymentCompleteRef = $reference;
        }
    }

    public function save()
    {
        return true;
    }

    public function add_order_note($note)
    {
        $GLOBALS['order_notes'][] = $note;
    }

    public function get_total()
    {
        return $this->total;
    }

    public function get_checkout_order_received_url()
    {
        return 'https://example.test/order-received/' . $this->id;
    }

    public function get_items()
    {
        return $this->items;
    }
}

$GLOBALS['woocommerce'] = (object) [
    'cart' => new class {
        public function empty_cart()
        {
            $GLOBALS['cart_emptied'] = true;
        }
    },
];

$pluginClassesPath = dirname(__DIR__, 2) . '/wp-content/plugins/Plugin_ePayco_WooCommerce/classes/';
define('EPAYCO_PLUGIN_CLASS_PATH', $pluginClassesPath);
define('EPAYCO_PLUGIN_URL', 'https://example.test/plugin/');
define('WC_VERSION', '8.0.0');

require_once $pluginClassesPath . 'class-wc-gateway-epayco.php';