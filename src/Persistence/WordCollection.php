<?php

namespace Hangman\Persistence;

interface WordCollection
{
    public function getWord(): string;
}