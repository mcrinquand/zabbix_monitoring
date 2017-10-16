<?php

namespace MCD\ZabbixMonitoringBundle\Command;

use MCD\ZabbixMonitoringBundle\Zabbix\Collector\Collector;
use MCD\ZabbixMonitoringBundle\Zabbix\Monitoring\AbstractMonitoring;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractMonitoringCommand extends Command
{
    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var AbstractMonitoring
     */
    private $monitoring;

    /**
     * @param Collector $collector
     * @param AbstractMonitoring $monitoring
     */
    public function __construct(Collector $collector, AbstractMonitoring $monitoring)
    {
        $this->collector = $collector;
        $this->monitoring = $monitoring;

        // you *must* call the parent constructor
        parent::__construct();
    }

    /**
     * @return string
     */
    abstract protected function getMonitoringKey();

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->collector->measure($this->getMonitoringKey(), $this->monitoring->getValue());
        $this->collector->flush();

        $output->writeln($this->monitoring->initialize());
    }
}
