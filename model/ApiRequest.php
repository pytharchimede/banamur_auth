<?php

require_once __DIR__ . '/ApiException.php';

use ApiException;

class ApiRequest
{
    private $method;
    private $path;
    private $body;
    private $query;
    private $headers;
    private $context;
    private $routeParams = [];
    private $authenticatedIdentity = null;

    public function __construct($method, $path, array $body, array $query, array $headers, array $context)
    {
        $this->method = strtoupper((string) $method);
        $this->path = $path;
        $this->body = $body;
        $this->query = $query;
        $this->headers = $headers;
        $this->context = $context;
    }

    public static function fromGlobals($path)
    {
        $rawBody = file_get_contents('php://input');
        $body = [];

        if ($rawBody !== false && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (!is_array($decoded)) {
                throw new ApiException('Le corps JSON est invalide.', 400, 'invalid_json');
            }

            $body = $decoded;
        }

        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $path,
            $body,
            $_GET,
            self::extractHeaders(),
            [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]
        );
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getBodyValue($key, $default = null)
    {
        return array_key_exists($key, $this->body) ? $this->body[$key] : $default;
    }

    public function getQueryParam($key, $default = null)
    {
        return array_key_exists($key, $this->query) ? $this->query[$key] : $default;
    }

    public function getQueryParams()
    {
        return $this->query;
    }

    public function setRouteParams(array $routeParams)
    {
        $this->routeParams = $routeParams;
    }

    public function getRouteParam($name, $default = null)
    {
        return array_key_exists($name, $this->routeParams) ? $this->routeParams[$name] : $default;
    }

    public function getRouteParams()
    {
        return $this->routeParams;
    }

    public function getHeader($name, $default = null)
    {
        $normalized = strtolower($name);
        return array_key_exists($normalized, $this->headers) ? $this->headers[$normalized] : $default;
    }

    public function getBearerToken()
    {
        $header = $this->getHeader('authorization', '');
        if (!is_string($header) || stripos($header, 'Bearer ') !== 0) {
            throw new ApiException('Header Authorization Bearer requis.', 401, 'missing_authorization_header');
        }

        return trim(substr($header, 7));
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setAuthenticatedIdentity(array $identity)
    {
        $this->authenticatedIdentity = $identity;
    }

    public function getAuthenticatedIdentity()
    {
        return $this->authenticatedIdentity;
    }

    private static function extractHeaders()
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (strpos($key, 'HTTP_') === 0) {
                $headerName = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$headerName] = $value;
            }
        }

        if (isset($_SERVER['Authorization']) && is_string($_SERVER['Authorization'])) {
            $headers['authorization'] = $_SERVER['Authorization'];
        }

        return $headers;
    }
}
