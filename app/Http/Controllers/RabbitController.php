<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitController extends Controller {

    private ?AMQPStreamConnection $connect = null;

    private function connection() : ? object{
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

    private function channel() : ? object{
        return $this->connection()->channel();
    }

    private function queueDeclare($channel, string $queueName) : void {
        $channel->queue_declare($queueName, false, true, false, false);
    }

    private function data(){
        $data = [
            'name' => 'arash narimani',
            'email' => 'arash.developer1380@gmail.com',
            'age' => 23,
        ];

        return json_encode($data);
    }
    private function publish($channel, mixed $msg, string $queueName){
        $channel->basic_publish($msg, '', $queueName);
    }

    private function msg($data){
        return new AMQPMessage($data, [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);
    }

    public function sendDataToRabbit() : Response {
        try {
            $channel = $this->channel();

            $queueName = 'simple_queue';
            $this->queueDeclare($channel, $queueName);

           $data = $this->data();

            $this->publish($channel, $this->msg($data), $queueName);

            return response([
                'message' => "Channel created successfully and send data",
            ]);

            $channel->close();

            $this->connect->close();

        } catch (\Exception $e) {
            return response("Error: " . $e->getMessage(), 500);
        }
    }
}
