INTSALL:
```
composer require harttman/neocord
```
Why? idk.

docs? Writing :0

```php
<?php
require 'vendor/autoload.php';

use Discord\Discord;

$discord = new Discord('token', 123);
//token and intents
$discord->on('MESSAGE_CREATE', function($msg) use ($discord) {
  if($msg-author->bot) return;
  if($msg->content == "!ping") {
      $client->replyToMessage($msg->channel_id, $msg->id, "Hello, {$msg->author->username}!");
    }
});

$discord->run();
```
