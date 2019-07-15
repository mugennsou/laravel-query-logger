<?php

namespace Mugennsou\LaravelQueryLogger;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Mugennsou\LaravelQueryLogger\Subscribers\RecordQueryLogSubscriber;

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
