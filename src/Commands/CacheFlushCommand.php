<?php

namespace Waavi\Translation\Commands;

use Illuminate\Console\Command;
use Waavi\Translation\Cache\CacheRepositoryInterface as CacheRepository;

class CacheFlushCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'translator:flush
                            {--locale= : Flush cache for a specific locale}
                            {--group= : Flush cache for a specific group}
                            {--namespace=* : Flush cache for a specific namespace (default: *)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush the translation cache (all or specific entries).';

    /**
     * The cache repository instance.
     *
     * @var CacheRepository
     */
    protected $cacheRepository;

    /**
     * Whether caching is enabled.
     *
     * @var bool
     */
    protected $cacheEnabled;

    /**
     * Create the cache flush command.
     *
     * @param  CacheRepository  $cacheRepository
     * @param  bool  $cacheEnabled
     */
    public function __construct(CacheRepository $cacheRepository, $cacheEnabled)
    {
        parent::__construct();
        $this->cacheRepository = $cacheRepository;
        $this->cacheEnabled = $cacheEnabled;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$this->cacheEnabled) {
            $this->info('The translation cache is disabled.');
            return 0;
        }

        $locale = $this->option('locale');
        $group = $this->option('group');
        $namespace = $this->option('namespace');
        $namespace = !empty($namespace) ? $namespace[0] : '*';

        if ($locale && $group) {
            $this->cacheRepository->flush($locale, $group, $namespace);
            $this->info("Translation cache cleared for: {$locale}/{$group}/{$namespace}");
        } else {
            $this->cacheRepository->flushAll();
            $this->info('All translation cache has been cleared.');
        }

        return 0;
    }

    /**
     * Execute the console command (Laravel 5.x compatibility).
     *
     * @return void
     */
    public function fire()
    {
        $this->handle();
    }
}
