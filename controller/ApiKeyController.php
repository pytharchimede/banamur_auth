<?php

require_once __DIR__ . '/../model/ApiRequest.php';
require_once __DIR__ . '/../service/ApiKeyService.php';

class ApiKeyController
{
    private $apiKeyService;

    public function __construct()
    {
        $this->apiKeyService = new \ApiKeyService();
    }

    public function index(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->apiKeyService->listKeys([
                'user_id' => $request->getQueryParam('user_id'),
                'include_revoked' => $request->getQueryParam('include_revoked'),
                'include_expired' => $request->getQueryParam('include_expired'),
            ]),
        ];
    }

    public function store(\ApiRequest $request)
    {
        return [
            'status' => 201,
            'body' => $this->apiKeyService->createKey(
                $request->getBody(),
                $request->getAuthenticatedIdentity(),
                $request->getContext()
            ),
        ];
    }

    public function destroy(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->apiKeyService->revokeKey(
                $request->getRouteParam('id'),
                $request->getAuthenticatedIdentity(),
                $request->getContext()
            ),
        ];
    }
}
