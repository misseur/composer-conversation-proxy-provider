# composer-conversation-proxy-provider
Permets aux differentes applications d'avoir un proxy vers conversation-api

Installation
------------

Modifier `composer.json` :

```
{
    // ...
    "require": {
        "etna/conversation-proxy-provider": "~0.1"
    },
    "repositories": [
       {
           "type": "composer",
           "url": "http://blu-composer.herokuapp.com"
       }
   ]
}
```

Utilisation
-----------

Créer un tableau qui va définir des routes et des callbacks pour les routes conversations :

```
        $this->proxy_routes    = [
            "/conversations"                         => [
                "callback" => [$this, 'createConversation'],
                "methods"  => [
                    "post" => null,
                ],
            ],
            "/conversation/search"                   => [
                "callback" => [$this, 'searchConversation'],
                "methods"  => [
                    "get" => null,
                ],
            ],
            "/conversations/{conversation}/messages" => [
                "callback" => [$this, 'createMessage'],
                "methods"  => [
                    "post" => null,
                ],
            ],
        ];
```

Dans le champ methods, la clef représente la methode HTTP, la valeur : une callback spécifique si on désire en appeler une autre que celle définie dans le champ callback.


Ne pas oublier de setter dans l'env les url pour l'api de conversations.
