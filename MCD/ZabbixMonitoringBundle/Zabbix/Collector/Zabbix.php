<?php

namespace MCD\ZabbixMonitoringBundle\Zabbix\Collector;

use MCD\ZabbixMonitoringBundle\Zabbix\Sender;

/**
 * Zabbix Collector.
 */
class Zabbix implements Collector
{
    /**
     * @var Sender
     */
    private $sender;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @param Sender $sender
     * @param null               $prefix
     */
    public function __construct(Sender $sender, $prefix = null)
    {
        $this->sender = $sender;
        $this->prefix = $prefix ?: gethostname();
    }

    /**
     * {@inheritdoc}
     */
    public function increment($variable)
    {
        $this->sender->addData($this->prefix, $variable, +1);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement($variable)
    {
        $this->sender->addData($this->prefix, $variable, -1);
    }

    /**
     * {@inheritdoc}
     */
    public function timing($variable, $time)
    {
        $this->sender->addData($this->prefix, $variable, $time);
    }

    /**
     * {@inheritdoc}
     */
    public function measure($variable, $value)
    {
        $this->sender->addData($this->prefix, $variable, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->sender->send();
    }
}
