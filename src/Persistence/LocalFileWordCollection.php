<?php

namespace Hangman\Persistence;

use Exception;

class LocalFileWordCollection implements WordCollection
{
    /** @var array|false */
    private $words;

    public function __construct(string $fileLocation)
    {
        $this->words = file($fileLocation, FILE_IGNORE_NEW_LINES);

        if ($this->isWordListValid())
        {
            $this->dataCleanse();
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getWord(): string
    {
        if (!$this->isWordListValid())
        {
            throw new Exception('word list empty');
        }

        return $this->words[mt_rand(0, count($this->words) - 1)];
    }

    private function isWordListValid(): bool
    {
        return is_array($this->words) && count($this->words) > 0;
    }

    private function dataCleanse(): void
    {
        $this->words = array_map(function($value) {
            return trim($value);
        }, $this->words);
    }
}