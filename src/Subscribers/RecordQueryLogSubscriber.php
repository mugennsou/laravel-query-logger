<?php

namespace Mugennsou\LaravelQueryLogger\Subscribers;

use Closure;
use DateTimeInterface;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

class RecordQueryLogSubscriber
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $logs;

    /**
     * @var float
     */
    protected $runtime;

    /**
     * @var bool
     */
    protected $enable;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->enable = config('app.debug') === true;
        $this->clear();
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(RouteMatched::class, 'Mugennsou\LaravelQueryLogger\Subscribers\RecordQueryLogSubscriber@routeMatched');
        $events->listen(RequestHandled::class, 'Mugennsou\LaravelQueryLogger\Subscribers\RecordQueryLogSubscriber@requestHandled');

        $events->listen(CommandStarting::class, 'Mugennsou\LaravelQueryLogger\Subscribers\RecordQueryLogSubscriber@commandStarting');
        $events->listen(CommandFinished::class, 'Mugennsou\LaravelQueryLogger\Subscribers\RecordQueryLogSubscriber@commandFinished');
    }

    /**
     * @param RouteMatched $event
     */
    public function routeMatched(RouteMatched $event)
    {
        if ($this->enable) {
            $this->enableQueryLog();
        }
    }

    /**
     * @param RequestHandled $event
     */
    public function requestHandled(RequestHandled $event)
    {
        if ($this->enable) {
            $this->prepareQueryLogs();

            $fullUrl = urldecode($event->request->fullUrl());
            $action  = empty($event->request->route()) ? '' : $event->request->route()->getAction('uses');
            $action  = $action instanceof Closure ? 'Closure' : $action;

            $this->logs = sprintf(
                "\n============ %s : %s ============\n\nACTION: %s\nSQL COUNT: %s\nSQL RUNTIME: %s ms\n\n%s",
                $event->request->method(), $fullUrl, $action, count(DB::getQueryLog()), $this->runtime, $this->logs
            );

            $this->recordLogs();
            $this->clear();
        }
    }

    public function commandStarting(CommandStarting $event)
    {
        if ($this->enable) {
            $this->enableQueryLog();
        }
    }

    public function commandFinished(CommandFinished $event)
    {
        if ($this->enable) {
            $this->prepareQueryLogs();

            $this->logs = sprintf(
                "\n============ %s ============\n\nEXIT CODE: %s\nSQL COUNT: %s\nSQL RUNTIME: %s ms\n\n%s",
                $event->command, $event->exitCode, count(DB::getQueryLog()), $this->runtime, $this->logs
            );

            $this->recordLogs();
            $this->clear();
        }
    }

    protected function enableQueryLog(): void
    {
        DB::enableQueryLog();
    }

    protected function prepareQueryLogs()
    {
        foreach (DB::getQueryLog() as $query) {
            [$sql, $bindings, $time] = array_values($query);

            $sqlWithPlaceholders = str_replace(['%', '?'], ['%%', '%s'], $sql);
            $bindings            = $this->prepareBindings($bindings);
            $realSql             = vsprintf($sqlWithPlaceholders, $bindings);

            $this->logs    .= sprintf("[%s ms] %s\n", $time, $realSql);
            $this->runtime += $time;
        }
    }

    protected function recordLogs()
    {
        $this->logger->debug($this->logs);
    }

    protected function clear(): void
    {
        $this->clearLogs();
        $this->clearRuntime();
    }

    protected function clearLogs(): void
    {
        $this->logs = '';
    }

    protected function clearRuntime(): void
    {
        $this->runtime = 0.0;
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param  array $bindings
     * @return array
     */
    protected function prepareBindings(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format('Y-m-d H:i:s');
            } elseif (is_bool($value)) {
                $bindings[$key] = (int)$value;
            }
        }

        return $bindings;
    }
}
