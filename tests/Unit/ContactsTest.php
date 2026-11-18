<?php

declare(strict_types=1);

use FeedbackPilgrim\Data\Contact;

it('lists contacts with the filters', function () {
    $history = [];
    $page = pilgrim([json(paginator([contactPayload()]))], $history)->contacts()->list(search: 'jane', tag: 'vip', sortBy: 'mrr', sortDirection: 'desc', perPage: 100);

    expect(sent($history[0]['request']))->toBe('GET /api/public/v1/contacts?search=jane&tag=vip&sort_by=mrr&sort_direction=desc&per_page=100')
        ->and($page->first())->toBeInstanceOf(Contact::class);
});

it('reads the custom fields of a contact from the Contacts operations', function () {
    $contact = pilgrim([json(contactPayload())])->contacts()->find(7);

    expect($contact->id)->toBe(7)
        ->and($contact->uuid)->toBe('6e48ad77-fb51-449f-99d2-9c5cc2d9d0c5')
        ->and($contact->name)->toBe('Jane Smith')
        ->and($contact->mrr)->toBe(149.99)
        ->and($contact->ltv)->toBe(1800.0)
        ->and($contact->topCustomer)->toBeFalse()
        ->and($contact->customFields)->toBe(['plan' => 'pro', 'seats' => 5])
        ->and($contact->tags[0]->title)->toBe('feature');
});

it('reads the custom fields of a nested contact from the metadata string', function () {
    $contact = Contact::fromArray(nestedContactPayload(['top_customer' => 1]));

    expect($contact->customFields)->toBe(['plan' => 'pro'])
        ->and($contact->topCustomer)->toBeTrue();
});

it('reads a nested contact with no metadata', function () {
    expect(Contact::fromArray(nestedContactPayload(['metadata' => null]))->customFields)->toBe([]);
});

it('creates or updates a contact with custom fields as metadata', function () {
    $history = [];
    $contact = pilgrim([json(contactPayload(), 201)], $history)->contacts()->upsert(
        'jane@example.com',
        'Jane Smith',
        mrr: 149.99,
        ltv: 1800,
        avatar: 'https://example.com/jane.jpg',
        tags: [1, 2],
        customFields: ['plan' => 'pro'],
    );

    expect(sent($history[0]['request']))->toBe('POST /api/public/v1/contacts')
        ->and(body($history[0]['request']))->toBe([
            'email' => 'jane@example.com',
            'name' => 'Jane Smith',
            'mrr' => 149.99,
            'ltv' => 1800,
            'avatar' => 'https://example.com/jane.jpg',
            'tags' => [1, 2],
            'metadata' => ['plan' => 'pro'],
        ])
        ->and($contact->customFields['plan'])->toBe('pro');
});

it('retries the upsert after a server error, because it is idempotent', function () {
    $history = [];
    pilgrim([json([], 503), json(contactPayload())], $history, noSleep(2))->contacts()->upsert('jane@example.com', 'Jane');

    expect($history)->toHaveCount(2)
        ->and(body($history[0]['request']))->toBe(['email' => 'jane@example.com', 'name' => 'Jane']);
});

it('updates a contact with PATCH', function () {
    $history = [];
    pilgrim([json(contactPayload(['mrr' => 199]))], $history)->contacts()->update(7, name: 'Jane S.', mrr: 199);

    expect(sent($history[0]['request']))->toBe('PATCH /api/public/v1/contacts/7')
        ->and(body($history[0]['request']))->toBe(['name' => 'Jane S.', 'mrr' => 199]);
});
