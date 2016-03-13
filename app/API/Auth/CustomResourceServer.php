<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 13.03.16
 * Time: 16:37
 */

namespace App\API\Auth;


use League\OAuth2\Server\Exception\InvalidRequestException;
use League\OAuth2\Server\ResourceServer;

class CustomResourceServer extends ResourceServer
{
    /**
     * Reads in the access token from the headers
     *
     * @param bool $headerOnly Limit Access Token to Authorization header
     *
     * @throws \League\OAuth2\Server\Exception\InvalidRequestException Thrown if there is no access token presented
     *
     * @return string
     */
    public function determineAccessToken($headerOnly = false)
    {
        if ($this->getRequest()->headers->get('Authorization') !== null) {
            $accessToken = $this->getTokenType()->determineAccessTokenInHeader($this->getRequest());
        } elseif ($headerOnly === false && (! $this->getTokenType() instanceof MAC)) {
            $accessToken = $this->getRequest()->query->get($this->tokenKey);
        }

        if (empty($accessToken)) {
            throw new InvalidRequestException('access token');
        }

        return $accessToken;
    }
}