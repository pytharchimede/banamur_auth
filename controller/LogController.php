<?php

require_once __DIR__ . '/../model/ApiRequest.php';
require_once __DIR__ . '/../service/LogService.php';

class LogController
{
    private $logService;

    public function __construct()
    {
        $this->logService = new \LogService();
    }

    public function index(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->logService->listLogs([
                'search' => $request->getQueryParam('search'),
                'event_type' => $request->getQueryParam('event_type'),
                'page' => $request->getQueryParam('page'),
                'per_page' => $request->getQueryParam('per_page'),
                'limit' => $request->getQueryParam('limit'),
            ]),
        ];
    }
}
