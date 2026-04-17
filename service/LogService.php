<?php

require_once __DIR__ . '/../repository/AuthLogRepository.php';

class LogService
{
    private $authLogRepository;

    public function __construct()
    {
        $this->authLogRepository = new \AuthLogRepository();
    }

    public function listLogs(array $filters = [])
    {
        if ($this->shouldUsePaginatedList($filters)) {
            $result = $this->authLogRepository->findPaginated($filters);

            return [
                'items' => $result['items'],
                'pagination' => $result['pagination'],
                'filters' => [
                    'search' => trim((string) ($filters['search'] ?? '')),
                    'event_type' => trim((string) ($filters['event_type'] ?? '')),
                ],
            ];
        }

        $limit = (int) ($filters['limit'] ?? 100);

        return $this->authLogRepository->findRecent($limit);
    }

    private function shouldUsePaginatedList(array $filters)
    {
        foreach (['search', 'event_type', 'page', 'per_page'] as $key) {
            if (array_key_exists($key, $filters) && $filters[$key] !== null && $filters[$key] !== '') {
                return true;
            }
        }

        return false;
    }
}
