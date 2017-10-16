<?php

namespace MCD\ZabbixMonitoringBundle\Zabbix\Agent;

class Config
{
    /**
     * @var string
     */
    private $configFilename;

    /**
     * @var array
     */
    private $configArray = [];

    /**
     * @param null|string $filename
     */
    public function __construct(string $filename = null) {
        $this->configFilename = isset($filename) && is_readable($filename)
            ? $filename
            : '/etc/zabbix/zabbix_agentd.conf'
        ;
        $this->configArray = $this->load($this->configFilename);
    }

    /**
     * @return array
     */
    public function getConfigArray() {
        return $this->configArray;
    }

    /**
     * @return string|null
     */
    public function getServer() {
        $return_value = null;
        if (array_key_exists('Server', $this->configArray)) {
            $return_value = $this->configArray{'Server'};
        }

        return $return_value;
    }

    /**
     * @return int|null
     */
    public function getServerPort() {
        $return_value = null;
        if (array_key_exists('ServerPort', $this->configArray) && is_numeric($this->configArray{'ServerPort'})) {
            $return_value = intval($this->configArray{'ServerPort'});
        }

        return $return_value;
    }

    /**
     * @return null|string
     */
    public function getCurrentConfigFilename()
    {
        return $this->configFilename;
    }

    /**
     * @param null|string $filename
     *
     * @return array
     */
    public function load(string $filename = null){
        $config_array = [];

        if (isset($filename) && is_readable($filename)) {
            $config_lines = file($filename);
            $config_lines = preg_grep("/^\s*[A-Za-z].+\=.+/", $config_lines);
            foreach ($config_lines as $line_num => $line) {
                list($key, $value) = explode("=", $line, 2);
                $key = trim($key);
                $value = trim($value);
                $config_array{$key} = $value; 
            }
        }

        return $config_array;
    }
}


