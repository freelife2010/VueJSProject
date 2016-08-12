<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 28.12.15
 * Time: 14:07
 */

namespace App\Helpers;


use Exception;

class PaypalSDK
{
    const VERSION = 51.0;
    /**
     * List of valid API environments
     * @var array
     */
    private $allowedEnvs = [
        'beta-sandbox',
        'live',
        'sandbox'
    ];

    private $username = 'developer_api1.portal.dev';
    private $password = '7NLD5F9XN4CMKF5S';
    private $signature = 'AFcWxV21C7fd0v3bYYYRCpSSRl31AvhNpwhD6vGWXlRZp99kDVhQFyfa';
    /**
     * Config storage from constructor
     * @var array
     */
    private $config = [];
    /**
     * URL storage based on environment
     * @var string
     */
    private $url;

    /**
     * Build PayPal API request
     *
     * @param string $environment
     * @throws Exception
     */
    public function __construct($environment = 'sandbox')
    {
        if (!in_array($environment, $this->allowedEnvs)) {
            throw new Exception('Specified environment is not allowed.');
        }
        $this->config = [
            'username'    => $this->username,
            'password'    => $this->password,
            'signature'   => $this->signature,
            'environment' => $environment
        ];
    }

    /**
     * Make a request to the PayPal API
     *
     * @param  string $method API method (e.g. GetBalance)
     * @param  array $params Additional fields to send in the request (e.g. array('RETURNALLCURRENCIES' => 1))
     * @return array
     */
    public function call($method, array $params = [])
    {
        $fields = $this->encodeFields(array_merge(
            [
                'METHOD'    => $method,
                'VERSION'   => self::VERSION,
                'USER'      => $this->config['username'],
                'PWD'       => $this->config['password'],
                'SIGNATURE' => $this->config['signature']
            ],
            $params
        ));
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getUrl());
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        if (!$response) {
            throw new Exception('Failed to contact PayPal API: ' . curl_error($ch) . ' (Error No. ' . curl_errno($ch) . ')');
        }
        curl_close($ch);
        parse_str($response, $result);

        return $this->decodeFields($result);
    }

    /**
     * Prepare fields for API
     *
     * @param  array $fields
     * @return array
     */
    private function encodeFields(array $fields)
    {
        return array_map('urlencode', $fields);
    }

    /**
     * Make response readable
     *
     * @param  array $fields
     * @return array
     */
    private function decodeFields(array $fields)
    {
        return array_map('urldecode', $fields);
    }

    /**
     * Get API url based on environment
     *
     * @return string
     */
    private function getUrl()
    {
        if (is_null($this->url)) {
            switch ($this->config['environment']) {
                case 'sandbox':
                case 'beta-sandbox':
                    $environment = $this->config['environment'];
                    $this->url = "https://api-3t.$environment.paypal.com/nvp";
                    break;
                default:
                    $this->url = 'https://api-3t.paypal.com/nvp';
            }
        }

        return $this->url;
    }
}