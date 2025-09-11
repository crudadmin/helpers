<?php

namespace AdminHelpers\Importer\Concerns;

use Throwable;

trait HasImportLogs
{
    public function logReport($type, $code, $message = null, $log = null)
    {
        $row = $this->logs()->create([
            'type' => $type,
            'code' => $code,
            'message' => $this->toLogResponse($message),
            'log' => $this->toLogResponse($log),
        ]);

        return $row;
    }

    public function logException(Throwable $e)
    {
        $code = $e->getCode();

        $message = $e->getMessage();

        $log = $e->getTraceAsString();

        return $this->logReport('error', $code, $message ?? null, $log ?? null);
    }

    private function toLogResponse($response)
    {
        if ( is_array($response) ){
            return json_encode($response, JSON_PRETTY_PRINT);
        }

        return $response;
    }
}