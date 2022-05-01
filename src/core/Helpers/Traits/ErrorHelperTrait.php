<?php

namespace Anet\App\Helpers\Traits;

trait ErrorHelperTrait
{
    private int $errorCount = 0;
    private array $errors = [];

    protected function getErrors() : array
    {
        return $this->errors;
    }

    protected function getErrorsCategory() : array
    {
        return array_keys($this->errors);
    }

    protected function getErrorCount() : int
    {
        return $this->errorCount;
    }

    protected function addError(string $category, string $message) : void
    {
        $this->errors[] = [
            'Category' => $category,
            'Message' => $message,
            'Time' => (new \DateTime())->format('H:i:s'),
        ];
        $this->errorCount++;
    }
}
