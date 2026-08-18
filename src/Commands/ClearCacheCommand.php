<?php

namespace Shabrani\SelectExcept\Commands;

use Illuminate\Console\Command;
use Shabrani\SelectExcept\ColumnListingCache;

class ClearCacheCommand extends Command
{
    protected $signature = 'select-except:clear';

    protected $description = 'Clear the cached database column listings used by selectExcept()';

    public function handle(ColumnListingCache $columns): int
    {
        $columns->flush();

        $this->info('Column listing cache cleared.');

        return self::SUCCESS;
    }
}
