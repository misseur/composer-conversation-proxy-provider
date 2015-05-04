<?php

namespace TestProxy;

use Silex\Application;

use ETNA\Silex\Provider\ConversationProxy\ConversationProxyProvider;
use Silex\ServiceProviderInterface;

/**
 * Configuration principale de l'application
 */
class AppConfig implements ServiceProviderInterface
{
    private $app = null;

    /**
     * @{inherit doc}
     */
    public function register(Application $app)
    {
        $this->app = $app;
        $app->register(new ConversationProxyProvider());
    }

    /**
     * @{inherit doc}
     */
    public function boot(Application $app)
    {
        $app = $app;
    }
}
