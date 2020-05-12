<?php

namespace Hangman\Service;

use Exception;
use Hangman\Exception\HangmanPersistenceException;
use Hangman\Exception\HangmanWordRetrievalException;
use Hangman\Message\GameStateMessage;
use Hangman\Persistence\Persistence;
use Hangman\Persistence\WordCollection;
use Ramsey\Uuid\Uuid;

class GameManageService implements GameManage
{
    private const MAXIMUM_GUESSES = 10;

    /** @var Persistence */
    private $persistence;
    /** @var WordCollection */
    private $wordRetriever;

    public function __construct(Persistence $persistence, WordCollection $wordRetriever)
    {
        $this->persistence   = $persistence;
        $this->wordRetriever = $wordRetriever;
    }

    /**
     * @return GameStateMessage
     * @throws HangmanPersistenceException
     * @throws HangmanWordRetrievalException
     */
    public function createGame(): GameStateMessage
    {
        $newGameId = $this->getGameId();
        $word = $this->getWordToGuess();

        try
        {
            return $this->persistence->persistNewGame($newGameId, $word, $this->getGuessWord($word), self::MAXIMUM_GUESSES);
        }
        catch (Exception $ex)
        {
            throw new HangmanPersistenceException();
        }
    }

    private function getGameId(): string
    {
        return (string) Uuid::uuid4();
    }

    /**
     * @return string
     * @throws HangmanWordRetrievalException
     */
    private function getWordToGuess(): string
    {
        try
        {
            return $this->wordRetriever->getWord();
        }
        catch (Exception $ex)
        {
            throw new HangmanWordRetrievalException();
        }
    }

    private function getGuessWord(string $word): array
    {
        return array_fill(0, strlen($word), GameManage::NON_GUESSED_LETTER);
    }
}