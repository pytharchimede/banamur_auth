<?php

class Permission
{
    private $id;
    private $name;
    private $code;
    private $description;
    private $module;
    private $createdAt;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->code = $data['code'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->module = $data['module'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
    }

    public static function fromArray(array $data)
    {
        return new self($data);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function toSafeArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'module' => $this->module,
            'created_at' => $this->createdAt,
        ];
    }
}
