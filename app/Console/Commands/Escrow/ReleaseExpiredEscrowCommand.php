<?php

namespace App\Console\Commands\Escrow;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use Domain\Escrow\Actions\ReleaseExpiredEscrowAction;

#[Signature('escrow:release-expired {--batch-size=50 : Number of escrows to process per batch}')]
#[Description('Release escrow funds that have passed their auto-release date')]
class ReleaseExpiredEscrowCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batchSize = (int) $this->option('batch-size');

        $this->info("Processing expired escrows with batch size: {$batchSize}");

        ReleaseExpiredEscrowAction::execute($batchSize);

        return 0;
    }
}
