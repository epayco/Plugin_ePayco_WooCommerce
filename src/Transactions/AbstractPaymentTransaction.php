<?php

namespace Epayco\Woocommerce\Transactions;

use Epayco\Woocommerce\Gateways\AbstractGateway;

abstract class AbstractPaymentTransaction extends AbstractTransaction
{
    /**
     * Payment Transaction constructor
     */
    public function __construct(AbstractGateway $gateway, ?\WC_Order $order, array $checkout)
    {
        parent::__construct($gateway, $order, $checkout);
    }

    /**
     * Create Payment
     *
     * @return string|array
     * @throws \Exception
     */
    public function createCashPayment($order_id, array $checkout)
    {
        $order = new \WC_Order($order_id);
        $descripcionParts = array();
        $iva=0;
        $ico=0;
        $base_tax=$order->get_subtotal()-$order->get_total_discount();
        foreach($order->get_items('tax') as $item_id => $item ) {
            $tax_label = trim(strtolower($item->get_label()));

            if ($tax_label == 'iva') {
                $iva += round($item->get_tax_total(), 2);
            }

            if ($tax_label == 'ico') {
                $ico += round($item->get_tax_total(), 2);
            }
        }
        $iva = $iva !== 0 ? $iva : $order->get_total() - $base_tax;

        foreach ($order->get_items() as $product) {
            $clearData = str_replace('_', ' ', $this->string_sanitize($product['name']));
            $descripcionParts[] = $clearData;
        }

        $descripcion = implode(' - ', $descripcionParts);
        $currency = strtolower(get_woocommerce_currency());
        $basedCountry = WC()->countries->get_base_country()!='' ? WC()->countries->get_base_country():$order->get_shipping_country();
        //$basedCountry = $checkout["countryType"]??$checkout["countrytype"];
        $city = WC()->countries->get_base_city() !='' ? WC()->countries->get_base_city():$order->get_shipping_city();
        $myIp=$this->getCustomerIp();
        $confirm_url = $checkout["confirm_url"];
        $response_url = $checkout["confirm_url"];
        $end_date = date('y-m-d', strtotime(sprintf('+%s days',$checkout["date_expiration"]) ));
        $testMode = $this->epayco->storeConfig->isTestMode()??false;
        $customerName = $checkout["name"]??$checkout[""]["name"];
        $explodeName = explode(" ", $customerName);
        $name = $explodeName[0];
        $lastName = $explodeName[1];
        //$person_type= $checkout["person_type"];
        $person_type= 'PN';
        //$holder_address= $checkout["address"];
        $holder_address=$order->get_billing_address_1();
        $doc_type= $checkout["identificationtype"]??$checkout["identificationType"]??$checkout["documentType"];
        $doc_number= $checkout["doc_number"]??$checkout["document"]??$checkout[""]["doc_number"]??$_POST['docNumberError']??$_POST['identificationTypeError'];
        $email= $checkout["email"];
        $cellphone= $checkout["cellphonetype"];
        //$cellphone=@$order->billing_phone??'0';
        $data = array(
            "paymentMethod" => $checkout["paymentMethod"],
            "invoice" => (string)$order->get_id()."dd",
            "description" => $descripcion,
            "value" =>(string)$order->get_total(),
            "tax" => (string)$iva,
            "taxBase" => (string)$base_tax,
            "currency" => $currency,
            "type_person" => $person_type=='PN'?"0":"1",
            "address" => $holder_address,
            "docType" => $doc_type,
            "docNumber" => $doc_number,
            "name" => $name,
            "lastName" => $lastName,
            "email" => $email,
            "country" => $basedCountry,
            "city" => $city,
            "cellPhone" => $cellphone,
            "endDate" => $end_date,
            "ip" => $myIp,
            "urlResponse" => $response_url,
            "urlConfirmation" => $confirm_url,
            "methodConfirmation" => "POST",
            "extra1" => (string)$order->get_id(),
            "extras" => array(
                "extra1" => (string)$order->get_id(),
            ),
            "vtex" => false,
            "testMode" => $testMode,
            "extras_epayco"=>["extra5"=>"P19"]
        );
        $cash = $this->sdk->cash->create($data);

        $cash = json_decode(json_encode($cash), true);
        return $cash;
    }

    /**
     * Create Payment
     *
     * @return string|array
     * @throws \Exception
     */
    public function createDaviplataPayment($order_id, array $checkout)
    {
        $order = new \WC_Order($order_id);
        $descripcionParts = array();
        $iva=0;
        $ico=0;
        $base_tax=$order->get_subtotal()-$order->get_total_discount();
        foreach($order->get_items('tax') as $item_id => $item ) {
            $tax_label = trim(strtolower($item->get_label()));

            if ($tax_label == 'iva') {
                $iva += round($item->get_tax_total(), 2);
            }

            if ($tax_label == 'ico') {
                $ico += round($item->get_tax_total(), 2);
            }
        }
        $iva = $iva !== 0 ? $iva : $order->get_total() - $base_tax;
        foreach ($order->get_items() as $product) {
            $clearData = str_replace('_', ' ', $this->string_sanitize($product['name']));
            $descripcionParts[] = $clearData;
        }

        $descripcion = implode(' - ', $descripcionParts);
        $currency = strtolower(get_woocommerce_currency());
        //$basedCountry = WC()->countries->get_base_country();
        $basedCountry = 'CO';
        $myIp=$this->getCustomerIp();
        $confirm_url = $checkout["confirm_url"];
        $response_url = $checkout["response_url"];
        $customerName = $checkout["name"]??$checkout[""]["name"];
        $explodeName = explode(" ", $customerName);
        $name = $explodeName[0];
        $lastName = $explodeName[1];
        //$person_type= $checkout["person_type"]??$checkout[""]["person_type"];
        //$holder_address= $checkout["address"]??$checkout[""]["address"];
        $person_type= 'PN';
        $holder_address=$order->get_billing_address_1();
        $doc_type= $checkout["identificationtype"]??$checkout["identificationType"];
        $doc_number= $checkout["doc_number"]??$checkout[""]["doc_number"]??$_POST['docNumberError']??$_POST['identificationTypeError'];
        $email= $checkout["email"]??$checkout[""]["email"];
        $cellphone= $checkout["cellphonetype"]??$checkout[""]["cellphonetype"];
        $cellphonetype = $_POST["cellphone"]??$checkout["cellphone"]??$checkout[""]["cellphone"];
        $cellphonetypeIn = explode("+", $cellphonetype)[1];
        $city = WC()->countries->get_base_city() !='' ? WC()->countries->get_base_city():$order->get_shipping_city();
        $testMode = $this->epayco->storeConfig->isTestMode()??false;
        $data = array(
            "invoice" => (string)$order->get_id()."dd",
            "description" => $descripcion,
            "value" =>(string)$order->get_total(),
            "tax" => (string)$iva,
            "taxBase" => (string)$base_tax,
            "currency" => strtoupper($currency),
            "type_person" => $person_type=='PN'?"0":"1",
            "address" => $holder_address,
            "docType" => $doc_type,
            "document" => $doc_number,
            "name" => $name,
            "lastName" => $lastName,
            "email" => $email,
            "country" => $basedCountry,
            "indCountry" => $cellphonetypeIn,
            "city" => $city,
            "phone" => $cellphone,
            "ip" => $myIp,
            "urlResponse" => $response_url,
            "urlConfirmation" => $confirm_url,
            "methodConfirmation" => "POST",
            "extra1" => (string)$order->get_id(),
            "extras" => array(
                "extra1" => (string)$order->get_id(),
            ),
            "vtex" => true,
            "testMode" => $testMode,
            "extras_epayco"=>["extra5"=>"P19"]
        );
        $daviplata = $this->sdk->daviplata->create($data);
        $daviplata= json_decode(json_encode($daviplata), true);
        return $daviplata;
    }

    /**
     * Create Payment
     *
     * @return string|array
     * @throws \Exception
     */
    public function createTcPayment($order_id, array $checkout)
    {
        $order = new \WC_Order($order_id);
        $descripcionParts = array();
        $iva=0;
        $ico=0;
        $base_tax=$order->get_subtotal()-$order->get_total_discount();
        foreach($order->get_items('tax') as $item_id => $item ) {
            $tax_label = trim(strtolower($item->get_label()));

            if ($tax_label == 'iva') {
                $iva += round($item->get_tax_total(), 2);
            }

            if ($tax_label == 'ico') {
                $ico += round($item->get_tax_total(), 2);
            }
        }
        $iva = $iva !== 0 ? $iva : $order->get_total() - $base_tax;

        foreach ($order->get_items() as $product) {
            $clearData = str_replace('_', ' ', $this->string_sanitize($product['name']));
            $descripcionParts[] = $clearData;
        }

        $descripcion = implode(' - ', $descripcionParts);
        $currency = strtolower(get_woocommerce_currency());
        $basedCountry = WC()->countries->get_base_country();
        //$customerData = $this->getCustomer($checkout);
        $basedCountry = $checkout["countryType"]??$checkout["countrytype"]??$checkout[""]["countryType"];
        $city = $checkout["country"]??$checkout[""]["country"];
        $myIp=$this->getCustomerIp();
        $confirm_url = $checkout["confirm_url"];
        $response_url = $checkout["response_url"];
        $testMode = $this->epayco->storeConfig->isTestMode()??false;
        $customerName = $checkout["name"]??$checkout[""]["name"];
        $explodeName = explode(" ", $customerName);
        $name = $explodeName[0];
        $lastName = $explodeName[1];
        $dues= $checkout["installmet"]??$checkout[""]["installmet"];
        //$person_type= $checkout["person_type"]??$checkout[""]["person_type"];
        $holder_address= $checkout["address"]??$checkout[""]["address"];
        $doc_type= $checkout["identificationtype"]??$checkout["identificationType"]??$checkout[""]["identificationType"];
        $doc_number= $checkout["doc_number"]??$checkout[""]["doc_number"]??$_POST['docNumberError']??$_POST['identificationTypeError'];
        $email= $checkout["email"]??$checkout[""]["email"];
        $cellphone= $checkout["cellphone"]??$checkout[""]["cellphone"];
        /*$customerData = $this->getCustomer($checkout);
        if(!$customerData['success']){
            return $customerData;
        }*/
        $data = array(
            "token_card" => $checkout["token"],
            //"customer_id" => $customerData['customer_id'],
            "customer_id" => 'customer_id',
            "bill" => (string)$order->get_id(),
            "dues" => $dues,
            "description" => $descripcion,
            "value" =>(string)$order->get_total(),
            "tax" => $iva,
            "tax_base" => $base_tax,
            "currency" => $currency,
            "doc_type" => $doc_type,
            "doc_number" => $doc_number,
            "name" => $name,
            "last_name" => $lastName,
            "email" => $email,
            "country" => $basedCountry,
            "address"=> $holder_address,
            "city" => $city,
            "cell_phone" => $cellphone,
            "ip" => $myIp,
            "url_response" => $response_url,
            "url_confirmation" => $confirm_url,
            "metodoconfirmacion" => "POST",
            "use_default_card_customer" => true,
            "extra1" => (string)$order->get_id(),
            "extras" => array(
                "extra1" => (string)$order->get_id(),
            ),
            "extras_epayco"=>["extra5"=>"P19"]
        );
        $charge = $this->sdk->charge->create($data);
        return $charge;
    }

    /**
     * Create Payment
     *
     * @return string|array
     * @throws \Exception
     */
    public function createPsePayment($order_id, array $checkout)
    {
        $order = new \WC_Order($order_id);
        $descripcionParts = array();
        $iva=0;
        $ico=0;
        $base_tax=$order->get_subtotal()-$order->get_total_discount();
        foreach($order->get_items('tax') as $item_id => $item ) {
            $tax_label = trim(strtolower($item->get_label()));

            if ($tax_label == 'iva') {
                $iva += round($item->get_tax_total(), 2);
            }

            if ($tax_label == 'ico') {
                $ico += round($item->get_tax_total(), 2);
            }
        }
        
        $iva = $iva !== 0 ? $iva : $order->get_total() - $base_tax;

        foreach ($order->get_items() as $product) {
            $clearData = str_replace('_', ' ', $this->string_sanitize($product['name']));
            $descripcionParts[] = $clearData;
        }

        $descripcion = implode(' - ', $descripcionParts);
        $currency = strtolower(get_woocommerce_currency());
        //$basedCountry = WC()->countries->get_base_country();
        $basedCountry = $checkout["countryType"]??$checkout["countrytype"]??$checkout[""]["countryType"];
        $city = $checkout["country"]??$checkout[""]["country"];
        $myIp=$this->getCustomerIp();
        $confirm_url = $checkout["confirm_url"];
        $response_url = $checkout["response_url"];
        $testMode = $this->epayco->storeConfig->isTestMode()??false;
        $customerName = $checkout["name"]??$checkout[""]["name"];
        $explodeName = explode(" ", $customerName);
        $name = $explodeName[0];
        $lastName = $explodeName[1];
        $bank = $checkout["bank"]??$checkout[""]["bank"];
        $person_type= $checkout["person_type"]??$checkout[""]["person_type"];
        $holder_address= $checkout["address"]??$checkout[""]["address"];
        $doc_type= $checkout["identificationtype"]??$checkout["identificationType"]??$checkout[""]["identificationType"];
        $doc_number= $checkout["doc_number"]??$checkout[""]["doc_number"]??$_POST['docNumberError']??$_POST['identificationTypeError'];
        $email= $checkout["email"]??$checkout[""]["email"];
        $cellphone= $checkout["cellphonetype"]??$checkout[""]["cellphonetype"];
        $data = array(
            "bank" => $bank,
            "invoice" => (string)$order->get_id(),
            "description" => $descripcion,
            "value" =>$order->get_total(),
            "tax" => $iva,
            "taxBase" => $base_tax,
            "currency" => $currency,
            "typePerson" => $person_type=='PN'?"0":"1",
            "address" => $holder_address,
            "docType" => $doc_type,
            "docNumber" => $doc_number,
            "name" =>$name,
            "lastName" => $lastName,
            "email" => $email,
            "country" => $basedCountry,
            "city" => $city,
            "cellPhone" => $cellphone,
            "ip" => $myIp,
            "urlResponse" => $response_url,
            "urlConfirmation" => $confirm_url,
            "methodConfirmation" => "POST",
            "extra1" => (string)$order->get_id(),
            "extras" => array(
                "extra1" => (string)$order->get_id(),
            ),
            "testMode" => $testMode,
            "extras_epayco"=>["extra5"=>"P58"]
        );
        $pse = $this->sdk->bank->create($data);
        return $pse;
    }

    /**
     * Create Payment
     *
     * @return string|array
     * @throws \Exception
     */
    public function createSubscriptionPayment($order_id, array $checkout)
    {
        global $wpdb;
        $order = new \WC_Order($order_id);
        $subscriptions = wcs_get_subscriptions_for_order($order_id);
        $customerData = $this->getCustomer($checkout);
        if(!$customerData['success']){
            return $customerData;
        }
        $checkout['customer_id'] = $customerData['customer_id'];
        $customer = $this->paramsBilling($subscriptions, $order, $checkout);
        $plans = $this->getPlansBySubscription($subscriptions);
        $getPlans = $this->getPlans($plans);
        if (!$getPlans) {
            $validatePlan = $this->validatePlan(true, $order_id, $plans, $subscriptions, $customer, $order, false, false, null,$checkout);
        } else {
            $validatePlan = $this->validatePlan(false, $order_id, $plans, $subscriptions, $customer, $order, true, false, $getPlans,$checkout);
        }
        $errorMessage = array();
        if(!$validatePlan['success']){
            if(is_array($validatePlan['message'])){
                foreach ($validatePlan['message'] as $message) {
                    $errorMessage[] = $message;
                }
                return [
                    'success' => false,
                    'message' => implode(' - ', $errorMessage)
                ];
            }else{
                return [
                    'success' => false,
                    'message' => $validatePlan['message']
                ];
            }
        }else{
            return $validatePlan;
        }
    }

    public function getCustomer($customerData)
    {
        global $wpdb;
        $table_name_customer = $wpdb->prefix . 'epayco_customer';
        $customerGetData = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name_customer WHERE email = %s",
                trim($customerData['email'])
            )
        );
        if (count($customerGetData) == 0) {
            return $this->getCreateNewCustomer($customerData);
        }else{
            $count_customers = 0;
            for ($i = 0; $i < count($customerGetData); $i++) {
                if ($customerGetData[$i]->email == trim($customerData['email'])) {
                    $count_customers += 1;
                }
            }
            if ($count_customers == 0) {
                $customer = $this->customerCreate($customerData);
                if (is_array($customer) && !$customer['success']) {
                    $response_status = [
                        'success' => false,
                        'message' => $customer['message']
                    ];
                    return $response_status;
                }
                $inserCustomer = $wpdb->insert(
                    $table_name_customer,
                    [
                        'customer_id' => $customer['data']['customerId'],
                        'email' => trim($customerData['email'])
                    ]
                );
                if (!$inserCustomer) {
                    $response_status = [
                        'success' => false,
                        'message' => 'internar error, tray again'
                    ];
                    return $response_status;
                }
                $response_status = [
                    'success' => true,
                    'customer_id' => $customer['data']['customerId']
                ];
                return $response_status;
            } else {
                $count_cards = 0;
                for ($i = 0; $i < count($customerGetData); $i++) {
                    $customers = $this->sdk->customer->get($customerGetData[$i]->customer_id);
                    $customers = json_decode(json_encode($customers), true);
                    if($customers['success']){
                        $cards = $customers['data']['cards'];
                        for ($j = 0; $j < count($cards); $j++) {
                            if ($cards[$j]['token'] == trim($customerData['token'])) {
                                $count_cards += 1;
                            }
                        }
                        if($count_cards == 0){
                            $this->customerAddToken($customerGetData[$i]->customer_id, trim($customerData['token']));
                        }
                    }else{
                        return $this->getCreateNewCustomer($customerData);
                    }
                    $customerData['customer_id'] = $customerGetData[$i]->customer_id;
                }
                $response_status = [
                    'success' => true,
                    'customer_id' => $customerData['customer_id']
                ];
                return $response_status;
            }
        }
    }

    public function getCreateNewCustomer($customerData)
    {
        global $wpdb;
        $table_name_customer = $wpdb->prefix . 'epayco_customer';
        $customer = $this->customerCreate($customerData);
        if (is_array($customer) && $customer['success']) {
            $inserCustomer = $wpdb->insert(
                $table_name_customer,
                [
                    'customer_id' => $customer['data']['customerId'],
                    'email' => trim($customerData['email'])
                ]
            );
            if (!$inserCustomer) {
                $response_status = [
                    'success' => false,
                    'message' => 'internar error, tray again'
                ];
                return $response_status;
            }{
                $response_status = [
                    'success' => true,
                    'customer_id' => $customer['data']['customerId']
                ];
                return $response_status;
            }
        }else{
            $messageError = $customer['message'];
            $errorMessage = "";
            if (isset($customer['data']['errors'])) {
                $errors = $customer['data']['errors'];
                if(is_array($errors)){
                    foreach ($errors as $error) {
                        $errorMessage = $error['errorMessage'] . "\n";
                    }
                }
                if(is_string($errors)){
                    $errorMessage = $errors . "\n";
                }
            } elseif (isset($customer['data']['error']['errores'])) {
                $errores = $customer['data']['error']['errores'];
                foreach ($errores as $error) {
                    $errorMessage = $error['errorMessage'] . "\n";
                }
            }
            $processReturnFailMessage = $messageError. " " . $errorMessage;
            $response_status = [
                'success' => false,
                'message' => $processReturnFailMessage
            ];
            return $response_status;
        }
    }

    public function customerCreate(array $data)
    {
        $customer = false;
        try {
            $customer = $this->sdk->customer->create(
                [
                    "token_card" => $data['token'],
                    "name" => $data['name'],
                    "email" => $data['email'],
                    "phone" => $data['cellphone'],
                    "cell_phone" => $data['cellphone'],
                    "country" => $data['countrytype'],
                    "address" => $data['address'],
                    "default" => true
                ]
            );
            $customer = json_decode(json_encode($customer), true);
            return $customer;
        } catch (\Exception $exception) {
            return [
                'status' => false,
                'message' => 'create client: : ' . $exception->getMessage()
            ];
        }
    }

    public function customerAddToken($customer_id, $token_card)
    {
        $customer = false;
        try {
            $customer = $this->sdk->customer->addNewToken(
                [
                    "token_card" => $token_card,
                    "customer_id" => $customer_id
                ]
            );
            return $customer;
        } catch (\Exception $exception) {
            return [
                'status' => false,
                'message' => 'add token: ' . $exception->getMessage()
            ];
        }
    }

    public function getPlansBySubscription(array $subscriptions)
    {
        $plans = [];
        foreach ($subscriptions as $key => $subscription) {
            $total_discount = $subscription->get_total_discount();
            $order_currency = $subscription->get_currency();
            $products = $subscription->get_items();
            $product_plan = $this->getPlan($products);
            $quantity = $product_plan['quantity'];
            $product_name = $product_plan['name'];
            $product_id = $product_plan['id'];
            $trial_days = $this->getTrialDays($subscription);
            $plan_code = "$product_name-$product_id";
            $plan_code = $trial_days > 0 ? "$product_name-$product_id-$trial_days" : $plan_code;
            //$plan_code = $this->currency !== $order_currency ? "$plan_code-$order_currency" : $plan_code;
            $plan_code = $quantity > 1 ? "$plan_code-$quantity" : $plan_code;
            $plan_code = $total_discount > 0 ? "$plan_code-$total_discount" : $plan_code;
            $plan_code = rtrim($plan_code, "-");
            $plan_id = str_replace(array("-", "--"), array("_", ""), $plan_code);
            $plan_name = trim(str_replace("-", " ", $product_name));
            $plans[] = array_merge(
                [
                    "id_plan" => strtolower(str_replace("__", "_", $plan_id)),
                    "name" => "Plan $plan_name",
                    "description" => "Plan $plan_name",
                    "currency" => $order_currency,
                ],
                [
                    "trial_days" => $trial_days
                ],
                $this->intervalAmount($subscription)
            );
        }
        return $plans;
    }

    public function getPlan($products)
    {
        $product_plan = [];

        $product_plan['name'] = '';
        $product_plan['id'] = 0;
        $product_plan['quantity'] = 0;

        foreach ($products as $product) {
            $product_plan['name'] .= "{$product['name']}-";
            $product_plan['id'] .= "{$product['product_id']}-";
            $product_plan['quantity'] .= $product['quantity'];
        }

        $product_plan['name'] = $this->cleanCharacters($product_plan['name']);

        return $product_plan;
    }

    public function validatePlan($create, $order_id, array $plans, $subscriptions, $customer, $order, $confirm, $update, $getPlans, $checkout)
    {
        if ($create) {
            $newPLan = $this->plansCreate($plans);
            if ($newPLan->status) {
                $getPlans_ = $this->getPlans($plans);
                if ($getPlans_) {
                    $eXistPLan = $this->validatePlanData($plans, $getPlans_, $order_id, $subscriptions, $customer, $order, $checkout);
                } else {
                    $this->validatePlan(true, $order_id, $plans, $subscriptions, $customer, $order, false, false, null,$checkout);
                }
            } else {
                $response_status = [
                    'status' => false,
                    'message' => $newPLan->message??$newPLan['message']
                ];
                return $response_status;
            }
        } else {
            if ($confirm) {
                $eXistPLan = $this->validatePlanData($plans, $getPlans, $order_id, $subscriptions, $customer, $order, $checkout);
            }
        }
        return $eXistPLan;
    }

    public function plansCreate(array $plans)
    {
        foreach ($plans as $plan) {
            try {
                $plan_ = $this->sdk->plan->create(
                    [
                        "id_plan" => (string)str_replace("-", "_",strtolower($plan['id_plan'])),
                        "name" => (string)$plan['name'],
                        "description" => (string)$plan['description'],
                        "amount" => $plan['amount'],
                        "currency" => $plan['currency'],
                        "interval" => $plan['interval'],
                        "interval_count" => $plan['interval_count'],
                        "trial_days" => $plan['trial_days']
                    ]
                );
                return $plan_;
            } catch (\Exception $exception) {
                return  [
                    'status' => false,
                    'message' => "create Plan: ".$exception->getMessage()
                ];
            }
        }
    }

    public function validatePlanData($plans, $getPlans, $order_id, $subscriptions, $customer, $order,$checkout)
    {
        foreach ($plans as $plan) {
            $plan_amount_cart = $plan['amount'];
            $plan_id_cart = (string)str_replace("-", "_",strtolower($plan['id_plan']));
        }
        $plan_amount_epayco = $getPlans->plan->amount;
        $plan_id_epayco = (string)str_replace("-", "_",strtolower($getPlans->plan->id_plan));
        if ($plan_id_cart == $plan_id_epayco) {
            try {
                if (intval($plan_amount_cart) == $plan_amount_epayco) {
                    return $this->process_payment_epayco($plans, $customer, $subscriptions, $order, $checkout);
                } else {
                    return $this->validateNewPlanData($plans, $order_id, $subscriptions,$customer, $order, $checkout);
                }
            } catch (\Exception $exception) {
                return [
                    'status' => false,
                    'message' => $exception->getMessage()
                ];
            }
        } else {
            return [
                'status' => false,
                'message' => 'el id del plan creado no concuerda!'
            ];
        }
    }

    public function validateNewPlanData($plans, $order_id, $subscriptions,$customer, $order, $checkout)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'epayco_plans';
        $wc_order_product_lookup = $wpdb->prefix . "wc_order_product_lookup";
        /*valida la actualizacion del precio del plan*/
        foreach ($subscriptions as $key => $subscription) {
            $products = $subscription->get_items();
            $product_plan = $this->getPlan($products);
            $product_id_ = $product_plan['id'];
            $porciones = explode("-", $product_id_);
            $product_id = $porciones[0];
        }
        $currency = strtolower(get_woocommerce_currency());
        $plan_currency = strtolower($plans[0]['currency']);
        if($currency == $plan_currency){
            $newPlans[] = [
                'id_plan' => $product_plan['name'].$product_plan['id'].$plans[0]['amount'],
                'name' => $plans[0]['name'],
                'description' => $plans[0]['description'],
                'currency' => $plans[0]['currency'],
                'trial_days' => $plans[0]['trial_days'],
                'interval' => $plans[0]['interval'],
                'amount' => $plans[0]['amount'],
                'interval_count' => $plans[0]['interval_count']
            ];
            $getPlans = $this->getPlans($newPlans);
            if(!$getPlans){
                return $this->validatePlan(true, $order_id, $newPlans, $subscriptions, $customer, $order, false, false, null,$checkout);
            }else{
                return $this->validatePlan(false, $order_id, $newPlans, $subscriptions, $customer, $order, true, false, $getPlans,$checkout);
            }
        }

    }

    public function getTrialDays(\WC_Subscription $subscription)
    {
        $trial_days = "0";
        $trial_start = $subscription->get_date('start');
        $trial_end = $subscription->get_date('trial_end');

        if ($trial_end > 0)
            $trial_days = (string)(strtotime($trial_end) - strtotime($trial_start)) / (60 * 60 * 24);

        return $trial_days;
    }

    public function intervalAmount(\WC_Subscription $subscription)
    {
        return [
            "interval" => $subscription->get_billing_period(),
            "amount" => $subscription->get_total(),
            "interval_count" => $subscription->get_billing_interval()
        ];
    }

    public function getPlans(array $plans)
    {
        foreach ($plans as $key => $plan) {
            try {
                $planId = str_replace("-", "_",strtolower($plans[$key]['id_plan']));
                $plan = $this->sdk->plan->get($planId);
                if ($plan->status) {
                    unset($plans[$key]);
                    return $plan;
                } else {
                    return false;
                }

            } catch (\Exception $exception) {
                return false;
            }
        }
    }

    public function cleanCharacters($string)
    {
        $string = str_replace(' ', '-', $string);
        $patern = '/[^A-Za-z0-9\-]/';
        return preg_replace($patern, '', $string);
    }

    public function paramsBilling($subscriptions, $order, $checkout)
    {
        $data = [];
        $subscription = end($subscriptions);
        if ($subscription) {
            $data['token_card'] = $checkout['token'];
            $data['customer_id'] = $checkout['customer_id'];
            $data['name'] = $checkout['name'];
            $data['email'] = $checkout['email'];
            $data['phone'] = $checkout['cellphone'];
            $data['country'] = $checkout['countrytype'];
            $data['city'] = $checkout['country'];
            $data['address'] = $checkout['address'];
            $data['doc_number'] = $checkout['doc_number'];
            $data['type_document'] = $checkout['identificationtype'];
            return $data;
        } else {
            $redirect = array(
                'result' => 'fail',
                'redirect' => add_query_arg('order-pay', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
            );
            wc_add_notice('EL producto que intenta pagar no es permitido', 'error');
            wp_redirect($redirect["redirect"]);
            die();
        }

    }

    public function process_payment_epayco(array $plans, array $customerData, $subscriptions, $order, $checkout)
    {
        $subsCreated = $this->subscriptionCreate($plans, $customerData, $checkout);
        if ($subsCreated->status) {
            $subs = $this->subscriptionCharge($plans, $customerData, $checkout);
            foreach ($subs as $sub) {
                $validation = !is_null($sub->status) ? $sub->status : $sub->success;
                if ($validation) {
                    $messageStatus = $this->handleStatusSubscriptions($subs, $subscriptions);
                    return $messageStatus;
                } else {
                    $errorMessage = $sub->data->errors;
                    $response_status = [
                        'success' => false,
                        'message' => $errorMessage
                    ];
                }
            }
        } else {
            $errorMessage = $subsCreated->data->description;
            $response_status = [
                'success' => false,
                'message' => $errorMessage,
            ];
        }
        return $response_status;
    }

    public function subscriptionCreate(array $plans, array $customer, $checkout)
    {
        $confirm_url = $checkout["confirm_url"];
        foreach ($plans as $plan) {
            try {
                $planId = str_replace("-", "_",strtolower($plan['id_plan']));
                $suscriptioncreted = $this->sdk->subscriptions->create(
                    [
                        "id_plan" => $planId,
                        "customer" => $customer['customer_id'],
                        "token_card" => $customer['token_card'],
                        "doc_type" => $customer['type_document'],
                        "doc_number" => $customer['doc_number'],
                        "url_confirmation" => $confirm_url,
                        "method_confirmation" => "POST"
                    ]
                );

                return $suscriptioncreted;

            } catch (\Exception $exception) {
                return [
                    'status' => false,
                    'message' => "subscriptionCreate ".$exception->getMessage()
                ];
            }
        }
    }

    public function subscriptionCharge(array $plans, array $customer, $checkout)
    {
        $subs = [];
        $confirm_url = $checkout["confirm_url"];
        foreach ($plans as $plan) {
            try {
                $planId = str_replace("-", "_",strtolower($plan['id_plan']));
                $subs[] = $this->sdk->subscriptions->charge(
                    [
                        "id_plan" => $planId,
                        "customer" => $customer['customer_id'],
                        "token_card" => $customer['token_card'],
                        "doc_type" => $customer['type_document'],
                        "doc_number" => $customer['doc_number'],
                        "ip" => $this->getCustomerIp(),
                        "url_confirmation" => $confirm_url,
                        "method_confirmation" => "POST"
                    ]
                );

            } catch (\Exception $exception) {
                return [
                    'status' => false,
                    'message' => "subscriptionCharge ".$exception->getMessage()
                ];
            }
        }

        return $subs;
    }

    public function handleStatusSubscriptions(array $subscriptionsStatus, array $subscriptions)
    {
        $count = 0;
        $messageStatus = [];
        $messageStatus['status'] = true;
        $messageStatus['success'] = false;
        $messageStatus['message'] = [];
        $messageStatus['estado'] = [];
        $messageStatus['ref_payco'] = [];
        $quantitySubscriptions = count($subscriptionsStatus);
        $orederStatus = array("Aprobada", "Aceptada", "Pendiente");
        foreach ($subscriptions as $subscription) {
            $sub = $subscriptionsStatus[$count];
            if($sub->status || $sub->success){
                if(isset($sub->data->estado)){
                    $messageStatus['ref_payco'] = array_merge($messageStatus['ref_payco'], [$sub->data->ref_payco]);
                    $messageStatus['message'] = array_merge($messageStatus['message'], ["estado: {$sub->data->respuesta}"]);
                    $messageStatus['estado'] = array_merge($messageStatus['estado'], [$sub->data->estado]);
                    if(in_array($sub->data->estado, $orederStatus)){
                        $messageStatus['success'] = true;
                    }
                }else{
                    $messageStatus['ref_payco'] = array_merge($messageStatus['ref_payco'], ['Pendiente']);
                    $messageStatus['message'] = array_merge($messageStatus['message'], ["estado: Pendiente"]);
                    $messageStatus['estado'] = array_merge($messageStatus['estado'], ['Pendiente']);
                    $messageStatus['success'] = true;
                }
            }

            $count++;
        }
        return $messageStatus;

    }


    public function string_sanitize($string, $force_lowercase = true, $anal = false) {
        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]","}", "\\", "|", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;","â€”", "â€“", "<", ">", "/", "?");
        $clean = trim(str_replace($strip, "", strip_tags($string)));
        $clean = preg_replace('/\s+/', "_", $clean);
        $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
        return $clean;
    }

    public function getCustomerIp(){
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    public function returnParameterToThankyouPage($transactionInfo, $payment)
    {
        $x_amount = $transactionInfo['data']['x_amount']??$transactionInfo['data']['amount'];
        $x_amount_base = $transactionInfo['data']['x_amount_base']??$transactionInfo['data']['taxBaseClient'];
        $x_cardnumber = $transactionInfo['data']['x_cardnumber']??$transactionInfo['data']['numberCard'];
        $x_id_invoice = $transactionInfo['data']['x_id_invoice']??$transactionInfo['data']['bill'];
        $x_franchise = $transactionInfo['data']['x_franchise']??$transactionInfo['data']['bank'];
        $x_transaction_id = $transactionInfo['data']['x_transaction_id']??$transactionInfo['data']['referencePayco'];
        $x_transaction_date = $transactionInfo['data']['x_transaction_date']??$transactionInfo['data']['transactionDate'];
        $x_transaction_state = $transactionInfo['data']['x_transaction_state']??$transactionInfo['data']['status'];
        $x_customer_ip = $transactionInfo['data']['x_customer_ip']??$transactionInfo['data']['ip'];
        $x_description = $transactionInfo['data']['x_description']??$transactionInfo['data']['description'];
        $x_response= $transactionInfo['data']['x_response']??$transactionInfo['data']['status'];
        $x_response_reason_text= $transactionInfo['data']['x_response_reason_text']??$transactionInfo['data']['response'];
        $x_approval_code= $transactionInfo['data']['x_approval_code']??$transactionInfo['data']['authorization'];
        $x_ref_payco= $transactionInfo['data']['x_ref_payco']??$transactionInfo['data']['referencePayco'];
        $x_tax= $transactionInfo['data']['x_tax']??$transactionInfo['data']['tax'];
        $x_currency_code= $transactionInfo['data']['x_currency_code']??$transactionInfo['data']['currency'];
        switch ($x_response) {
            case 'Aceptada': {
                $iconUrl = $payment->epayco->hooks->gateway->getGatewayIcon('check.png');
                $iconColor = '#67C940';
                $message = $payment->storeTranslations['success_message'];
            }break;
            case 'Pendiente':
            case 'Pending':{
                $iconUrl = $this->epayco->hooks->gateway->getGatewayIcon('warning.png');
                $iconColor = '#FFD100';
                $message = $payment->storeTranslations['pending_message'];
            }break;
            default: {
                $iconUrl = $payment->epayco->hooks->gateway->getGatewayIcon('error.png');
                $iconColor = '#E1251B';
                $message = $payment->storeTranslations['fail_message'];
            }break;
        }
        $donwload_url =get_site_url() . "/";
        $donwload_url = add_query_arg( 'wc-api', $payment::WEBHOOK_DONWLOAD, $donwload_url );
        $donwload_url = add_query_arg( 'refPayco', $x_ref_payco, $donwload_url );
        $donwload_url = add_query_arg( 'fecha', $x_transaction_date, $donwload_url );
        $donwload_url = add_query_arg( 'franquicia', $x_franchise, $donwload_url );
        $donwload_url = add_query_arg( 'descuento', '0', $donwload_url );
        $donwload_url = add_query_arg( 'autorizacion', $x_approval_code, $donwload_url );
        $donwload_url = add_query_arg( 'valor', $x_amount, $donwload_url );
        $donwload_url = add_query_arg( 'estado', $x_response, $donwload_url );
        $donwload_url = add_query_arg( 'descripcion', $x_description, $donwload_url );
        $donwload_url = add_query_arg( 'respuesta', $x_response, $donwload_url );
        $donwload_url = add_query_arg( 'ip', $x_customer_ip, $donwload_url );
        $is_cash = false;
        if($x_franchise == 'EF'||
            $x_franchise == 'GA'||
            $x_franchise == 'PR'||
            $x_franchise == 'RS'||
            $x_franchise == 'SR'
        ){
            $x_cardnumber_ = null;
            $is_cash = true;
        }else{
            if($x_franchise == 'PSE' || $x_franchise == 'DP' || $x_franchise == 'DaviPlata' ){
                $x_cardnumber_ = null;
            }else{
                $x_cardnumber_ = isset($x_cardnumber)?substr($x_cardnumber, -8):null;
            }
            $x_franchise = $x_franchise == 'DaviPlata' ? 'DP' : $x_franchise;
        }
        $transaction = [
            'franchise_logo' => 'https://secure.epayco.co/img/methods/'.$x_franchise.'.svg',
            'x_amount_base' => $x_amount_base,
            'x_cardnumber' => $x_cardnumber_,
            'status' => $x_response,
            'type' => "",
            'refPayco' => $x_ref_payco,
            'factura' => $x_id_invoice,
            'descripcion_order' => $x_description,
            'valor' => $x_amount,
            'iva' => $x_tax,
            'estado' => $x_transaction_state,
            'response_reason_text' => $x_response_reason_text,
            'respuesta' => $x_response,
            'fecha' => $x_transaction_date,
            'currency' => $x_currency_code,
            'name' => '',
            'card' => '',
            'message' => $message,
            'error_message' => $payment->storeTranslations['error_message'],
            'error_description' => $payment->storeTranslations['error_description'],
            'payment_method'  => $payment->storeTranslations['payment_method'],
            'response'=> $payment->storeTranslations['response'],
            'dateandtime' => $payment->storeTranslations['dateandtime'],
            'authorization' => $x_approval_code,
            'iconUrl' => $iconUrl,
            'iconColor' => $iconColor,
            'epayco_icon' => $this->epayco->hooks->gateway->getGatewayIcon('logo_white.png'),
            'ip' => $x_customer_ip,
            'totalValue' => $payment->storeTranslations['totalValue'],
            'description' => $payment->storeTranslations['description'],
            'reference' => $payment->storeTranslations['reference'],
            'purchase' => $payment->storeTranslations['purchase'],
            'iPaddress' => $payment->storeTranslations['iPaddress'],
            'receipt' => $payment->storeTranslations['receipt'],
            'authorizations' => $payment->storeTranslations['authorization'],
            'paymentMethod'  => $payment->storeTranslations['paymentMethod'],
            'epayco_refecence'  => $payment->storeTranslations['epayco_refecence'],
            'donwload_url' => $donwload_url,
            'donwload_text' => $payment->storeTranslations['donwload_text'],
            'code' => $payment->storeTranslations['code']??null,
            'is_cash' => $is_cash
        ];

        return $transaction;
    }
}