<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class getRabbitController extends Controller {

    private ?AMQPStreamConnection $connect = null;

    private function connection(){
        if (!$this->connect) {
            $this->connect = new AMQPStreamConnection(
                env('RABBIT_HOST'),
                env('RABBIT_PORT'),
                env('RABBIT_USER'),
                env('RABBIT_PASS')
            );
        }
        return $this->connect;
    }

    private function channel() : ? object {
        return $this->connection()->channel();
    }

    private function queueDeclare($channel, string $queueName) : void {
        $channel->queue_declare($queueName, false, true, false, false);
    }

    function callback($msg){
        dd($msg);
        $data = json_decode($msg->body, true);
        echo "Received message: " . print_r($data, true) . "\n";
    }

    public function consumeDataOnRabbit() : void {
        $channel = $this->channel();

        $queueName = 'simple_queue';
        $this->queueDeclare($channel, $queueName);

        $channel->basic_consume($queueName, '', false, false, false, false, [$this, 'callback']);

        $channel->close();
        $this->connect->close();
    }
}
