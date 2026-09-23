<?php

use App\Services\Matching\RecommendationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('matching:refresh', function (RecommendationService $recommendations) {
    $count = $recommendations->refreshAll();
    $this->info("Đã tính lại điểm khớp cho {$count} sinh viên.");
})->purpose('Tính lại điểm khớp việc cho mọi sinh viên');
