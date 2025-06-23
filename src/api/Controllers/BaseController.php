<?php

namespace Palshin\PswCrack\Controllers;

class BaseController
{
    /**
     * Return JSON response with optional status code
     * 
     * @param mixed $data The data to be encoded as JSON
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        echo json_encode($data);
    }

    /**
     * Return JSON error response
     */
    protected function jsonError($message, $statusCode = 400)
    {
        $this->json(['error' => $message], $statusCode);
    }
}
