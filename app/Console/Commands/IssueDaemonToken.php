<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class IssueDaemonToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daemon:token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Issue (or reissue) the API token used by the Python monitoring daemon';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::firstOrCreate(
            ['email' => 'daemon@netguard.local'],
            [
                'name' => 'Monitoring Daemon',
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
            ]
        );

        $user->tokens()->delete();

        $token = $user->createToken('monitoring-daemon', ['monitoring:write']);

        $this->info('Daemon API token (copy into the Python daemon .env as API_TOKEN — shown once):');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
