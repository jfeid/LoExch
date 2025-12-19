<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateInternalJobSecret extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'internal:secret
                            {--show : Display the secret instead of modifying files}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a random INTERNAL_JOB_SECRET and save it to .env';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $secret = Str::random(64);

        if ($this->option('show')) {
            $this->line($secret);

            return self::SUCCESS;
        }

        if (! $this->setSecretInEnvironmentFile($secret)) {
            return self::FAILURE;
        }

        $this->laravel['config']['services.internal_job.secret'] = $secret;

        $this->components->info('Internal job secret set successfully.');

        return self::SUCCESS;
    }

    /**
     * Set the secret in the environment file.
     */
    protected function setSecretInEnvironmentFile(string $secret): bool
    {
        $currentSecret = $this->laravel['config']['services.internal_job.secret'];

        if ($currentSecret !== null && $currentSecret !== '' && ! $this->confirmOverwrite()) {
            return false;
        }

        if (! $this->writeSecretToEnvironmentFile($secret)) {
            return false;
        }

        return true;
    }

    /**
     * Confirm that the user wants to overwrite the existing secret.
     */
    protected function confirmOverwrite(): bool
    {
        return $this->option('force') || $this->confirm(
            'An INTERNAL_JOB_SECRET already exists. Do you want to overwrite it?'
        );
    }

    /**
     * Write the secret to the environment file.
     */
    protected function writeSecretToEnvironmentFile(string $secret): bool
    {
        $envPath = $this->laravel->environmentFilePath();

        if (! file_exists($envPath)) {
            $this->components->error('Environment file not found.');

            return false;
        }

        $contents = file_get_contents($envPath);

        if (preg_match('/^INTERNAL_JOB_SECRET=.*/m', $contents)) {
            $contents = preg_replace(
                '/^INTERNAL_JOB_SECRET=.*/m',
                'INTERNAL_JOB_SECRET='.$secret,
                $contents
            );
        } else {
            $contents .= "\nINTERNAL_JOB_SECRET=".$secret."\n";
        }

        file_put_contents($envPath, $contents);

        return true;
    }
}
