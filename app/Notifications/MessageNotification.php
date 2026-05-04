<?php

namespace App\Notifications;

use App\Models\Chat\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class MessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Message $message
    ) {
        Log::info("Chamando para a notificação");
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }
    public function broadcastType(): string
    {
        return 'message.notification';
    }
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        Log::info("batendo aqui dentro do toBroadcast");
        $senderName = $this->message->sender_name;
        $preview = strlen($this->message->content) > 50
            ? substr($this->message->content, 0, 50) . '...'
            : $this->message->content;

        if ($this->message->isGroupMessage()) {
            return new BroadcastMessage([
                'type' => 'new_message',
                'title' => 'Nova mensagem na turma',
                'message' => "{$senderName}: {$preview}",
                'classe_id' => $this->message->classe_id,
                'message_id' => $this->message->id,
                'sender_id' => $this->message->sender_id,
                'sender_type' => $this->message->sender_type,
            ]);
        }
        return new BroadcastMessage([
            'type' => 'new_message',
            'title' => 'Nova mensagem',
            'message' => "{$senderName}: {$preview}",
            'classe_id' => $this->message->classe_id,
            'message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'sender_type' => $this->message->sender_type,
        ]);
    }
}
