<?php
// app/Events/Chat/MessageSent.php

namespace App\Events\Chat;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\{ShouldBroadcast, ShouldBroadcastNow};
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Chat\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Log;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message
    ) {}

    /**
     * Canal de broadcasting
     */
    public function broadcastOn(): array
    {
        // Se for mensagem privada, broadcast nos dois canais (sender e receiver)
        if ($this->message->isPrivate()) {
            return [
                new PrivateChannel('chat-teacher-student.' . $this->message->receiver_id),
                new PrivateChannel('chat-teacher-student.' . $this->message->sender_id),
            ];
        }

        // Se for mensagem para turma, broadcast no canal da turma
        return [
            new PresenceChannel('chat.classe.' . $this->message->classe_id)
        ];
    }

    /**
     * Nome do evento no cliente
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Dados enviados
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'classe_id' => $this->message->classe_id,
            'sender_id' => $this->message->sender_id,
            'sender_type' => $this->message->sender_type,
            'sender_name' => $this->message->sender_name,
            'receiver_id' => $this->message->receiver_id,
            'receiver_type' => $this->message->receiver_type,
            'receiver_name' => $this->message->receiver_name,
            'content' => $this->message->content,
            'is_read' => $this->message->is_read,
            'created_at' => $this->message->created_at->toISOString(),
            'is_private' => $this->message->isPrivate(),
        ];
    }
}
