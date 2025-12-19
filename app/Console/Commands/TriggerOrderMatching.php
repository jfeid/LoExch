<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TriggerOrderMatching extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:match
                            {--url= : Override the base URL for the API request}
                            {--internal : Use internal localhost URL (for running inside DDEV)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger order matching by calling the internal job endpoint';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $secret = config('services.internal_job.secret');

        if (empty($secret)) {
            $this->components->error('INTERNAL_JOB_SECRET is not configured.');
            $this->components->info('Run: php artisan internal:secret');

            return self::FAILURE;
        }

        $baseUrl = $this->determineBaseUrl();
        $endpoint = rtrim($baseUrl, '/').'/api/internal/job';

        $this->components->info("Triggering order matching at: {$endpoint}");

        try {
            $response = Http::withToken($secret)
                ->timeout(30)
                ->withOptions($this->getHttpOptions())
                ->post($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                $this->components->info('Order matching completed successfully.');

                if (isset($data['matched'])) {
                    $this->components->info("Orders matched: {$data['matched']}");
                }

                if (isset($data['message'])) {
                    $this->line($data['message']);
                }

                return self::SUCCESS;
            }

            $this->components->error("Request failed with status: {$response->status()}");
            $this->line($response->body());

            return self::FAILURE;
        } catch (\Exception $e) {
            $this->components->error('Request failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Determine the base URL based on environment.
     */
    protected function determineBaseUrl(): string
    {
        // Allow explicit override
        if ($url = $this->option('url')) {
            return $url;
        }

        // Force internal URL
        if ($this->option('internal')) {
            return 'http://localhost';
        }

        // Auto-detect: running inside DDEV container
        if ($this->isInsideDdev()) {
            return 'http://localhost';
        }

        // Running from host: use DDEV URL if available, otherwise APP_URL
        if ($ddevUrl = env('DDEV_PRIMARY_URL_WITHOUT_PORT')) {
            return $ddevUrl;
        }

        return config('app.url', 'http://localhost');
    }

    /**
     * Check if running inside a DDEV container.
     */
    protected function isInsideDdev(): bool
    {
        return env('IS_DDEV_PROJECT') === 'true' || env('IS_DDEV_PROJECT') === true;
    }

    /**
     * Get HTTP client options.
     */
    protected function getHttpOptions(): array
    {
        $options = [];

        // Inside DDEV, disable SSL verification for localhost
        if ($this->isInsideDdev() || $this->option('internal')) {
            $options['verify'] = false;
        }

        return $options;
    }
}
