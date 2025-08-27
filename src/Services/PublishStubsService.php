<?php

namespace Kyledoesdev\Essentials\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

final class PublishStubsService
{
    protected string $packagePath;
    protected array $publishableFiles = [];
    protected array $existingFiles = [];
    
    public function __construct()
    {
        $this->packagePath = realpath(__DIR__ . '/../..');
    }
    
    public function getPublishableFiles(?string $tag = null): array
    {
        $this->publishableFiles = [];
        
        if (!$tag || in_array($tag, ['stubs', 'models', 'filament'])) {
            $this->collectStubFiles($tag);
        }
        
        return $this->publishableFiles;
    }
    
    public function getExistingFiles(?string $tag = null): array
    {
        $this->existingFiles = [];
        $publishableFiles = $this->getPublishableFiles($tag);
        
        foreach ($publishableFiles as $source => $destination) {
            if (File::exists($destination)) {
                $this->existingFiles[] = $this->getRelativePath($destination);
            }
        }
        
        return $this->existingFiles;
    }
    
    public function publish(array $files, bool $force = false): array
    {
        $published = [];
        $skipped = [];
        
        foreach ($files as $source => $destination) {
            $directory = dirname($destination);

            if (!File::exists($source)) {
                continue;
            }
            
            if (File::exists($destination) && !$force) {
                $skipped[] = $this->getRelativePath($destination);
                continue;
            }

            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            if (str_ends_with($source, '.stub')) {
                $content = File::get($source);
                File::put($destination, $content);
            } else {
                File::copy($source, $destination);
            }
            
            $published[] = $this->getRelativePath($destination);
        }
        
        return [
            'published' => $published,
            'skipped' => $skipped
        ];
    }
    
    protected function collectStubFiles(?string $tag): void
    {
        $stubsPath = $this->packagePath . '/stubs';

        $searchPath = $stubsPath . ($tag === 'models' ? '/Models' : ($tag === 'filament' ? '/Filament' : ''));

        if (! File::exists($stubsPath) || ($tag && ! File::exists($searchPath))) {
            return;
        }
        
        $finder = new Finder();
        $finder->files()->in($searchPath)->name('*.stub');
        
        foreach ($finder as $file) {
            $relativePath = str_replace($stubsPath . '/', '', $file->getRealPath());
            $targetPath = str_replace('.stub', '.php', $relativePath);
            $targetFullPath = app_path($targetPath);
            
            $this->publishableFiles[$file->getRealPath()] = $targetFullPath;
        }
    }
                
    public function getAvailableTags(): array
    {
        return ['stubs', 'models', 'filament'];
    }

    private function getRelativePath(string $path): string
    {
        return str_starts_with($path, base_path()) ? ltrim(str_replace(base_path(), '', $path), '/') : $path;
    }
}