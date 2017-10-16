<?php

namespace MCD\ZabbixMonitoringBundle\Command;

use AppBundle\Monitorer\VisitorMonitorer;
use MCD\ZabbixMonitoringBundle\Zabbix\Collector\Collector;
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
     * @var VisitorMonitorer
     */
    private $monitorer;

    /**
     * @param Collector $collector
     * @param VisitorMonitorer $monitorer
     */
    public function __construct(Collector $collector, VisitorMonitorer $monitorer)
    {
        $this->collector = $collector;
        $this->monitorer = $monitorer;

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
        $this->collector->measure($this->getMonitoringKey(), $this->monitorer->getValue());
        $this->collector->flush();

        $output->writeln($this->monitorer->initialize());
    }
}
