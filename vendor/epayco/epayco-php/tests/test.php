<?php

include __DIR__ . '/../vendor/autoload.php';

use Epayco\Epayco;
use Epayco\Client;

class AccessSDKTest extends PHPUnit_Framework_TestCase
{

    protected $epayco;
    protected $apiKey = "491d6a0b6e992cf924edd8d3d088aff1";
    protected $privateKey = "268c8e0162990cf2ce97fa7ade2eff5a";
    protected $lenguage = "ES";
    protected $test = true;
    protected $client;
    protected $testCard = array(
                    "card[number]" => '4575623182290326',
                    "card[exp_year]" => "2017",
                    "card[exp_month]" => "07",
                    "card[cvc]" => "123");
    protected $token;

    /**
     * Init sdk epayco
     */
    protected function setUp()
    {
        $this->epayco = new Epayco(array(
            "apiKey" => $this->apiKey,
            "privateKey" => $this->privateKey,
            "lenguage" => $this->lenguage,
            "test" => $this->test
        ));
        $this->client = new Client();
    }

    /**
     * Create token credit card form tokenization
     * @return string
     */
    protected function createToken()
    {

        $response = $this->client->request(
            "POST",
            "/v1/tokens",
            $api_key = $this->apiKey,
            $options = $this->testCard,
            $private_key = $this->privateKey,
            $test = $this->test,
            $switch = false,
            $lang = $this->lenguage
        );
        $this->token = $response->id;

        return $this->token;
    }

    /**
     * Create clien and token credit card
     * @return object
     */
    protected function createClient()
    {
        
        $token = $this->createToken();
        $client = $this->epayco->customer->create(array(
            "token_card" => $token,
            "name" => "Joe Doe",
            "email" => "joe" . rand() . "@payco.co",
            "phone" => "3005234321",
            "default" => true
        ));

        $array = array(
            "token" => $token,
            "clientId" => $client->data->customerId
        );

        return (Object) $array;
    }

    /* Customers */
    public function testCreateClient()
    {
        $token = $this->createToken();

        $client = $this->epayco->customer->create(array(
            "token_card" => $token,
            "name" => "Joe Doe",
            "email" => "joe@payco.co",
            "phone" => "3005234321",
            "default" => true
        ));

        $this->assertTrue(strlen($client->data->customerId) > 0);
    }

    public function testGetClient()
    {
        $customers = $this->epayco->customer->getList()->data;
        $customerId = $customers[0]->id_customer;
        $response = $this->epayco->customer->get($customerId);
        $this->assertGreaterThanOrEqual(1, count($response));
    }

    public function testGetClients()
    {
        $response = $this->epayco->customer->getList();
        $this->assertGreaterThanOrEqual(1, count($response));
    }

    /* Plans */
    public function testCreatePlan()
    {
        $plan = $this->epayco->plan->create(array(
            "id_plan" => $this->randomString(20),
            "name" => "Course react js",
            "description" => "Course react and redux",
            "amount" => 30000,
            "currency" => "cop",
            "interval" => "month",
            "interval_count" => 1,
            "trial_days" => 30
        ));
        $this->assertTrue(strlen($plan->data->id_plan) > 0);
    }

    public function testGetPlan()
    {
        $response = $this->epayco->plan->getList();
        $this->assertGreaterThanOrEqual(1, count($response));
    }

    public function testEditPlan()
    {
        $response = $this->epayco->plan->update("coursereact", array("interval_count" => 4));
        $this->assertGreaterThanOrEqual(1, count($response));
    }

    /* Subscriptions */
    public function testCreateSubscription()
    {
        $data = $this->createClient();
        $sub = $this->epayco->subscriptions->create(array(
            "id_plan" => $this->randomString(20),
            "customer" => $data->clientId,
            "doc_type" => 'CC',
            "doc_number" => rand() . 'test.php',
            "token_card" => $data->token
        ));
        $this->assertTrue(strlen($sub->data->suscription) > 0);
    }

    public function testGetSuscription()
    {
        $subs = $this->epayco->subscriptions->getList();
        $subId = $subs->plans[0]->_id;
        $request = $this->epayco->subscriptions->get($subId);
        $this->assertTrue(strlen($request) > 0);
    }

    public function testListSubscriptions()
    {
        $subs = $this->epayco->subscriptions->getList();
        $this->assertGreaterThanOrEqual(1, count($subs));
    }

    public function testCancelSubscription()
    {
        $response = $this->epayco->subscriptions->cancel(array(
            "id" => "TxmRjbKWFbsNaNtRW"
        ));
        $this->assertGreaterThanOrEqual(1, count($response));
    }

    public function testPseCreate()
    {
        $response = $this->epayco->bank->create(array(
            "bank" => "1007",
            "invoice" => "1472050778",
            "description" => "Pago pruebas",
            "value" => "10000",
            "tax" => "0",
            "tax_base" => "0",
            "currency" => "COP",
            "type_person" => "0",
            "doc_type" => "CC",
            "doc_number" => "10358519",
            "name" => "PRUEBAS",
            "last_name" => "PAYCO",
            "email" => "no-responder@payco.co",
            "country" => "CO",
            "cell_phone" => "3010000001",
            "ip" => "186.116.10.133",
            "url_response" => "https:/secure.payco.co/restpagos/testRest/endpagopse.php",
            "url_confirmation" => "https:/secure.payco.co/restpagos/testRest/endpagopse.php",
            "method_confirmation" => "GET",
        ));
        $this->assertGreaterThanOrEqual(1, count($response));
    }

    public function testCashCreate()
    {
        $end_date = (new \DateTime())->add((new \DateInterval('P1D')))->format('Y-m-d H:i');
        $response = $this->epayco->cash->create("efecty", array(
            "invoice" => "1472050778",
            "description" => "pay test",
            "value" => "20000",
            "tax" => "0",
            "tax_base" => "0",
            "currency" => "COP",
            "type_person" => "0",
            "doc_type" => "CC",
            "doc_number" => "12344545",
            "name" => "testing",
            "last_name" => "PAYCO",
            "email" => "test@test.com",
            "cell_phone" => "3010000001",
            "end_date" => $end_date,
            "url_response" => "https:/secure.payco.co/restpagos/testRest/endpagopse.php",
            "url_confirmation" => "https:/secure.payco.co/restpagos/testRest/endpagopse.php",
            "method_confirmation" => "GET",
        ));
        $this->assertGreaterThanOrEqual(1, count($response));
    }
    
    public function testPsebanks()
    {
        $response = $this->epayco->bank->pseBank();
        $this->assertTrue($response->success);
        $this->assertObjectHasAttribute('data', $response);
        $this->assertNotEmpty($response->data);
        $this->assertObjectHasAttribute('bankName', $response->data[0]);
    }

    /**
     * @param int $length
     * @return string
     */
    protected function randomString($length = 10)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}