<?php

namespace TestProxy;

use ETNA\Silex\Provider\ConversationProxy\ConversationProxyProvider;
use ETNA\Silex\Provider\ConversationProxy\Conversation;

use Silex\Application;
use Silex\ServiceProviderInterface;

use Symfony\Component\HttpFoundation\Request;

class ConversationConfig implements ServiceProviderInterface
{
    private $application_env;

    public function __construct($application_env = null)
    {
        if (null === $application_env) {
            throw new \Exception("Application env is not set");
        }
        $this->application_env = $application_env;
    }

    public function boot(Application $app)
    {
        return $app;
    }

    public function register(Application $app)
    {
        switch ($this->application_env) {
            case "production":
                putenv("CONVERSATION_API_URL=https://conversation-api.etna-alternance.net");
                break;
            case "development":
                putenv("CONVERSATION_API_URL=http://conversation-api.etna.dev");
                break;
        }
        $app->register(new ConversationProxyProvider());

        $app->get("/contract/{id}/conversation", function (Application $app, Request $req, $id) {
            $conversation = $app["conversations"]->findByQueryString($req, "+test");
            return "test";
        });
    }
}
