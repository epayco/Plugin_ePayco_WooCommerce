<?php
namespace Epayco\Woocommerce\Hooks;

use Exception;
use Epayco\Woocommerce\Helpers\PaymentStatus;
use Epayco\Woocommerce\Helpers\CurrentUser;
use Epayco\Woocommerce\Order\OrderMetadata;
use Epayco\Woocommerce\Hooks\Template;
use Epayco\Woocommerce\Configs\Seller;
use Epayco\Woocommerce\Configs\Store;
use Epayco\Woocommerce\Helpers\Url;
use Epayco\Woocommerce\Translations\AdminTranslations;
use Epayco\Woocommerce\Translations\StoreTranslations;
use Epayco\Woocommerce\Helpers\Cron;
use WC_Order;
use WP_Post;
use Epayco as EpaycoSdk;

if (!defined('ABSPATH')) {
    exit;
}

class Order
{

    private const NONCE_ID = 'EP_ORDER_NONCE';
    /**
     * Order constructor
     * @param Template $template
     * @param OrderMetadata $orderMetadata
     * @param AdminTranslations $adminTranslations
     * @param StoreTranslations $storeTranslations
     * @param Store $store
     * @param Seller $seller
     * @param Scripts $scripts
     * @param Url $url
     * @param Endpoints $endpoints
     * @param Cron $cron
     * @param CurrentUser $currentUser
     */
     public function __construct(
        Template $template,
        OrderMetadata $orderMetadata,
        AdminTranslations $adminTranslations,
        StoreTranslations $storeTranslations,
        Store $store,
        Seller $seller,
        Scripts $scripts,
        Url $url,
        Endpoints $endpoints,
        Cron $cron,
        CurrentUser $currentUser
     ){
         $this->template          = $template;
         $this->orderMetadata     = $orderMetadata;
         $this->adminTranslations = $adminTranslations;
         $this->storeTranslations = $storeTranslations;
         $this->store             = $store;
         $this->seller            = $seller;
         $this->scripts           = $scripts;
         $this->url               = $url;
         $this->endpoints         = $endpoints;
         $this->cron              = $cron;
         $this->currentUser       = $currentUser;

         $this->sdk         = $this->getSdkInstance();
         $this->registerSyncPendingStatusOrdersAction();

         $this->registerStatusSyncMetaBox();
     }

    /**
     * Get SDK instance
     */
    public function getSdkInstance()
    {

        $lang = get_locale();
        $lang = explode('_', $lang);
        $lang = $lang[0];
        $public_key = $this->seller->getCredentialsPublicKeyPayment();
        $private_key = $this->seller->getCredentialsPrivateKeyPayment();
        //$isTestMode = $this->seller->isTestUser()?"true":"false";
        $isTestMode = $this->seller->isTestMode()?"true":"false";
        return new EpaycoSdk\Epayco(
            [
                "apiKey" => $public_key,
                "privateKey" => $private_key,
                "lenguage" => strtoupper($lang),
                "test" => $isTestMode
            ]
        );
    }

    /**
     * Set ticket metadata in the order
     *
     * @param WC_Order $order
     * @param $data
     *
     * @return void
     */
    public function setTicketMetadata(WC_Order $order, $data): void
    {
        $externalResourceUrl = $data['urlPayment'];
        $this->orderMetadata->setTicketTransactionDetailsData($order, $externalResourceUrl);
        $order->save();
    }

    /**
     * Set ticket metadata in the order
     *
     * @param WC_Order $order
     * @param $data
     *
     * @return void
     */
    public function setDaviplataMetadata(WC_Order $order, $data): void
    {
        $externalResourceUrl = json_encode($data);
        $this->orderMetadata->setDaviplataTransactionDetailsData($order, $externalResourceUrl);
        $order->save();
    }

    /**
     * Registers the Status Sync Metabox
     */
    private function registerStatusSyncMetabox(): void
    {
        $this->registerMetaBox(function ($postOrOrderObject) {
            $order = ($postOrOrderObject instanceof WP_Post) ? wc_get_order($postOrOrderObject->ID) : $postOrOrderObject;

            if (!$order || !$this->getLastPaymentInfo($order)) {
                return;
            }

            $paymentMethod     = $this->orderMetadata->getUsedGatewayData($order);
            $isMpPaymentMethod = array_filter($this->store->getAvailablePaymentGateways(), function ($gateway) use ($paymentMethod) {
                return $gateway::ID === $paymentMethod || $gateway::WEBHOOK_API_NAME === $paymentMethod;
            });

            /*if (!$isMpPaymentMethod) {
                return;
            }*/

            $this->loadScripts($order);
            $epayco_order = $this->getMetaboxData($order);
            if(!$epayco_order){
                return;
            }

            $this->addMetaBox(
                'ep_payment_status_sync',
                $this->adminTranslations->statusSync['metabox_title'],
                'admin/order/payment-status-metabox-content.php',
                $this->getMetaboxData($order)
            );
        });
    }

    /**
     * Add a meta box to screen
     *
     * @param string $id
     * @param string $title
     * @param string $name
     * @param array $args
     *
     * @return void
     */
    public function addMetaBox(string $id, string $title, string $name, array $args): void
    {
        add_meta_box($id, $title, function () use ($name, $args) {
            $this->template->getWoocommerceTemplate($name, $args);
        });
    }

    /**
     * Load the Status Sync Metabox script and style
     *
     * @param WC_Order $order
     */
    private function loadScripts(WC_Order $order): void
    {
        $this->scripts->registerStoreScript(
            'mp_payment_status_sync',
            $this->url->getJsAsset('admin/order/payment-status-sync'),
            [
                'order_id' => $order->get_id(),
                'nonce' => self::generateNonce(self::NONCE_ID),
            ]
        );

        $this->scripts->registerStoreStyle(
            'mp_payment_status_sync',
            $this->url->getCssAsset('admin/order/payment-status-sync')
        );
    }

    /**
     * Register meta box addition on order page
     *
     * @param mixed $callback
     *
     * @return void
     */
    public function registerMetaBox($callback): void
    {
        add_action('add_meta_boxes_shop_order', $callback);
        add_action('add_meta_boxes_woocommerce_page_wc-orders', $callback);
    }

    /**
     * Get the data to be renreded on the Status Sync Metabox
     *
     * @param WC_Order $order
     *
     * @return array|bool
     */
    private function getMetaboxData(WC_Order $order)
    {
        $paymentInfo  = $this->getLastPaymentInfo($order);
        if(!$paymentInfo->success){
            return false;
        }
        $status = 'pending';
        $alert_title = '';
        $order_id=false;

        $status = $paymentInfo->data->x_response;
        $alert_title = $paymentInfo->data->x_response;
        $alert_description = $paymentInfo->data->x_response_reason_text;
        $ref_payco = $paymentInfo->data->x_ref_payco;
        $test = $paymentInfo->data->x_test_request == 'TRUE' ? 'Pruebas' : 'Producción';
        $transactionDateTime= $paymentInfo->data->x_transaction_date;
        $bank= $paymentInfo->data->x_bank_name;
        $authorization= $paymentInfo->data->x_approval_code;
        $order_id = $paymentInfo->data->x_id_invoice;

        if(!$order_id){
            return false;
        }
        $order = new WC_Order($order_id);
        $WooOrderstatus = $order->get_status();

        switch ($status) {
            case 'Aceptada':
                $orderstatus = 'approved';
                break;
            case 'Pendiente':
                $orderstatus = 'pending';
                break;
            default:
                $orderstatus = 'rejected';
                break;
        }
        $paymentStatusType = PaymentStatus::getStatusType(strtolower($orderstatus));
        $upload_order=false;
        if($WooOrderstatus == 'on-hold'||$WooOrderstatus == 'cancelled'){
            $upload_order=true;
        }

        $cardContent = PaymentStatus::getCardDescription(
            $this->adminTranslations->statusSync,
            'by_collector',
            false
        );

        switch ($paymentStatusType) {
            case 'success':{
                if($upload_order){
                    if($WooOrderstatus !== 'processing'){
                        $order->update_status("processing");
                    }
                }
                return [
                    'card_title'        => $this->adminTranslations->statusSync['card_title'],
                    'img_src'           => $this->url->getImageAsset('icons/icon-success'),
                    'alert_title'       => $alert_title,
                    'alert_description' => $alert_description,
                    'link'              => 'https://epayco.com',
                    'border_left_color' => '#00A650',
                    'link_description'  => $this->adminTranslations->statusSync['link_description_success'],
                    'sync_button_text'  => $this->adminTranslations->statusSync['sync_button_success'],
                    'ref_payco'         => $ref_payco,
                    'test'              => $test,
                    'transactionDateTime'              => $transactionDateTime,
                    'bank'              => $bank,
                    'authorization'     => $authorization
                ];
            }break;
            case 'pending':
                return [
                    'card_title'        => $this->adminTranslations->statusSync['card_title'],
                    'img_src'           => $this->url->getImageAsset('icons/icon-alert'),
                    'alert_title'       => $alert_title,
                    'alert_description' => $alert_description,
                    'link'              => 'https://epayco.com',
                    'border_left_color' => '#f73',
                    'link_description'  => $this->adminTranslations->statusSync['link_description_pending'],
                    'sync_button_text'  => $this->adminTranslations->statusSync['sync_button_pending'],
                    'ref_payco'         => $ref_payco,
                    'test'              => $test,
                    'transactionDateTime'              => $transactionDateTime,
                    'bank'              => $bank,
                    'authorization'     => $authorization
                ];
                break;
            case 'rejected':
            case 'refunded':
            case 'charged_back':{
                if($upload_order){
                    if($WooOrderstatus !== 'cancelled'){
                        $order->update_status("cancelled");
                    }
                }

                return [
                    'card_title'        => $this->adminTranslations->statusSync['card_title'],
                    'img_src'           => $this->url->getImageAsset('icons/icon-warning'),
                    'alert_title'       => $alert_title,
                    'alert_description' => $alert_description,
                    'link'              => 'reasons_refusals',
                    'border_left_color' => '#F23D4F',
                    'link_description'  => $this->adminTranslations->statusSync['link_description_failure'],
                    'sync_button_text'  => $this->adminTranslations->statusSync['sync_button_failure'],
                    'ref_payco'         => $ref_payco,
                    'test'              => $test,
                    'transactionDateTime'              => $transactionDateTime,
                    'bank'              => $bank,
                    'authorization'     => $authorization
                ];
            }break;
            default:
                return [];
        }
    }

    /**
     * Get the last order payment info
     *
     * @param WC_Order $order
     *
     * @return bool|AbstractCollection|AbstractEntity|object
     */
    private function getLastPaymentInfo(WC_Order $order)
    {
        try {
            $paymentsIds   = explode(',', $this->orderMetadata->getPaymentsIdMeta($order));
            $lastPaymentId = trim(end($paymentsIds));

            if (!$lastPaymentId) {
                return false;
            }
            $data = array(
                "filter" => array(
                    "referencePayco" =>(string) $paymentsIds[0]),
                    "pagination" => ["page"=>1],
                    "success" =>true
            );
            return $this->sdk->transaction->get($paymentsIds[0]);
            //return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add order note
     *
     * @param WC_Order $order
     * @param string $description
     * @param int $isCustomerNote
     * @param bool $addedByUser
     *
     * @return void
     */
    public function addOrderNote(WC_Order $order, string $description, int $isCustomerNote = 0, bool $addedByUser = false)
    {
        $order->add_order_note($description, $isCustomerNote, $addedByUser);
    }

    /**
     * Generate wp_nonce
     *
     * @param string $id
     *
     * @return string
     */
    private static function generateNonce(string $id): string
    {
        $nonce = wp_create_nonce($id);

        if (!$nonce) {
            return '';
        }

        return $nonce;
    }

    /**
     * Register action that sync orders with pending status with corresponding status in epayco
     *
     * @return void
     */
    public function registerSyncPendingStatusOrdersAction(): void
    {
        add_action('epayco_sync_pending_status_order_action', function () {
            try {
                $orders = wc_get_orders(array(
                    'limit'    => -1,
                    'status'   => 'on-hold',
                    'meta_query' => array(
                        'key' => $this->orderMetadata::PAYMENTS_IDS
                    )
                ));
                $ref_payco_list = [];
                foreach ($orders as $order) {
                    $ref_payco = $this->syncOrderStatus($order);
                    if($ref_payco){
                        $ref_payco_list[] = $ref_payco;
                    }
                }

                if (is_array($ref_payco_list) && !empty($ref_payco_list))
                {
                    $token = $this->epyacoBerarToken();
                    if($token){
                        foreach ($ref_payco_list as $ref_payco) {
                            $this->getEpaycoStatusOrder($ref_payco, $token);
                        }
                    }
                }


            } catch (\Exception $ex) {
                $error_message = "Unable to update batch of orders on action got error: {$ex->getMessage()}";

                if ( class_exists( 'WC_Logger' ) ) {
                    $logger = new \WC_Logger();
                    $logger->add( 'ePayco',$error_message);
                }

            }
        });
    }

    public function getEpaycoStatusOrder($ref_payco,$token)
    {
        if($token){
            $headers = array(
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer ".$token['token']
            );
            $public_key = $this->seller->getCredentialsPublicKeyPayment();
            $path = "transaction/response.json?ref_payco=".$ref_payco."&&public_key=".$public_key;
            $epayco_status = $this->epayco_realizar_llamada_api($path,[],$headers,false, 'GET');
            if($epayco_status['success']){
                if (isset($epayco_status['data']) && is_array($epayco_status['data'])) {
                    $this->epaycoUploadOrderStatus($epayco_status);
                }
            }

        }
    }

    public function epaycoUploadOrderStatus($epayco_status)
    {
        $order_id = isset($epayco_status['data']['x_extra1']) ? $epayco_status['data']['x_extra1'] : null;
        $x_cod_transaction_state = isset($epayco_status['data']['x_cod_transaction_state']) ? $epayco_status['data']['x_cod_transaction_state'] : null;
        $x_ref_payco = isset($epayco_status['data']['x_ref_payco']) ? $epayco_status['data']['x_ref_payco'] : null;
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $orderStatus = $order->get_status();
                switch ($x_cod_transaction_state) {
                    case 1: {
                        $order->payment_complete($x_ref_payco);
                        $order->update_status('processing', 'La orden se ha completado automáticamente por la integración con ePayco.');
                        $order->add_order_note('ePayco.');
                    } break;
                    case 3:
                    case 7:
                        {
                            $orderStatus = "on-hold";
                            if($orderStatus !== $orderStatus){
                                $order->update_status($orderStatus,'La orden se ha completado automáticamente por la integración con ePayco.');
                                $order->add_order_note('ePayco.');
                            }
                        } break;
                    case 2:
                    case 4:
                    case 10:
                    case 11:{
                        if($orderStatus == 'pending' || $orderStatus == 'on-hold'){
                            $order->update_status('cancelled','La orden se ha completado automáticamente por la integración con ePayco.');
                            $order->add_order_note('ePayco.');
                        }
                    }break;
                }

            }
        }
    }

    public function syncOrderStatus(\WC_Order $order): string
    {
        $paymentsIds   = explode(',', $order->get_meta($this->orderMetadata::PAYMENTS_IDS));
        $lastPaymentId = trim(end($paymentsIds));
        if ($lastPaymentId) {
            return $lastPaymentId;
        }else{
            return false;
        }
    }

    public function epyacoBerarToken()
    {

        $publicKey = $this->seller->getCredentialsPublicKeyPayment();
        $privateKey = $this->seller->getCredentialsPrivateKeyPayment();

        if(!isset($_COOKIE[$publicKey])) {
            $token = base64_encode($publicKey.":".$privateKey);
            $bearer_token = $token;
            $cookie_value = $bearer_token;
            setcookie($publicKey, $cookie_value, time() + (60 * 14), "/");
        }else{
            $bearer_token = $_COOKIE[$publicKey];
        }

        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => "Basic ".$bearer_token
        );
        return $this->epayco_realizar_llamada_api("login",[],$headers);
    }

    public function epayco_realizar_llamada_api($path, $data, $headers, $afify = true, $method = 'POST') {
        if($afify){
            $url = 'https://apify.epayco.io/'.$path;
        }else{
            $url = 'https://secure2.epayco.io/restpagos/'.$path;
        }

        $response = wp_remote_post($url, array(
            'method'    => $method,
            'headers' => $headers,
            'data' => json_encode($data),
            'timeout'   => 120,
        ));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("Error al hacer la llamada a la API de ePayco: " . $error_message);
            return false;
        } else {
            $response_body = wp_remote_retrieve_body($response);
            $status_code = wp_remote_retrieve_response_code($response);

            if ($status_code == 200) {
                $data = json_decode($response_body, true);
                return $data;
            } else {
                error_log("Error en la respuesta de la API de ePayco, código de estado: " . $status_code);
                return false;
            }
        }
    }

    /**
     * Register/Unregister cron job that sync pending orders
     *
     * @return void
     */
    public function toggleSyncPendingStatusOrdersCron(string $enabled): void
    {
        $action = 'epayco_sync_pending_status_order_action';

        if ($enabled == 'yes') {
            $this->cron->registerScheduledEvent('hourly', $action);
        } else {
            $this->cron->unregisterScheduledEvent($action);
        }

    }
}