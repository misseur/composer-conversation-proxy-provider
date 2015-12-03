# composer-conversation-proxy-provider
Permets aux differentes applications d'avoir un proxy vers conversation-api

[![GitHub version](https://badge.fury.io/gh/etna-alternance%2Fcomposer-conversation-proxy-provider.svg)](https://badge.fury.io/gh/etna-alternance%2Fcomposer-conversation-proxy-provider)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/etna-alternance/composer-conversation-proxy-provider/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/etna-alternance/composer-conversation-proxy-provider/?branch=master)
[![Dependency Status](https://www.versioneye.com/user/projects/56604422f376cc003c00030f/badge.svg?style=flat)](https://www.versioneye.com/user/projects/56604422f376cc003c00030f)

## Installation

Modifier `composer.json` :

```
{
    // ...
    "require": {
        "etna/conversation-proxy-provider": "~1.0.x"
    },
    "repositories": [
       {
           "type": "composer",
           "url": "http://blu-composer.herokuapp.com"
       }
   ]
}
```

## Utilisation

### Déclarer le composant

Le composant `etna/config-provider` met à disposition une classe permettant de faire utiliser ce proxy a notre application.

Lors de la configuration de l'application il faut donc utiliser la classe `ETNA\Silex\Provider\Config\ConversationProxy` :

```
use ETNA\Silex\Provider\Config as ETNAConf;

class EtnaConfig implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        ...

        //L'utilisation du controlleur custom est expliquée plus bas
        $my_controller = new ConversationController();
        $app->register(new ETNAConf\ConversationProxy($my_controller));

        ...
    }
}
```

### Le contenu de ce composant

##### Le controlleur custom

Ce provider met a disposition un `DumbMethodsProxy` qui fournit toutes les routes basiques de conversations :
 - Likes
 - Message
 - Views
 - Recherche

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

##### Les plus de ce proxy

Ce provider met a disposition :
- L'objet Conversation, qui est une "entité" qui se comporte comme une entité doctrine le ferait
 - On peut la remplir avec un array grace a `$conversation->fromArray($array)`
 - On peut la serializer en array grace a `$conversation->toArray()`
- L'objet ConversationManager ($app["conversations"]) qui lui se comporte comme l'entity manager de doctrine, sauf qu'il permet aussi de recuperer des conversations. Il met a disposition les methodes :
 - `findByQueryString` qui prends en parametre une query string ElasticSearch (+contract_id:42 +app-name:gsa) et qui retourne un tableau de Conversations
 - `findOneByQueryString` qui prends aussi en parametre une query string mais retourne l'objet le plus pertinent
 - `save` qui prend en paramètre une Conversation et effectue les requetes necessaires pour sauvegarder les changements
