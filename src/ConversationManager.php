<?php

namespace ETNA\Silex\Provider\ConversationProxy;

use Guzzle\Http\Message\Request as GuzzleRequest;

use Silex\Application;

use Symfony\Component\HttpFoundation\Request;

class ConversationManager
{
    private $app;

    public function __construct(Application $app = null)
    {
        if (null === $app) {
            throw new \Exception("Conversation manager require $app to be set");
        }
        $this->app = $app;
    }

    public function findByQueryString($query, $from = 0, $size = 99999)
    {
        $query = urlencode($query);
        $request = $this->app["conversation_proxy"]
            ->get("/search?q={$query}&from={$from}&size={$size}");
        $response = $this->fireRequest($request, $this->app["cookies.authenticator"]);

        $conversations_array = [];
        $conversations       = $response["conversations"]["hits"];
        foreach ($conversations as $conversation) {
            $conversation_item = new Conversation();
            $conversation_item->fromArray($conversation);
            $conversations_array[] = $conversation_item;
        }
        return $conversations_array;
    }

    public function findOneByQueryString($query)
    {
        $matching = $this->findByQueryString($query, 0, 1);
        if (0 === count($matching)) {
            return null;
        }
        return $matching[0];
    }

    public function save(Conversation $conversation)
    {
        $actions  = $conversation->getSaveActions();
        $response = null;

        if ($actions === [["method" => "post", "route" => "/conversations"]]) {
            $body = $conversation->toArray();
            if (false === isset($body["messages"][0])) {
                return $this->app->abort(400, "Need content to create conversation");
            }

            $body["content"] = $body["messages"][0]["content"];
            $body["metas"]   = json_encode($body["metas"]);
            unset($body["messages"]);

            $request  = $this->app["conversation_proxy"]->post("/conversations", [], $body);
            $response = $this->fireRequest($request, $this->app["cookies.authenticator"]);
        } else {
            foreach ($actions as $action) {
                $route  = $action["route"];
                $method = $action["method"];
                unset($action["route"]);
                unset($action["method"]);

                $request  = $this->app["conversation_proxy"]->{$method}($route, [], $action);
                $response = $this->fireRequest($request, $this->app["cookies.authenticator"]);
            }
        }
        return $response;
    }

    public function toJsonResponse(array $conversations)
    {
        foreach ($conversations as $index => $conversation) {
            $conversations[$index] = $conversation->toArray();
        }
        return $conversations;
    }

    private function fireRequest(GuzzleRequest $request, $cookie)
    {
        try {
            $response = $request
                ->addCookie('authenticator', $cookie)
                ->send();
            return $response->json();
        } catch (\Guzzle\Http\Exception\BadResponseException $client_error) {
            return $this->app->abort(
                $client_error->getResponse()->getStatusCode(),
                $client_error->getResponse()->getReasonPhrase()
            );
        }
    }
}
