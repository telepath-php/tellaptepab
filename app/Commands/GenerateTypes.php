<?php

namespace App\Commands;

use App\Generators\TypeGenerator;
use App\Parsers\Types\TypeParser;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class GenerateTypes extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'generate:types
                            {--path= : Path to the src folder (Default: src/)}
                            {--namespace= : Namespace Prefix (Default: Telepath\\)}
                            {--parent-class= : Parent Class for Type classes (Default: Telepath\\Types\\Type)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Generates Type classes';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $response = Http::get('https://core.telegram.org/bots/api');
        $content = $response->body();

        $srcPath = Str::finish($this->option('path') ?? 'src', '/');
        $namespace = Str::finish($this->option('namespace') ?? 'Telepath\\', '\\');
        $parentClass = $this->option('parent-class') ?? 'Telepath\\Types\\Type';

        $parser = new TypeParser($namespace, $parentClass);
        $types = $parser->parse($content);

        $generator = new TypeGenerator();
        foreach ($types as $type) {
            $file = $generator->generate($type);
            $path = str_replace([$namespace, '\\'], ['', '/'], $type->namespace) . '/';

            File::ensureDirectoryExists($srcPath . $path);
            File::put($srcPath . $path . $type->name . '.php', $file);
        }
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
