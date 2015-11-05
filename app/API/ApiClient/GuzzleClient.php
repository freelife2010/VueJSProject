<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 30.10.15
 * Time: 10:55
 */

namespace App\API\ApiClient;


use GuzzleHttp\Client;
use Exception;

trait GuzzleClient {

    protected $client = null;

    protected $config = [
        'base_uri' => 'http://chat.binacube.com/',
        'timeout'  => 2.5
    ];

    protected function createHttpClient($config =[])
    {
        $config = $config ?: $this->config;
        $this->client = $this->client ?: new Client($config);
    }

    public function sendRequest($resource, $data = [], $method = 'GET')
    {
        $data = $data ? ['json' => $data] : [];
        try {
            $result = $this->client->request($method, $resource, $data);
        } catch (Exception $e) {
            $result = $e;
        }
        return $result;
    }

    public function sendPost($resource, $data)
    {
        $jsonType = [
            'content-type' => 'application/json'
        ];

        return $this->client->post($resource, $jsonType, $data);
    }
}