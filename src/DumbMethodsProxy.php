<?php

namespace ETNA\Silex\Provider\ConversationProxy;

use Guzzle\Http\Message\Request as GuzzleRequest;

use Silex\Application;
use Silex\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\Request;

class DumbMethodsProxy implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        if (!isset($app["conversation_proxy"])) {
            throw new \Exception("The guzzle client conversation_proxy is not set");
        }

        $controllers = $app["controllers_factory"];

        //Dumb proxy for messages
        $controllers->get("/conversations/{conversation}/messages/{message}", [$this, "loadMessage"]);
        $controllers->get("/conversations/{conversation}/messages", [$this, "loadMessages"]);
        $controllers->post("/conversations/{conversation}/messages", [$this, "writeMessage"]);

        //Dumb proxy for views
        $controllers->get("/conversations/{conversation}/messages/{message}/views", [$this, "loadViews"]);
        $controllers->match("/conversations/{conversation}/messages/{message}/view", [$this, "writeMessageView"])
            ->method("POST|PUT");
        $controllers->match("/conversations/{conversation}/views", [$this, "writeConversationViews"])
            ->method("POST|PUT");

        //Dumb proxy for likes
        $controllers->get("/conversations/{conversation}/messages/{message}/likes", [$this, "loadLikes"]);
        $controllers->match("/conversations/{conversation}/messages/{message}/like", [$this, "addLike"])
            ->method("POST|PUT");
        $controllers->delete("/conversations/{conversation}/messages/{message}/like", [$this, "removeLike"]);

        return $controllers;
    }

    public function loadMessage(Application $app, Request $req)
    {
        return $this->doProxyWithoutDatas($app, $req);
    }

    public function loadMessages(Application $app, Request $req)
    {
        return $this->doProxyWithoutDatas($app, $req);
    }

    public function writeMessage(Application $app, Request $req)
    {
        if (null === $req->request->get("content", null)) {
            return $app->abort(400, "Missing content field for message creation");
        }
        return $this->doProxyWithDatas($app, $req);
    }

    public function loadViews(Application $app, Request $req)
    {
        return $this->doProxyWithoutDatas($app, $req);
    }

    public function writeMessageView(Application $app, Request $req)
    {
        return $this->doProxyWithDatas($app, $req);
    }

    public function writeConversationViews(Application $app, Request $req)
    {
        return $this->doProxyWithDatas($app, $req);
    }

    public function loadLikes(Application $app, Request $req)
    {
        return $this->doProxyWithoutDatas($app, $req);
    }

    public function addLike(Application $app, Request $req)
    {
        return $this->doProxyWithoutDatas($app, $req);
    }

    public function removeLike(Application $app, Request $req)
    {
        return $this->doProxyWithoutDatas($app, $req);
    }

    public function doProxyWithoutDatas(Application $app, Request $req)
    {
        $request  = $app["conversation_proxy"]
            ->{$req->getMethod()}("{$req->getPathInfo()}?{$req->getQueryString()}");
        $response = self::requestWithJsonResponse($request, $app, $req->cookies->get("authenticator"));
        return $response;
    }

    public function doProxyWithDatas(Application $app, Request $req)
    {
        $request  = $app["conversation_proxy"]
            ->{$req->getMethod()}("{$req->getPathInfo()}?{$req->getQueryString()}", [], $req->request->all());
        $response = self::requestWithJsonResponse($request, $app, $req->cookies->get("authenticator"));
        return $response;
    }

    public static function requestWithJsonResponse(GuzzleRequest $request, Application $app, $cookie)
    {
        try {
            $response = $request
                ->addCookie("authenticator", $cookie)
                ->send();
            $headers  = $response->getHeaders()->toArray();
            return $app->json(json_decode($response->getBody()), 200, $headers);
        } catch (\Guzzle\Http\Exception\BadResponseException $client_error) {
            return $app->abort(
                $client_error->getResponse()->getStatusCode(),
                $client_error->getResponse()->getReasonPhrase()
            );
        }
    }
}
