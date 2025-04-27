<?php

namespace Webkul\Marketing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Webkul\Marketing\Repositories\SearchTermRepository;

class UpdateCreateSearchTerm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(protected $dat)
    {
        $this->dat = $dat;
    }


    public function handle()
    {
        app(SearchTermRepository::class)->updateOrCreate([
            'term'       => $this->dat['term'],
            'channel_id' => $this->dat['channel_id'],
            'locale'     => $this->dat['locale'],
        ], [
            'uses'    => DB::raw('uses + 1'),
            'results' => $this->dat['results'],
        ]);
    }
}
