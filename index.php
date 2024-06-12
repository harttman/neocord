<?php

require 'vendor/autoload.php';

use Discord\Discord;

$intents = 513;

$client = new Discord('token', $intents);

$client->on('MESSAGE_CREATE', function($msg) use ($client) {
    if($msg->author->bot) return;
    
    if($msg->content == "!ping") {
      $client->replyToMessage($msg->channel_id, $msg->id, "Hello, {$msg->author->username}!");
    }
});

$client->run();
