<?php

namespace Discord;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Ratchet\Client\WebSocket;
use Ratchet\Client\Connector;
use React\EventLoop\Factory;

class Discord
{
    private $token;
    private $intents;
    private $httpClient;
    private $eventHandlers = [];
    private $gatewayUrl = 'wss://gateway.discord.gg/?v=9&encoding=json';
    private $heartbeatInterval;

    public function __construct($token, $intents = 513)
    {
        $this->token = $token;
        $this->intents = $intents;
        $this->httpClient = new Client([
            'base_uri' => 'https://discord.com/api/',
            'headers' => [
                'Authorization' => 'Bot ' . $this->token,
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    public function on($event, callable $handler)
    {
        $this->eventHandlers[$event] = $handler;
    }

    public function run()
    {
        $loop = Factory::create();
        $connector = new Connector($loop);

        $connector($this->gatewayUrl)->then(function (WebSocket $conn) use ($loop) {
            $conn->on('message', function ($msg) use ($conn) {
                $data = json_decode($msg);
                switch ($data->op) {
                    case 10: // Hello
                        $this->heartbeatInterval = $data->d->heartbeat_interval / 1000;
                        $this->startHeartbeat($conn);
                        $this->identify($conn);
                        break;
                    case 0: // Dispatch
                        $event = $data->t;
                        if (isset($this->eventHandlers[$event])) {
                            $this->eventHandlers[$event]($data->d);
                        }
                        break;
                }
            });

            $conn->on('close', function ($code = null, $reason = null) use ($loop) {
                echo "Connection closed ({$code} - {$reason})\n";
                $loop->stop();
            });
        }, function ($e) use ($loop) {
            echo "Could not connect: {$e->getMessage()}\n";
            $loop->stop();
        });

        $loop->run();
    }

    private function startHeartbeat(WebSocket $conn)
    {
        $loop = Factory::create();
        $loop->addPeriodicTimer($this->heartbeatInterval, function () use ($conn) {
            $conn->send(json_encode(['op' => 1, 'd' => null]));
        });
    }

    private function identify(WebSocket $conn)
    {
        $payload = [
            'op' => 2,
            'd' => [
                'token' => $this->token,
                'intents' => $this->intents,
                'properties' => [
                    '$os' => php_uname('s'),
                    '$browser' => 'discord-php',
                    '$device' => 'discord-php'
                ]
            ]
        ];
        $conn->send(json_encode($payload));
    }

    public function sendRequest($method, $uri, $options = [])
    {
        try {
            $response = $this->httpClient->request($method, $uri, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \Exception('Request failed: ' . $e->getMessage());
        }
    }

    public function sendMessage($channelId, $content)
    {
        return $this->sendRequest('POST', "channels/{$channelId}/messages", [
            'json' => ['content' => $content]
        ]);
    }

    public function replyToMessage($channelId, $messageId, $content)
    {
        return $this->sendRequest('POST', "channels/{$channelId}/messages", [
            'json' => [
                'content' => $content,
                'message_reference' => [
                    'message_id' => $messageId
                ]
            ]
        ]);
    }
}
