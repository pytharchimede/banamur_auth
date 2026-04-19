<?php

require_once __DIR__ . '/../model/ApiException.php';
require_once __DIR__ . '/../service/AuthService.php';
require_once __DIR__ . '/../model/ApiRequest.php';

class AuthController
{
    private $authService;

    public function __construct()
    {
        $this->authService = new \AuthService();
    }

    public function register(\ApiRequest $request)
    {
        return [
            'status' => 201,
            'body' => $this->authService->register($request->getBody(), $request->getContext()),
        ];
    }

    public function login(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->authService->login($request->getBody(), $request->getContext()),
        ];
    }

    public function antiBotChallenge(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->authService->issueAntiBotChallenge($request->getContext()),
        ];
    }

    public function logout(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->authService->logout($request->getBearerToken(), $request->getContext()),
        ];
    }

    public function me(\ApiRequest $request)
    {
        $identity = $request->getAuthenticatedIdentity();
        if ($identity === null) {
            $identity = $this->authService->authenticateRequest($request);
        }

        return [
            'status' => 200,
            'body' => $this->authService->formatIdentityResponse($identity),
        ];
    }

    public function health()
    {
        return [
            'status' => 200,
            'body' => [
                'message' => 'API Banamur Auth disponible.',
            ],
        ];
    }
}
