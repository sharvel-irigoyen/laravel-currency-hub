<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateDevToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a development API token and save it to the .env file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (app()->environment('production')) {
            $this->error('This command can only be run in local environment.');
            return 1;
        }

        $user = User::firstOrCreate(
            ['email' => 'dev@localhost'],
            [
                'name' => 'Developer',
                'password' => Hash::make(Str::random(32)),
            ]
        );

        // Revoke existing tokens to keep it clean
        $user->tokens()->delete();

        $token = $user->createToken('dev-access')->plainTextToken;

        $this->info("Token generated: {$token}");

        $this->updateEnvFile('DEV_API_TOKEN', $token);

        $this->info('Token saved to .env file as DEV_API_TOKEN');

        return 0;
    }

    protected function updateEnvFile($key, $value)
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            $env = file_get_contents($path);

            // If key exists, replace it
            if (strpos($env, "{$key}=") !== false) {
                $env = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $env);
            } else {
                // Determine if we need to add a newline before appending
                $newline = (substr($env, -1) !== "\n") ? "\n" : "";
                $env .= "{$newline}{$key}={$value}";
            }

            file_put_contents($path, $env);
        }
    }
}
