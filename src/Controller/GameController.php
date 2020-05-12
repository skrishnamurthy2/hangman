<?php

namespace Hangman\Controller;

use Hangman\Exception\HangmanConflictException;
use Hangman\Exception\HangmanNotAllowed;
use Hangman\Exception\HangmanNotFoundException;
use Hangman\Exception\HangmanPersistenceException;
use Hangman\Service\Game;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use function GuzzleHttp\Psr7\stream_for;

class GameController
{
    /** @var Game */
    private $game;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(Game $game, LoggerInterface $logger)
    {
        $this->game   = $game;
        $this->logger = $logger;
    }

    public function getGame(Request $request, Response $response, $args)
    {
        if (!$this->getGameId($request))
        {
            return $this->invalidFieldResponse('Game Id', $response);
        }

        try
        {
            $gameStateMessage = $this->game->getGame($this->getGameId($request));
            return $response
                ->withStatus(200)
                ->withBody(stream_for(json_encode($gameStateMessage)))
                ->withHeader('Content-type', 'application/json');
        }
        catch (HangmanNotFoundException $ex)
        {
            return $response->withStatus(404);
        }
        catch (HangmanPersistenceException $ex)
        {
            $this->logger->warning(
                'GameRetrievalFailed',
                [
                    'gameId' => $this->getGameId($request),
                    'Exception' => $ex,
                ]
            );
        }

        return $response->withStatus(500);
    }

    public function guess(Request $request, Response $response, $args)
    {
        if (!$this->getGameId($request))
        {
            return $this->invalidFieldResponse('Game Id', $response);
        }

        if (!$this->getGuessLetter($request))
        {
            return $this->invalidFieldResponse('Guessed Letter', $response);
        }

        if (!$this->getVersion($request))
        {
            return $this->invalidFieldResponse('Version value', $response);
        }

        try
        {
            $gameUpdatedMessage = $this->game->guess($this->getGameId($request), $this->getVersion($request), $this->getGuessLetter($request));
            return $response
                ->withStatus(200)
                ->withBody(stream_for(json_encode($gameUpdatedMessage)))
                ->withHeader('Content-type', 'application/json');
        }
        catch (HangmanNotFoundException $ex)
        {
            return $response->withStatus(404);
        }
        catch (HangmanNotAllowed $ex)
        {
            return $response
                ->withStatus(400)
                ->withBody(stream_for(json_encode(['error' => $ex->getMessage()])))
                ->withHeader('Content-type', 'application/json');
        }
        catch (HangmanConflictException $ex)
        {
            return $response
                ->withStatus(409)
                ->withBody(stream_for(json_encode(['error' => 'Version mismatch'])))
                ->withHeader('Content-type', 'application/json');
        }
        catch (HangmanPersistenceException $ex)
        {
            $this->logger->warning(
                'GameGuessFailed',
                [
                    'gameId' => $this->getGameId($request),
                    'Exception' => $ex,
                ]
            );
        }

        return $response->withStatus(500);
    }

    private function getGameId(Request $request): ?string
    {
        return $request->getAttribute('gameId') ?? null;
    }

    private function getVersion(Request $request): ?string
    {
        $body = json_decode($request->getBody(), true);
        return $body['version'] ?? null;
    }

    private function getGuessLetter(Request $request): ?string
    {
        $body = json_decode($request->getBody(), true);

        if (!$body['guessLetter'] || strlen($body['guessLetter']) > 1 )
        {
            return null;
        }

        return $body['guessLetter'];
    }

    private function invalidFieldResponse(string $field, Response $response): Response
    {
        return $response
            ->withStatus(400)
            ->withBody(
                stream_for(
                    json_encode(
                        [
                            'error' => "Invalid ${$field}"
                        ]
                    )
                )
            )
            ->withHeader('Content-type', 'application/json');
    }
}