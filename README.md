# Hangman API

Description on the hangman API written using PHP and Slim Framework. Uses DynamoDB for persisting new games and local file system where all the words are stored.

Each Hangman game instance is associated with a gameId. Multiple players can use the same gameId to collaborate on the same game instance.

Each game also has a version field which will be incremented on each guess. To make an update to the game by guessing, a layer should pass gameId and latest version value.

# Endpoints

This service has following 3 endpoints

 1. Create or start a new game

**Request**
*POST* /game
Body: None

**Response**

HTTP 200 - Game started ok, and format of the sample json returned will be like below
```json
{
    "gameId": "94a079fc-3d5b-420a-b2a4-b0c4ec1e16ac",
    "remaining": 10,
    "wrongGuesses": [],
    "guessWord": ["_", "_", "_", "_", "_", "_", "_", "_", "_", "_", "_"],
    "version": 1
}
```

HTTP 500 - Internal server error, mostly happens if the server is not able to retrieve the words list or unable to persist the new game into the database

 2. Get data about a game which is either complete or in-progress

**Request**
GET /game/{gameId}

Response

 HTTP 200 - Game with that id exists and format of the json returned will be same as above
 HTTP 404 - Game with the given id does not exists
 HTTP 500 - Something unexpected happened during the retrieval of the game

 3. Guess a letter in the game

Request
POST /game/{gameId}
BODY
```json
{
    "version": 1,
    "guessLetter": "A"
}
```
Above is an example where we guessing the letter 'p', with latest version value passed through

Response

HTTP 200 - Either the guess was wrong or it was the right guess. If it was the wrong guess, the new letter will be in wrongGuesses field otherwise the new letter will be in guessWord field
```json
{
    "gameId": "94a079fc-3d5b-420a-b2a4-b0c4ec1e16ac",
    "remaining": 10,
    "wrongGuesses": [],
    "guessWord": ["A", "_", "_", "_", "_", "_", "A", "_", "_", "_", "_"],
    "version": 2
}
```
Note: You will get updated version after this call, which should be used in subsequent POST call.

If this was a wrong guess, then the response will be like below
```json
{
    "gameId": "94a079fc-3d5b-420a-b2a4-b0c4ec1e16ac",
    "remaining": 9,
    "wrongGuesses": ["A"],
    "guessWord": ["_", "_", "_", "_", "_", "_", "_", "_", "_", "_", "_"],
    "version": 2
}
```
HTTP 404 - If the game id is not valid
HTTP 409 - This error code is returned when version value passed does not match the latest game. Usually this means caller has stale data
HTTP 400 - This error code is returned for multiple reasons as given below

		 1. Game Id / Version / Guess Letter is not passed
		 
		 2. Game already completed with either successfully guessing the word or reached maximum unsuccessful attempts
		 
		 3. Passing already guessed valid or invalid letter 
		 
The body of this error code in the error field gives the detail on the above error
