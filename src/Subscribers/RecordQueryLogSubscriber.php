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
    protected $logs = '';

    /**
     * @var float
     */
    protected $runtime = 0.0;

    /**
     * @var bool
     */
    protected $enable = false;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->enable = config('app.debug') === true;
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
            $this->prepare();
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
        }
    }

    public function commandStarting(CommandStarting $event)
    {
        if ($this->enable) {
            $this->prepare();
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
        }
    }

    protected function prepareQueryLogs(): void
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

    /**
     * Prepare the query bindings for execution.
     *
     * @param  array $bindings
     * @return array
     */
    protected function prepareBindings(array $bindings): array
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

    protected function prepare(): void
    {
        $this->clear();
        $this->enableQueryLog();
    }

    protected function enableQueryLog(): void
    {
        DB::enableQueryLog();
    }

    protected function recordLogs(): void
    {
        $this->logger->debug($this->logs);

        $this->clear();
    }

    protected function clear(): void
    {
        $this->clearLogs();
        $this->clearRuntime();
        $this->flushQueryLog();
    }

    protected function clearLogs(): void
    {
        $this->logs = '';
    }

    protected function clearRuntime(): void
    {
        $this->runtime = 0.0;
    }

    protected function flushQueryLog(): void
    {
        DB::flushQueryLog();
    }
}
