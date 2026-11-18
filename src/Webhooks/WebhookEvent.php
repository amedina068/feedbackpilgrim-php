<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Webhooks;

/**
 * A verified webhook delivery.
 *
 * The body is `{"event": "...", "data": {...}}`. For the post events, `data`
 * has `id`, `title`, `content`, `status`, `board_id`, `contact_id` and
 * `stage` (`{id, name, type}` or null).
 */
final readonly class WebhookEvent
{
    public const POST_CREATED = 'post.created';

    public const POST_STATUS_CHANGED = 'post.status_changed';

    public const POST_COMPLETED = 'post.completed';

    /**
     * @param  string  $type  The event type, for example `post.created`.
     * @param  array<array-key, mixed>  $data  The `data` object of the body.
     * @param  string|null  $deliveryId  The UUID of the delivery. Each retry sends the same UUID. Use it to ignore a delivery that you received before.
     * @param  int  $timestamp  The Unix time of this attempt.
     * @param  array<array-key, mixed>  $payload  The decoded JSON body.
     */
    public function __construct(
        public string $type,
        public array $data,
        public ?string $deliveryId,
        public int $timestamp,
        public array $payload,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromDelivery(array $payload, int $timestamp, ?string $deliveryId = null, ?string $eventHeader = null): self
    {
        $type = $payload['event'] ?? $eventHeader;

        return new self(
            type: is_string($type) ? $type : '',
            data: is_array($payload['data'] ?? null) ? $payload['data'] : [],
            deliveryId: $deliveryId,
            timestamp: $timestamp,
            payload: $payload,
        );
    }

    public function is(string $type): bool
    {
        return $this->type === $type;
    }
}
