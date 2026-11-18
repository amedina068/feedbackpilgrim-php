<?php

declare(strict_types=1);

namespace FeedbackPilgrim\Resources;

use DateTimeImmutable;
use DateTimeInterface;
use FeedbackPilgrim\Data\Page;
use FeedbackPilgrim\Data\Testimonial;
use Generator;

/**
 * The customer testimonials of the client.
 *
 * The API cannot delete a testimonial.
 */
final readonly class Testimonials extends Resource
{
    /**
     * One page of testimonials, with their contact, votes and comments.
     *
     * @param  bool  $approved  True gives the approved testimonials. False gives the pending testimonials.
     * @param  int|null  $rating  1 to 5.
     * @param  string|null  $sortBy  `created_at` (default), `updated_at`, `rating` or `approved_at`.
     * @param  string|null  $sortDirection  `asc` or `desc` (default).
     * @param  int|null  $perPage  1 to 100. The default is 15.
     * @return Page<Testimonial>
     */
    public function list(
        bool $approved = false,
        ?string $search = null,
        ?int $rating = null,
        ?string $sortBy = null,
        ?string $sortDirection = null,
        ?int $perPage = null,
        int $page = 1,
    ): Page {
        $body = $this->transport->get('testimonials', self::query([
            'approved' => $approved,
            'search' => $search,
            'rating' => $rating,
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
            'per_page' => $perPage,
            'page' => $page === 1 ? null : $page,
        ]))->json();

        return Page::fromBody($body, Testimonial::fromArray(...));
    }

    /**
     * Every testimonial that matches the filters, page by page.
     *
     * @return Generator<int, Testimonial>
     */
    public function all(
        bool $approved = false,
        ?string $search = null,
        ?int $rating = null,
        ?string $sortBy = null,
        ?string $sortDirection = null,
        int $perPage = 100,
    ): Generator {
        return Page::walk(fn (int $page): Page => $this->list(
            $approved, $search, $rating, $sortBy, $sortDirection, $perPage, $page,
        ));
    }

    /**
     * One testimonial, with its contact, votes and comments.
     */
    public function find(int $id): Testimonial
    {
        return Testimonial::fromArray($this->transport->get("testimonials/{$id}")->json());
    }

    /**
     * Create a pending testimonial.
     *
     * @param  int|null  $rating  1 to 5.
     * @param  int|null  $contactId  Links the testimonial to a contact of the client.
     */
    public function create(string $description, string $username, ?int $rating = null, ?int $contactId = null): Testimonial
    {
        $response = $this->transport->post('testimonials', self::filled([
            'description' => $description,
            'username' => $username,
            'rating' => $rating,
            'contact_id' => $contactId,
        ]));

        return Testimonial::fromArray($response->json());
    }

    /**
     * Change the fields that you give. An approval date approves the
     * testimonial.
     *
     * @param  DateTimeInterface|string|null  $approvedAt  A date, or an ISO 8601 string.
     */
    public function update(
        int $id,
        ?string $description = null,
        ?int $rating = null,
        ?string $username = null,
        DateTimeInterface|string|null $approvedAt = null,
    ): Testimonial {
        $response = $this->transport->patch("testimonials/{$id}", self::filled([
            'description' => $description,
            'rating' => $rating,
            'username' => $username,
            'approved_at' => self::date($approvedAt),
        ]));

        return Testimonial::fromArray($response->json());
    }

    /**
     * Approve a testimonial. The default approval date is now.
     */
    public function approve(int $id, ?DateTimeInterface $at = null): Testimonial
    {
        return $this->update($id, approvedAt: $at ?? new DateTimeImmutable);
    }
}
