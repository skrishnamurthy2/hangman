<?php

namespace Hangman\Service;

use Hangman\Message\GameStateMessage;

interface Game
{
    public function getGame(string $gameId): GameStateMessage;

    public function guess (string $gameId, int $version, string $letter): GameStateMessage;
}