<?php

namespace Ruwler\Model;

/**
 * Class ApiResponse
 * @package Ruwler\Model
 */
class ApiResponse
{
    /**
     * @var int
     */
    private $code;

    /**
     * @var array
     */
    private $data;

    public function __construct($code, $data)
    {
        $this->code = $code;
        $this->data = $data;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function getData(): ?array
    {
        return $this->data;
    }
}