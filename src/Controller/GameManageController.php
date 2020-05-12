<?php

namespace Hangman\Controller;

use Hangman\Exception\HangmanPersistenceException;
use Hangman\Exception\HangmanWordRetrievalException;
use Hangman\Service\GameManage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use function GuzzleHttp\Psr7\stream_for;

class GameManageController
{
    /** @var GameManage */
    private $gameManager;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(GameManage $gameManager, LoggerInterface $logger)
    {
        $this->gameManager = $gameManager;
        $this->logger = $logger;
    }

    public function newGame(Request $request, Response $response, $args)
    {
        try
        {
            $gameCreatedMessage = $this->gameManager->createGame();
            return $response
                ->withStatus(200)
                ->withBody(stream_for(json_encode($gameCreatedMessage)))
                ->withHeader('Content-type', 'application/json');
        }
        catch (HangmanPersistenceException | HangmanWordRetrievalException $ex)
        {
            $this->logger->warning(
                'NewGameSetupFailed',
                [
                    'Exception' => $ex,
                ]
            );
        }

        return $response->withStatus(500);
    }
}