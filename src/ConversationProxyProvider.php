<?php

namespace ETNA\Silex\Provider\ConversationProxy;

use Guzzle\Http\Client;

use Silex\Application;
use Silex\ServiceProviderInterface;

class ConversationProxyProvider implements ServiceProviderInterface
{
    private $routes = null;

    public function __construct($route = null)
    {
        $this->routes = $route;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        return $app;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app["conversation_proxy"] = $app->share(function ($app) {
            $conversation_api_url = getenv("CONVERSATION_API_URL");
            if (false === $conversation_api_url) {
                throw new \Exception("ConversationProxyProvider needs env var CONVERSATION_API_URL");
            }

            return new Client("{$conversation_api_url}", []);
        });

        $app->mount("/", new ConversationProxyController($this->routes));
    }
}
