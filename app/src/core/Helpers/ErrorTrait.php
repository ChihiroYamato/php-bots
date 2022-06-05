<?php

namespace Anet\App\Helpers;

/**
 * **ErrorTrait** -- contains any methods for storage and increment project errors
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
 */
trait ErrorTrait
{
    /**
     * @var int $errorCount current count of errors
     */
    private int $errorCount = 0;
    /**
     * @var string[][] $errors storage of current errors
     */
    private array $errors = [];

    /**
     * Method return all stored errors
     * @return string[][] all stored errors
     */
    protected function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * Method return all stored error categories
     * @return string[] error categories
     */
    protected function getErrorsCategory() : array
    {
        return array_keys($this->errors);
    }

    /**
     * Method return stored errors count
     * @return int errors count
     */
    protected function getErrorCount() : int
    {
        return $this->errorCount;
    }

    /**
     * Method add an error to storage by category
     * @param string $category category of error
     * @param string $message message of error
     * @return void
     */
    protected function addError(string $category, string $message) : void
    {
        $this->errors[] = [
            'category' => $category,
            'time' => (new \DateTime())->format('H:i:s'),
            'message' => $message,
        ];
        $this->errorCount++;
    }
}
