<?php

namespace Hangman\Persistence;

use Hangman\Message\GameStateMessage;

interface Persistence
{
    public const NOT_FOUND = 'Not Found';

    public function persistNewGame(string $newGameId, string $word, array $guessWord, int $maximumGuess): GameStateMessage;

    public function getGame(string $gameId): GameStateMessage;

    public function guess(string $gameId, string $word, array $guessWord, int $oldVersion, int $newVersion, int $remaining, array $wrongGuesses): GameStateMessage;

}