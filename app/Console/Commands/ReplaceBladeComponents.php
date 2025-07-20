<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ReplaceBladeComponents extends Command
{
    protected $signature = 'blade:convert-xcomponents';
    protected $description = 'Replace <x-...> Blade components with plain HTML/Blade syntax';

    protected $replacements = [
        '/<x-input-label\s+for="(.*?)"\s+value="(.*?)"\s*\/>/' =>
            '<label for="$1">$2</label>',

        '/<x-text-input\s+([^>]*)\/>/' =>
            '<input $1>',

        '/<x-input-error\s+messages="\$errors->get\(\'(.*?)\'\)"[^\/]*\/>/' =>
            '@error(\'$1\')<div class="text-red-500 text-xs">{{ $message }}</div>@enderror',

        '/<x-primary-button>\s*(.*?)\s*<\/x-primary-button>/' =>
            '<button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">$1</button>',

        '/<x-app-layout>/' =>
            '',

        '/<\/x-app-layout>/' =>
            '',

        '/<x-slot name="header">/' =>
            '@section(\'header\')',

        '/<\/x-slot>/' =>
            '@endsection',
    ];

    public function handle()
    {
        $directory = resource_path('views');
        $files = File::allFiles($directory);
        $updatedFiles = 0;

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') continue;

            $contents = File::get($file);
            $original = $contents;

            foreach ($this->replacements as $pattern => $replacement) {
                $contents = preg_replace($pattern, $replacement, $contents);
            }

            if ($contents !== $original) {
                File::put($file, $contents);
                $this->info("✅ Updated: " . $file->getRelativePathname());
                $updatedFiles++;
            }
        }

        $this->info("\n🎉 Replacement complete. {$updatedFiles} file(s) updated.");
    }
}
