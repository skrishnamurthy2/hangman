<?php

namespace Hangman\Service;

use Exception;
use Hangman\Exception\HangmanConflictException;
use Hangman\Exception\HangmanNotAllowed;
use Hangman\Exception\HangmanNotFoundException;
use Hangman\Exception\HangmanPersistenceException;
use Hangman\Message\GameStateMessage;
use Hangman\Persistence\Persistence;

class GameService implements Game
{
    /** @var Persistence */
    private $persistence;

    public function __construct(Persistence $persistence)
    {
        $this->persistence = $persistence;
    }

    /**
     * @param string $gameId
     * @return GameStateMessage
     * @throws HangmanNotFoundException
     * @throws HangmanPersistenceException
     */
    public function getGame(string $gameId): GameStateMessage
    {
        try
        {
            return $this->persistence->getGame($gameId);
        }
        catch (Exception $ex)
        {
            if ($ex->getMessage() === Persistence::NOT_FOUND)
            {
                throw new HangmanNotFoundException();
            }
            throw new HangmanPersistenceException();
        }
    }

    /**
     * @param string $gameId
     * @param int $version
     * @param string $letter
     * @return GameStateMessage
     * @throws HangmanConflictException
     * @throws HangmanNotAllowed
     * @throws HangmanNotFoundException
     * @throws HangmanPersistenceException
     */
    public function guess(string $gameId, int $version, string $letter): GameStateMessage
    {
        $gameStateMessage = $this->getGame($gameId);

        if (!$this->isGameUpdateVersionMatch($gameStateMessage, $version))
        {
            throw new HangmanConflictException();
        }

        if ($this->isGameCompleted($gameStateMessage))
        {
            throw new HangmanNotAllowed('Game completed');
        }

        if (!$this->isGameUpdateValid($gameStateMessage))
        {
            throw new HangmanNotAllowed('No more guess left');
        }

        if ($this->isAlreadyGuessed($gameStateMessage->getGuessWord(), $letter))
        {
            throw new HangmanNotAllowed('Already guessed letter');
        }

        if ($this->isAlreadyGuessed($gameStateMessage->getWrongGuesses(), $letter))
        {
            throw new HangmanNotAllowed('Already guessed as wrong letter');
        }

        try
        {
            if ($this->isGuessCorrect($gameStateMessage->getWord(), $letter))
            {
                $updatedGuess = $this->updateGuessWordWithLetter($gameStateMessage->getGuessWord(), $gameStateMessage->getWord(), $letter);

                return $this->persistence->guess($gameId, $gameStateMessage->getWord(), $updatedGuess, $gameStateMessage->getVersion(),
                    $gameStateMessage->getVersion() + 1, $gameStateMessage->getRemaining(), $gameStateMessage->getWrongGuesses());
            }
            else
            {
                $wrongGuesses = array_merge($gameStateMessage->getWrongGuesses(), [$letter]);
                return $this->persistence->guess($gameId, $gameStateMessage->getWord(), $gameStateMessage->getGuessWord(), $gameStateMessage->getVersion(),
                    $gameStateMessage->getVersion() + 1, $gameStateMessage->getRemaining() - 1, $wrongGuesses);
            }
        }
        catch (Exception $ex)
        {
            if (strstr($ex->getMessage(), 'Conflict'))
            {
                throw new HangmanConflictException();
            }
            throw new HangmanPersistenceException();
        }
    }

    private function isGameCompleted(GameStateMessage $gameStateMessage): bool
    {
        $fullWordGuessed = true;

        $guessWord = $gameStateMessage->getGuessWord();

        for($index = 0; $index < count($guessWord); $index++)
        {
            if ($guessWord[$index] === GameManage::NON_GUESSED_LETTER)
            {
                $fullWordGuessed = false;
                break;
            }
        }

        return $fullWordGuessed ;
    }

    private function isGameUpdateValid(GameStateMessage $gameStateMessage): bool
    {
        return $gameStateMessage->getRemaining() !== 0;
    }

    private function isGameUpdateVersionMatch(GameStateMessage $gameStateMessage, int $version): bool
    {
        return $gameStateMessage->getVersion() === $version;
    }

    private function isGuessCorrect(string $word, string $letter): bool
    {
        for ($index = 0; $index < strlen($word); $index++)
        {
            if ($word[$index] === $letter)
            {
                return true;
            }
        }

        return false;
    }

    private function isAlreadyGuessed(array $guessWord, string $letter): bool
    {
        for ($index = 0; $index < count($guessWord); $index++)
        {
            if ($guessWord[$index] === $letter)
            {
                return true;
            }
        }

        return false;
    }

    private function updateGuessWordWithLetter(array $guessWord, string $word, string $letter): array
    {
        for ($index = 0; $index < count($guessWord); $index++)
        {
            if ($guessWord[$index] == GameManage::NON_GUESSED_LETTER && $word[$index] == $letter)
            {
                $guessWord[$index] = $letter;
            }
        }

        return $guessWord;
    }

}