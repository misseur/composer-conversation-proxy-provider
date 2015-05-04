<?php

namespace ETNA\Silex\Provider\ConversationProxy;

use Silex\Application;
use Silex\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\Request;

class ConversationProxyController implements ControllerProviderInterface
{
    private $routes;

    public function __construct($routes = null)
    {
        $routes = $routes ?: [
            "/conversation" => [
                "callback" => [$this, "doProxy"],
                "methods"  => [
                    "post" => null,
                ],
            ],
            "/conversations/{conversation}" => [
                "callback" => [$this, "doProxy"],
                "methods"  => [
                    "get"    => null,
                    "put"    => null,
                    "delete" => null,
                ],
            ],
        ];
        $this->routes = $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        /* @var $controllers ControllerCollection */
        $controllers = $app['controllers_factory'];

        foreach ($this->routes as $route => $route_info) {
            if (false === is_callable($route_info["callback"])) {
                throw new \Exception("The callback for route {$route} is not callable");
            }

            $methods_with_default_cb = array_keys(
                array_filter(
                    $route_info["methods"],
                    function ($callback, $method) {
                        return null === $callback;
                    },
                    ARRAY_FILTER_USE_BOTH
                )
            );
            $methods_with_setted_cb = array_diff(
                array_keys($route_info["methods"]),
                $methods_with_default_cb
            );

            if (false === empty($methods_with_default_cb)) {
                $controllers->match($route, $route_info["callback"])
                    ->method(strtoupper(implode("|", $methods_with_default_cb)));
            }
            array_map(
                function ($method) use ($controllers, $route, $route_info) {
                    $controllers->{$method}($route, $route_info["methods"][$method]);
                },
                $methods_with_setted_cb
            );
        }
        return $controllers;
    }

    public static function doProxy(Application $app, Request $req)
    {
        try {
            $response = $app["conversation_proxy"]
                ->{$req->getMethod()}("{$req->getRequestUri()}")
                ->addCookie('authenticator', $req->cookies->get("authenticator"))
                ->send();
            $headers = $response->getHeaders()->toArray();
            return $app->json(json_decode($response->getBody()), 200, $headers);
        } catch (\Exception $exception) {
            return $app->abort($exception->getResponse()->getStatusCode(), $exception->getResponse()->getReasonPhrase());
        }
    }
}
