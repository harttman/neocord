<?php

require 'vendor/autoload.php';

use Discord\Discord;

$intents = 513;

$client = new Discord('MTI0OTEyMDM2NjQyOTg2NDA1OQ.G3IC8T.ks2xQM91LM_HEpO9vSL3cLccIXgqiHSeG9mNEM', $intents);

$client->on('MESSAGE_CREATE', function($msg) use ($client) {
    echo "Message from {$msg->author}: {$msg->content}" . PHP_EOL;
    if($msg->content == "hello") {
      $client->replyToMessage($msg->channel_id, $msg->id, "Hello, {$msg->author->username}!");
    }
});

$client->run();
