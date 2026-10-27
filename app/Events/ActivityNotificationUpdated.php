<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityNotificationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $notifiableId,
        public string $action,
        public ?string $activityId = null,
        public ?string $type = null,
        public ?string $notificationId = null,
        public ?string $dedupeKey = null,
        public ?array $toast = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('notifications.'.$this->notifiableId)];
    }

    public function broadcastAs(): string
    {
        return 'activity.notification.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'activity_id' => $this->activityId,
            'type' => $this->type,
            'notification_id' => $this->notificationId,
            'dedupe_key' => $this->dedupeKey,
            'toast' => $this->toast,
        ];
    }
}
