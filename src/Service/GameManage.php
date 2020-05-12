<?php

namespace Hangman\Service;

use Hangman\Message\GameStateMessage;

interface GameManage
{
    public const NON_GUESSED_LETTER = '_';

    public function createGame(): GameStateMessage;
}