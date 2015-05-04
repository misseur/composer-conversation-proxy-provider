<?php

namespace TestProxy;

class App extends \Silex\Application
{
    public function __construct(array $values = array())
    {
        parent::__construct($values);

        putenv("CONVERSATION_API_URL=http://conversation-api.etna.dev");

        $this->register(new AppConfig);
    }
}
