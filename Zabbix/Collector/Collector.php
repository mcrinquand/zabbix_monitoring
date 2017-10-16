<?php

namespace MCD\ZabbixMonitoringBundle\Zabbix\Collector;

/**
 * Collector interface.
 */
interface Collector
{
    /**
     * Updates a counter by some arbitrary amount.
     *
     * @param string $variable
     * @param int $value The amount to increment the counter by
     */
    public function measure($variable, $value);

    /**
     * Increments a counter.
     *
     * @param string $variable
     */
    public function increment($variable);

    /**
     * Decrements a counter.
     *
     * @param string $variable
     */
    public function decrement($variable);

    /**
     * Records a timing.
     *
     * @param string $variable
     * @param int $time The duration of the timing in milliseconds
     */
    public function timing($variable, $time);

    /**
     * Sends the metrics to the adapter backend.
     */
    public function flush();
}
