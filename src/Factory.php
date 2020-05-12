<?php

namespace Hangman;

use Aws\DynamoDb\DynamoDbClient;
use Hangman\Controller\GameController;
use Hangman\Controller\GameManageController;
use Hangman\Persistence\DynamoDbPersistence;
use Hangman\Persistence\LocalFileWordCollection;
use Hangman\Persistence\Persistence;
use Hangman\Persistence\WordCollection;
use Hangman\Service\Game;
use Hangman\Service\GameManage;
use Hangman\Service\GameManageService;
use Hangman\Service\GameService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Slim\App;
use Slim\Factory\AppFactory;

class Factory
{
    /** @var App */
    private $app;

    private $gameManagerController;
    private $gameController;
    private $wordCollection;
    private $persistence;
    private $gameManager;
    private $game;
    private $logger;

    public function __construct()
    {
    }

    public function getApp(): App
    {
        return $this->app ?: $this->app = AppFactory::create();
    }

    public function getRoutes(): void
    {
        $this->app->post('/game', [$this->getGameManagerController(), 'newGame']);

        $this->app->get('/game/{gameId}', [$this->getGameController(), 'getGame']);
        $this->app->post('/game/{gameId}', [$this->getGameController(), 'guess']);
    }

    private function getGameManagerController(): GameManageController
    {
        return $this->gameManagerController ?: $this->gameManagerController = new GameManageController(
            $this->getGameManager(),
            $this->getLogger()
        );
    }

    private function getGameController(): GameController
    {
        return $this->gameController ?: $this->gameController = new GameController(
            $this->getGame(),
            $this->getLogger()
        );
    }

    private function getGame(): Game
    {
        return $this->game ?: $this->game = new GameService(
            $this->getPersistence()
        );
    }

    private function getGameManager(): GameManage
    {
        return $this->gameManager ?: $this->gameManager = new GameManageService(
            $this->getPersistence(),
            $this->getWordCollection()
        );
    }

    private function getWordCollection(): WordCollection
    {
        return $this->wordCollection ?: $this->wordCollection = new LocalFileWordCollection(__DIR__ . '/../data/words.txt');
    }

    private function getPersistence(): Persistence
    {
        return $this->persistence ?: $this->persistence = new DynamoDbPersistence($this->getDynamoDbClient(), 'Hangman');
    }

    private function getDynamoDbClient(): DynamoDbClient
    {
        return new DynamoDbClient(
            [
                'region' => 'us-west-2',
                'version' => 'latest'
            ]
        );
    }

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?: $this->logger = new NullLogger();
    }
}
