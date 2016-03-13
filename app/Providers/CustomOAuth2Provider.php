<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 13.03.16
 * Time: 16:54
 */

namespace App\Providers;


use LucaDegasperi\OAuth2Server\Authorizer;
use LucaDegasperi\OAuth2Server\OAuth2ServerServiceProvider;
use League\OAuth2\Server\AuthorizationServer;
use App\API\Auth\CustomResourceServer as ResourceServer;
use League\OAuth2\Server\Storage\AccessTokenInterface;
use League\OAuth2\Server\Storage\AuthCodeInterface;
use League\OAuth2\Server\Storage\ClientInterface;
use League\OAuth2\Server\Storage\RefreshTokenInterface;
use League\OAuth2\Server\Storage\ScopeInterface;
use League\OAuth2\Server\Storage\SessionInterface;

class CustomOAuth2Provider extends OAuth2ServerServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerAuthorizer();
        $this->registerMiddlewareBindings();
    }

    /**
     * Register the Authorization server with the IoC container.
     *
     * @return void
     */
    public function registerAuthorizer()
    {
        $this->app->bindShared('oauth2-server.authorizer', function ($app) {
            $config = $app['config']->get('oauth2');
            $issuer = $app->make(AuthorizationServer::class)
                ->setClientStorage($app->make(ClientInterface::class))
                ->setSessionStorage($app->make(SessionInterface::class))
                ->setAuthCodeStorage($app->make(AuthCodeInterface::class))
                ->setAccessTokenStorage($app->make(AccessTokenInterface::class))
                ->setRefreshTokenStorage($app->make(RefreshTokenInterface::class))
                ->setScopeStorage($app->make(ScopeInterface::class))
                ->requireScopeParam($config['scope_param'])
                ->setDefaultScope($config['default_scope'])
                ->requireStateParam($config['state_param'])
                ->setScopeDelimiter($config['scope_delimiter'])
                ->setAccessTokenTTL($config['access_token_ttl']);

            // add the supported grant types to the authorization server
            foreach ($config['grant_types'] as $grantIdentifier => $grantParams) {
                $grant = $app->make($grantParams['class']);
                $grant->setAccessTokenTTL($grantParams['access_token_ttl']);

                if (array_key_exists('callback', $grantParams)) {
                    list($className, $method) = array_pad(explode('@', $grantParams['callback']), 2, 'verify');
                    $verifier = $app->make($className);
                    $grant->setVerifyCredentialsCallback([$verifier, $method]);
                }

                if (array_key_exists('auth_token_ttl', $grantParams)) {
                    $grant->setAuthTokenTTL($grantParams['auth_token_ttl']);
                }

                if (array_key_exists('refresh_token_ttl', $grantParams)) {
                    $grant->setRefreshTokenTTL($grantParams['refresh_token_ttl']);
                }

                if (array_key_exists('rotate_refresh_tokens', $grantParams)) {
                    $grant->setRefreshTokenRotation($grantParams['rotate_refresh_tokens']);
                }

                $issuer->addGrantType($grant);
            }

            $checker = $app->make(ResourceServer::class);

            $authorizer = new Authorizer($issuer, $checker);
            $authorizer->setRequest($app['request']);
            $authorizer->setTokenType($app->make($config['token_type']));

            $app->refresh('request', $authorizer, 'setRequest');

            return $authorizer;
        });

        $this->app->bind(Authorizer::class, function ($app) {
            return $app['oauth2-server.authorizer'];
        });
    }
}