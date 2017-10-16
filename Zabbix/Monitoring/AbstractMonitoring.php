<?php

namespace MCD\ZabbixMonitoringBundle\Zabbix\Monitoring;

abstract class AbstractMonitoring
{
    /**
     * @var string
     */
    private $filepath;

    /**
     * @param string $filepath
     */
    public function __construct(string $filepath)
    {
        $this->filepath = $filepath;
    }

    /**
     * @return bool|int
     */
    public function getValue()
    {
        return (int) file_get_contents($this->filepath);
    }

    /**
     * @param int $increment
     *
     * @return bool|int
     */
    public function incrementValue(int $increment = 1)
    {
        $value = $this->getValue();
        $value += $increment;

        return file_put_contents($this->filepath, $value);
    }

    /**
     * @return bool|int
     */
    public function initialize()
    {
        return file_put_contents($this->filepath, 0);
    }
}
