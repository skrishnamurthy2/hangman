<?php

namespace Hangman\Message;

use JsonSerializable;

class GameStateMessage implements JsonSerializable
{
    /** @var string */
    private $gameId;
    /** @var int */
    private $remaining;
    /** @var array */
    private $wrongGuesses;
    /** @var array */
    private $guessWord;
    /** @var string  */
    private $word;
    /** @var int */
    private $version;

    public function __construct(string $gameId, int $remaining, array $wrongGuesses, string $word, array $guessWord, int $version)
    {
        $this->gameId = $gameId;
        $this->remaining = $remaining;
        $this->wrongGuesses = $wrongGuesses;
        $this->word = $word;
        $this->guessWord = $guessWord;
        $this->version = $version;
    }

    public function getRemaining(): int
    {
        return $this->remaining;
    }

    public function getWrongGuesses(): array
    {
        return $this->wrongGuesses;
    }

    public function getWord(): string
    {
        return $this->word;
    }

    public function getGuessWord(): array
    {
        return $this->guessWord;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function jsonSerialize(): array
    {
        return [
            'gameId' => $this->gameId,
            'remaining' => $this->remaining,
            'wrongGuesses' => $this->wrongGuesses,
            'guessWord' => $this->guessWord,
            'version' => $this->version,
        ];
    }
}