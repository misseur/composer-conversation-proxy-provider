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
    /**
     * @{inherit doc}
     */
    public function register(Application $app)
    {
        $application_env = getenv("APPLICATION_ENV");
        $app->register(new ConversationConfig($application_env));
    }

    /**
     * @{inherit doc}
     */
    public function boot(Application $app)
    {
        return $app;
    }
}
