<?php

namespace Ruwler\Exception;

use Exception;

/**
 * Class RuwlerException
 * @package Ruwler\Exception
 */
class RuwlerException extends Exception
{
    /**
     * The complete error response
     * Contains error, error_description and error_uri.
     *
     * @var array
     */
    protected $body;

    /**
     * RuwlerException constructor.
     *
     * @param string $message
     * @param int    $code
     * @param null   $previous
     */
    public function __construct($message = '', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getBody(): ?array
    {
        return $this->body;
    }

    /**
     * @param array $body
     *
     * @return $this
     */
    public function setBody($body): self
    {
        $this->body = $body;

        return $this;
    }
}
