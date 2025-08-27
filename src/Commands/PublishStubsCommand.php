<?php

namespace Kyledoesdev\Essentials\Commands;

use Illuminate\Console\Command;
use Kyledoesdev\Essentials\Services\PublishStubsService;

class PublishStubsCommand extends Command
{
    protected $signature = 'essentials:publish 
        {--force : Overwrite existing files}
        {--tag= : Publish specific tag (stubs, models, filament, migrations)}';

    protected $description = 'Publish Essentials package resources';

    protected PublishStubsService $publisher;

    protected ?string $tag = null;

    protected bool $force = false;

    public function __construct()
    {
        parent::__construct();

        $this->publisher = new PublishStubsService;
    }

    public function handle(): int
    {
        $this->tag = $this->option('tag');
        $this->force = $this->option('force');

        $this->newLine();

        $this->info($this->tag ? "🚀 Publishing [{$this->tag}] resources..." : "🚀 Publishing all resources...");

        if (!is_null($this->tag) && !in_array($this->tag, $this->publisher->getAvailableTags())) {
            $this->error("Invalid tag: {$this->tag}");
            $this->line('Available tags: ' . implode(', ', $this->publisher->getAvailableTags()));
            return self::FAILURE;
        }

        $existingFiles = $this->publisher->getExistingFiles($this->tag);

        if (!empty($existingFiles) && !$this->force) {
            $this->newLine();
            $this->warn('⚠️ The following files already exist:');
            
            foreach ($existingFiles as $file) {
                $this->line("   • {$file}");
            }
            
            $this->newLine();
            
            if ($this->confirm('Do you want to overwrite these files?', false)) {
                $this->force = true;
            } else {
                $this->info('Skipping existing files...');
            }
        }

        $publishableFiles = $this->publisher->getPublishableFiles($this->tag);

        if (empty($publishableFiles)) {
            $this->warn('No files to publish.');
            return self::SUCCESS;
        }

        $results = $this->publisher->publish($publishableFiles, $this->force);

        $this->displayResults($results);

        return self::SUCCESS;
    }

    protected function displayResults(array $results): void
    {
        $this->newLine();

        if (!empty($results['published'])) {
            $this->info('✅ Published files:');

            foreach ($results['published'] as $file) {
                $this->line("   • {$file}");
            }
        }

        if (!empty($results['skipped'])) {
            $this->newLine();
            $this->warn('⏭️  Skipped files (already exist):');

            foreach ($results['skipped'] as $file) {
                $this->line("   • {$file}");
            }

            $this->newLine();
            $this->comment('Use --force to overwrite existing files.');
        }

        $this->newLine();
        
        $publishedCount = count($results['published']);
        $skippedCount = count($results['skipped']);
        
        $summary = $publishedCount > 0 
            ? "Successfully published {$publishedCount} file(s)"
            : "No new files published";
            
        if ($skippedCount > 0) {
            $summary .= " ({$skippedCount} skipped)";
        }
        
        $this->info($summary);
    }
}