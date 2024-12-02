<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\RabbitController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/publish-rabbit', [RabbitController::class, 'sendDataToRabbit']);

//Route::get('/consume-rabbit', [getRabbitController::class, 'consumeDataOnRabbit']);
Route::get('/consume-rabbit', function (){
    $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
    $channel = $connection->channel();
    $queueName = 'simple_queue';
    $channel->queue_declare($queueName, false, true, false, false);

    echo " [*] Waiting for messages. To exit press CTRL+C\n";

    $callback = function ($msg) {
        echo " [x] Received JSON data: " . $msg->body . "\n";

        $data = json_decode($msg->body, true);
        if ($data) {
            echo " Name: " . $data['name'] . "\n";
            echo " Email: " . $data['email'] . "\n";
            echo " Age: " . $data['age'] . "\n";
        } else {
            echo " [!] Failed to decode JSON\n";
        }
    };

    $channel->basic_consume($queueName, '', false, true, false, false, $callback);

    try {
        $channel->wait();
    } catch (\Throwable $exception) {
        echo $exception->getMessage();
    }

    $channel->close();
    $connection->close();
});
