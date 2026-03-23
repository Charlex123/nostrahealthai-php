<?php

namespace NostraHealthAI;

use Exception;

/**
 * NostraHealthAI API Exception
 */
class NostraHealthAIException extends Exception
{
    /** @var int|null */
    private $statusCode;

    /** @var array|null */
    private $response;

    /**
     * Create a new exception
     *
     * @param string $message Error message
     * @param int|null $statusCode HTTP status code
     * @param array|null $response API response data
     */
    public function __construct(string $message, ?int $statusCode = null, ?array $response = null)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->response = $response;
    }

    /**
     * Get HTTP status code
     *
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Get API response data
     *
     * @return array|null
     */
    public function getResponse(): ?array
    {
        return $this->response;
    }
}
