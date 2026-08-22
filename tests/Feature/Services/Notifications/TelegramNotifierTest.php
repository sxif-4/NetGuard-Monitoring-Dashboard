<?php

namespace Tests\Feature\Services\Notifications;

use App\Services\Notifications\TelegramNotifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramNotifierTest extends TestCase
{
    public function test_send_posts_to_the_correct_telegram_endpoint_with_the_expected_payload(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $result = app(TelegramNotifier::class)->send('123:abc', '999', 'hello world');

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bot123:abc/sendMessage'
                && $request['chat_id'] === '999'
                && $request['text'] === 'hello world'
                && $request['parse_mode'] === 'Markdown';
        });
    }

    public function test_send_returns_false_on_a_rejected_response(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'bad token'], 401)]);

        $result = app(TelegramNotifier::class)->send('bad-token', '999', 'hello');

        $this->assertFalse($result);
    }

    public function test_send_returns_false_rather_than_throwing_on_a_network_failure(): void
    {
        Http::fake(function () {
            throw new ConnectionException('timed out');
        });

        $result = app(TelegramNotifier::class)->send('123:abc', '999', 'hello');

        $this->assertFalse($result);
    }
}
