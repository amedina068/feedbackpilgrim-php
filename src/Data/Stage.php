<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Data;

/**
 * A column of the post pipeline.
 */
final readonly class Stage
{
    /**
     * @param  string  $type  One of `open`, `planned`, `active`, `complete` or `closed`.
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $type,
        public ?string $slug = null,
        public ?string $color = null,
        public ?int $position = null,
        public bool $showOnRoadmap = false,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $payload = Payload::of($data);

        return new self(
            id: $payload->int('id'),
            name: $payload->string('name'),
            type: $payload->string('type'),
            slug: $payload->nullableString('slug'),
            color: $payload->nullableString('color'),
            position: $payload->nullableInt('position'),
            showOnRoadmap: $payload->bool('show_on_roadmap'),
        );
    }

    public function isComplete(): bool
    {
        return $this->type === 'complete';
    }
}
