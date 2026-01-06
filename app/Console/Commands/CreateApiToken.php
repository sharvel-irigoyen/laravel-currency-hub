<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:create-token {name : The name of the user} {email : The email of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user (if not exists) and generate an API token';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $email = $this->argument('email');

        // Find or create user
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(Str::random(32)), // Random password, auth is via token only usually
            ]
        );

        // Generate token
        $token = $user->createToken('production-access')->plainTextToken;

        $this->info("User: {$user->email}");
        $this->info("Token ID: " . explode('|', $token)[0]);
        $this->line("");
        $this->comment("API Token (KEEP IT SAFE):");
        $this->info($token);
        $this->line("");

        return 0;
    }
}
