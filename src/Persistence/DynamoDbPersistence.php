<?php

namespace Hangman\Persistence;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use Exception;
use Hangman\Message\GameStateMessage;

class DynamoDbPersistence implements Persistence
{
    /** @var DynamoDbClient */
    private $dynamodbClient;
    /** @var string */
    private $tableName;
    /** @var Marshaler */
    private $marshaler;

    public function __construct(DynamoDbClient $dynamodbClient, string $tableName)
    {
        $this->dynamodbClient = $dynamodbClient;
        $this->tableName = $tableName;
        $this->marshaler = New Marshaler();
    }

    public function persistNewGame(string $newGameId, string $word, array $guessWord, int $maximumGuess): GameStateMessage
    {
        try
        {
            $this->dynamodbClient->putItem(
                [
                    'TableName' => $this->tableName,
                    'Item' => $this->marshaler->marshalItem(
                        [
                            'gameId' => $newGameId,
                            'remaining' => $maximumGuess,
                            'wrongGuesses' => [],
                            'word' => $word,
                            'guessWord' => $guessWord,
                            'version' => 1,
                        ]
                    ),
                ]
            );
        }
        catch (DynamoDbException $ex)
        {
            throw new Exception($ex->getMessage(), 0, $ex);
        }

        return new GameStateMessage(
            $newGameId,
            $maximumGuess,
            [],
            $word,
            $guessWord,
            1
        );
    }

    /**
     * @param string $gameId
     * @return GameStateMessage
     * @throws Exception
     */
    public function getGame(string $gameId): GameStateMessage
    {
        try
        {
            $result = $this->dynamodbClient->getItem(
                [
                    'TableName' => $this->tableName,
                    'Key' => $this->marshaler->marshalItem(
                        [
                            'gameId' => $gameId,
                        ]
                    ),
                ]
            );

            if (!$result || !$result['Item'])
            {
                throw new Exception(Persistence::NOT_FOUND);
            }

            return $this->parseDynamoDbRow(
                $this->marshaler->unmarshalItem($result['Item'])
            );
        }
        catch (DynamoDbException $ex)
        {
            throw new Exception($ex->getMessage(), 0, $ex);
        }
    }

    public function guess (string $gameId, string $word, array $guessWord, int $oldVersion, int $newVersion, int $remaining, array $wrongGuesses): GameStateMessage
    {
        try
        {
            $this->dynamodbClient->updateItem(
                [
                    'TableName' => $this->tableName,
                    'Key' => ['gameId' => ['S' => $gameId]],
                    'UpdateExpression' => 'SET guessWord = :guessWord, version = :version, remaining = :remaining, wrongGuesses = :wrongGuesses',
                    'ConditionExpression' => '#v = :oldVersion',
                    'ExpressionAttributeNames' => ['#v' => 'version'],
                    'ExpressionAttributeValues' => [
                        ':version' => ["N" => "$newVersion"],
                        ':oldVersion' => ["N" => "$oldVersion"],
                        ':guessWord' => ["L" => $this->marshal($guessWord)],
                        ':remaining' => ["N" => "$remaining"],
                        ':wrongGuesses' => ["L" => $this->marshal($wrongGuesses)],
                    ]
                ]
            );
        }
        catch (DynamoDbException $ex)
        {
            throw new Exception($ex->getMessage(), 0, $ex);
        }

        return new GameStateMessage(
            $gameId,
            $remaining,
            $wrongGuesses,
            $word,
            $guessWord,
            $newVersion
        );
    }

    private function parseDynamoDbRow(array $item): GameStateMessage
    {
        return new GameStateMessage(
            $item['gameId'],
            $item['remaining'],
            $item['wrongGuesses'],
            $item['word'],
            $item['guessWord'],
            $item['version']
        );
    }

    private function marshal(array $guessWord): array
    {
        $marshalled = [];

        for ($index = 0; $index < count($guessWord); $index++)
        {
            $marshalled[] = ["S" => $guessWord[$index]];
        }

        return $marshalled;
    }
}