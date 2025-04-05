<?php

namespace Epayco\Woocommerce\Configs;

use Exception;
use Epayco\Woocommerce\Helpers\Cache;
use Epayco\Woocommerce\Hooks\Options;

if (!defined('ABSPATH')) {
    exit;
}

class Seller
{

    private const CREDENTIALS_P_CUST_ID = '_ep_p_cust_id';

    private const CREDENTIALS_PUBLIC_KEY = '_ep_publicKey';

    private const CREDENTIALS_PRIVATE_KEY = '_ep_private_key';

    private const CREDENTIALS_P_KEY = '_ep_p_key';

    private const CHECKBOX_CHECKOUT_TEST_MODE = 'checkbox_checkout_test_mode';

    private const HOMOLOG_VALIDATE = 'homolog_validate';
    private const TEST_USER = '_test_user_v1';

    private const EP_APIFY  = "https://apify.epayco.io";
    private Cache $cache;
    private Options $options;

    /**
     * Credentials constructor
     *
     * @param Cache $cache
     * @param Options $options
     */
    public function __construct(Cache $cache, Options $options)
    {
        $this->cache     = $cache;
        $this->options   = $options;
    }

    /**
     * @param string $publicKey
     * @param string $type
     *
     * @return array
     */
    public function validateEpaycoCredentials(string $publicKey, string $private_key): array
    {
        return $this->validateCredentialsPayment( $publicKey, $private_key, true);
    }

    /**
     * @param string $credentialsPcustId
     */
    public function setCredentialsPCustId(string $credentialsPcustId): void
    {
        $this->options->set(self::CREDENTIALS_P_CUST_ID, $credentialsPcustId);
    }

    /**
     * @return string
     */
    public function getCredentialsPCustId(): string
    {
        return $this->options->get(self::CREDENTIALS_P_CUST_ID, '');
    }

    /**
     * @return string
     */
    public function getCredentialsPublicKeyPayment(): string
    {
        return $this->options->get(self::CREDENTIALS_PUBLIC_KEY, '');
    }

    /**
     * @param string $credentialsPublicKey
     */
    public function setCredentialsPublicKeyPayment(string $credentialsPublicKey): void
    {
        $this->options->set(self::CREDENTIALS_PUBLIC_KEY, $credentialsPublicKey);
    }

    /**
     * @param string $key
     * @param string $type
     *
     * @return array
     */
    public function validatePublicKeyPayment(string $type, string $key): array
    {
        return $this->validateCredentialsPayment( $type, $key);
    }

    /**
     * @param string $credentialsPrivateKey
     */
    public function setCredentialsPrivateKeyPayment(string $credentialsPrivateKey): void
    {
        $this->options->set(self::CREDENTIALS_PRIVATE_KEY, $credentialsPrivateKey);
    }

    /**
     * @return string
     */
    public function getCredentialsPrivateKeyPayment(): string
    {
        return $this->options->get(self::CREDENTIALS_PRIVATE_KEY, '');
    }

    /**
     * @param string $credentialsPKey
     */
    public function setCredentialsPkey(string $credentialsPKey): void
    {
        $this->options->set(self::CREDENTIALS_P_KEY, $credentialsPKey);
    }

    /**
     * @return string
     */
    public function getCredentialsPkey(): string
    {
        return $this->options->get(self::CREDENTIALS_P_KEY, '');
    }

    /**
     * @return bool
     */
    public function isTestMode(): bool
    {
        return $this->getCheckboxCheckoutTestMode() === 'yes';
    }



    /**
     * @return string
     */
    public function getCheckboxCheckoutTestMode(): string
    {
        return $this->options->get(self::CHECKBOX_CHECKOUT_TEST_MODE, 'yes');
    }

    /**
     * @param string $checkboxCheckoutTestMode
     */
    public function setCheckboxCheckoutTestMode(string $checkboxCheckoutTestMode): void
    {
        $this->options->set(self::CHECKBOX_CHECKOUT_TEST_MODE, $checkboxCheckoutTestMode);
    }

    /**
     * @return bool
     */
    public function getHomologValidate(): bool
    {
        return $this->options->get(self::HOMOLOG_VALIDATE);
    }

    /**
     * @param bool $homologValidate
     */
    public function setHomologValidate(bool $homologValidate): void
    {
        $this->options->set(self::HOMOLOG_VALIDATE, $homologValidate);
    }

    /**
     * @return bool
     */
    public function getTestUser(): bool
    {
        return $this->options->get(self::TEST_USER);
    }

    /**
     * @param bool $testUser
     */
    public function setTestUser(bool $testUser): void
    {
        $this->options->set(self::TEST_USER, $testUser);
    }

    /**
     * @return bool
     */
    public function isTestUser(): bool
    {
        return $this->getTestUser();
    }


    /**
     * Validate credentials
     *
     * @param string|null $type
     * @param string|null $key
     *
     * @return array
     */
    private function validateCredentialsPayment( string $type = null, string $keys = null, bool $validate = false): array
    {
        try {
            $publicKey = trim($type);
            $private_key = trim($keys);
            if(!$validate){
                $key   = sprintf('%s%s',$publicKey, $private_key);
                $cache = $this->cache->getCache($key);
                if ($cache) {
                    return $cache;
                }
                $serializedResponse = [
                    'data'   => $key,
                    'status' => 200,
                ];
                $this->cache->setCache($key, $serializedResponse);
            }else{
                $serializedResponse = [
                    'data'   =>[],
                    'status' => false,
                ];
                $headers = [];
                $uri     = '/login';
                $accessToken = base64_encode($publicKey.":".$private_key);
                $headers[] = 'Authorization: Basic ' . $accessToken;
                $headers[] = 'Content-Type: application/json ';
                $body = array(
                    'public_key' => $publicKey,
                    'private_key' => $private_key,
                );
                $response           = $this->my_woocommerce_post_request($uri, $headers, $body);
                if(isset($response) && $response['token']){
                    $serializedResponse = [
                        'data'   => $response['token'],
                        'status' => true,
                    ];
                }
            }
            return $serializedResponse;
        } catch (Exception $e) {
            return [
                'data'   => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    private function my_woocommerce_post_request($uri, $headers, $body = []) {
        $url = self::EP_APIFY.$uri;
        /*$response = wp_remote_post( $url, array(
            'body'    => wp_json_encode( $body ),
            'headers' => $headers,
            'method'  => 'POST',
        ));*/
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => $headers,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return "Something went wrong: $error_message";
        }
        //$response_body = wp_remote_retrieve_body( $response );
        $response_data = json_decode( $response, true );

        return $response_data;
    }


}