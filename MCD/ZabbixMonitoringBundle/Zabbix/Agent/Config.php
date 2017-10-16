<?php

namespace MCD\ZabbixMonitoringBundle\Zabbix\Agent;

class Config
{
    /**
     * @var string
     */
    private $_config_filename;

    /**
     * @var array
     */
    private $_config_array = array();

    /**
     * @param null|string $filename
     */
    public function __construct(string $filename = null) {
        $this->_config_filename = isset($filename) && is_readable($filename)
            ? $filename
            : '/etc/zabbix/zabbix_agentd.conf'
        ;
        $this->_config_array = $this->load($this->_config_filename);
    }

    /**
     * @return array
     */
    public function getConfigArray() {
        return $this->_config_array;
    }

    /**
     * @return string|null
     */
    public function getServer() {
        $return_value = null;
        if (array_key_exists('Server', $this->_config_array)) {
            $return_value = $this->_config_array{'Server'}; 
        }

        return $return_value;
    }

    /**
     * @return int|null
     */
    public function getServerPort() {
        $return_value = null;
        if (array_key_exists('ServerPort', $this->_config_array) && is_numeric($this->_config_array{'ServerPort'})) {
            $return_value = intval($this->_config_array{'ServerPort'});
        }

        return $return_value;
    }

    /**
     * @return null|string
     */
    public function getCurrentConfigFilename()
    {
        return $this->_config_filename;
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


