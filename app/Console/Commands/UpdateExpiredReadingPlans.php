<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateExpiredReadingPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reading-plans:update-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '期日を過ぎた読書計画のステータスを期限切れに更新する';

    /**
     * 期限切れの読書計画を更新する。
     *
     * @return int コマンドの終了コード
     */
    public function handle(): int
    {
        ReadingPlan::where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', '<', Carbon::today())
            ->update([
                'status' => ReadingPlanStatus::Expired,
            ]);

        return Command::SUCCESS;
    }
}
