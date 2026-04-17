<?php

class ApiRouter
{
    private $routes = [];

    public function add($method, $pattern, callable $handler)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(\ApiRequest $request)
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->getMethod()) {
                continue;
            }

            $params = $this->match($route['pattern'], $request->getPath());
            if ($params === null) {
                continue;
            }

            $request->setRouteParams($params);

            return call_user_func($route['handler'], $request);
        }

        throw new \ApiException('Route introuvable.', 404, 'route_not_found');
    }

    private function match($pattern, $path)
    {
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($matches) {
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $pattern);

        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
