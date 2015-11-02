<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 30.10.15
 * Time: 10:55
 */

namespace App\Models\ApiClient;


use GuzzleHttp\Client;
use Exception;

trait GuzzleClient {

    protected $client;

    protected $config = [
        'base_uri' => 'http://107.155.99.21:3000/api/',
        'timeout'  => 2.5
    ];

    protected function createHttpClient($config =[])
    {
        $config = $config ?: $this->config;
        return new Client($config);
    }

    public function sendRequest($method = 'GET', $resource, $data = [])
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