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

Il faut tout d'abord creer une classe pour la configuration de ce provider :
```
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

        //Dans le cas ou l'on souhaite fournir son propre controlleur
        $controller = new ConversationController();
        $app->register(new ConversationProxyProvider($controller));
        //Sinon
        $app->register(new ConversationProxyProvider());
    }
}
```

Ce provider met a disposition un `DumbMethodsProxy` qui fournit toutes les routes basiques de conversations :
 - Likes
 - Message
 - Views

Il est possible de creer un controlleur qui hérite de ce `DumbMethodsProxy` pour rajouter des routes custom :
```
class ConversationController extends DumbMethodsProxy
{
    public function connect(Application $app)
    {
        //Si il y'a besoin des routes basiques
        $controllers = parent::connect($app);
        //Sinon
        $controllers = $app["controllers_factory"];

        $controllers->get("/contract/{contract_id}/conversation", [$this, 'getConversation']);
        $controllers->post("/contract/{contract_id}/conversation", [$this, 'createConversation']);
    }

    public function getConversation(Application $app, $contract_id)
    {
        $conversation = $app["conversations"]->findOneByQueryString("+contract_id:{$contract_id} +app-name:gsa");

        return $app->json($conversation->toArray(), 200);
    }

    public function createConversation(Application $app, $contract_id)
    {
        $conversation = new Conversation();

        $conversation->setTitle("GSA - Contract {$contract_id}");

        $response = $app["conversations"]->save($conversation);

        return $app->json($response, 201);
    }
}
```

Ce provider met a disposition :
- L'objet Conversation, qui est une "entité" qui se comporte comme une entité doctrine le ferait
 - On peut la remplir avec un array grace a `$conversation->fromArray($array)`
 - On peut la serializer en array grace a `$conversation->toArray()`
- L'objet ConversationManager ($app["conversations"]) qui lui se comporte comme l'entity manager de doctrine, sauf qu'il permet aussi de recuperer des conversations. Il met a disposition les methodes :
 - `findByQueryString` qui prends en parametre une query string ElasticSearch (+contract_id:42 +app-name:gsa) et qui retourne un tableau de Conversations
 - `findOneByQueryString` qui prends aussi en parametre une query string mais retourne l'objet le plus pertinent
 - `save` qui prend en paramètre une Conversation et effectue les requetes necessaires pour sauvegarder les changements
