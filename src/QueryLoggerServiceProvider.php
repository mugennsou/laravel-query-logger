<?php

namespace Mugennsou\LaravelQueryLogger;

use Mugennsou\LaravelQueryLogger\Subscribers\RecordQueryLogSubscriber;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class QueryLoggerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Event::subscribe(RecordQueryLogSubscriber::class);
    }
}
