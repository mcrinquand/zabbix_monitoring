<?php

namespace MCD\ZabbixMonitoringBundle\Zabbix;

use MCD\ZabbixMonitoringBundle\Zabbix\Agent\Config;
use MCD\ZabbixMonitoringBundle\Zabbix\Exception\SenderNetworkException;
use MCD\ZabbixMonitoringBundle\Zabbix\Exception\SenderProtocolException;

class Sender
{
    /**
     * @var string
     */
    private $servername;

    /**
     * @var int
     */
    private $serverport;

    /**
     * @var int
     */
    private $timeout = 30;

    /**
     * @var string
     */
    private $protocolHeaderString = 'ZBXD';

    /**
     * @var int
     */
    private $protocolVersion = 1;

    /**
     * @var null
     */
    private $lastResponseInfo = null;

    /**
     * @var null
     */
    private $lastResponseArray = null;

    /**
     * @var null
     */
    private $lastProcessed = null;

    /**
     * @var null
     */
    private $lastFailed = null;

    /**
     * @var null
     */
    private $lastSpent = null;

    /**
     * @var null
     */
    private $lastTotal = null;

    /**
     * @var null
     */
    private $socket;

    /**
     * @var null
     */
    private $data;

    /**
     * @param string $servername
     * @param integer $serverport
     */
    public function __construct($servername = 'localhost', $serverport = 10051)
    {
        $this->setServerName($servername);
        $this->setServerPort($serverport);

        $this->initData();
    }

    public function initData()
    {
        $this->data = [
            'request' => 'sender data',
            'data' => [],
        ];
    }

    /**
     * @param Config $agentConfig
     *
     * @return $this
     */
    public function importAgentConfig(Config $agentConfig)
    {
        $this->setServerName($agentConfig->getServer());
        $this->setServerPort($agentConfig->getServerPort());

        return $this;
    }

    /**
     * @param string $servername
     *
     * @return $this
     */
    public function setServerName($servername)
    {
        $this->servername = $servername;

        return $this;
    }

    /**
     * @param int $serverport
     *
     * @return $this
     */
    public function setServerPort($serverport)
    {
        if (is_int($serverport)) {
            $this->serverport = $serverport;
        }

        return $this;
    }

    /**
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout = 0)
    {
        if ((is_int($timeout) || is_numeric($timeout)) && intval($timeout) > 0) {
            $this->timeout = $timeout;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param string  $headerString
     *
     * @return $this
     */
    public function setProtocolHeaderString($headerString)
    {
        $this->protocolHeaderString = $headerString;

        return $this;
    }

    /**
     * @param int $version
     *
     * @return $this
     */
    public function setProtocolVersion($version)
    {
        if (is_int($version) and $version > 0) {
            $this->protocolVersion = $version;
        }

        return $this;
    }

    /**
     * @param string $hostname
     * @param int $key
     * @param string $value
     * @param string $clock
     *
     * @return $this
     */
    public function addData($hostname = null, $key = null, $value = null, $clock = null)
    {
        $input = [
            'host' => $hostname,
            'value' => $value,
            'key' => $key,
        ];

        if (isset($clock)) {
            $input['clock'] = $clock;
        }
        array_push($this->data['data'], $input);

        return $this;
    }

    /**
     * @return array
     */
    public function getDataArray()
    {
        return $this->data['data'];
    }

    /**
     * @return string
     */
    private function buildSendData()
    {
        $json_data = json_encode(array_map(
            function ($t) {
                return is_string($t) ? utf8_encode($t) : $t;
            },
            $this->data
        ));
        $json_length = strlen($json_data);
        $data_header = pack("aaaaCCCCCCCCC",
            substr($this->protocolHeaderString, 0, 1),
            substr($this->protocolHeaderString, 1, 1),
            substr($this->protocolHeaderString, 2, 1),
            substr($this->protocolHeaderString, 3, 1),
            intval($this->protocolVersion),
            ($json_length & 0xFF),
            ($json_length & 0x00FF)>>8,
            ($json_length & 0x0000FF)>>16,
            ($json_length & 0x000000FF)>>24,
            0x00,
            0x00,
            0x00,
            0x00
        );

        return ($data_header . $json_data);
    }

    /**
     * @param mixed $info
     *
     * @return array|null
     */
    protected function parseResponseInfo($info = null)
    {
        # info: "Processed 1 Failed 1 Total 2 Seconds spent 0.000035"
        $parsedInfo = null;
        if(isset($info)){
            list(, $processed, , $failed, , $total, , , $spent) = explode(' ', $info);
            $parsedInfo = [
                "processed" => intval($processed),
                "failed" => intval($failed),
                "total" => intval($total),
                "spent" => $spent,
            ];
        }

        return $parsedInfo;
    }

    /**
     * @return null
     */
    public function getLastResponseInfo()
    {
        return $this->lastResponseInfo;
    }

    /**
     * @return null
     */
    public function getLastResponseArray()
    {
        return $this->lastResponseArray;
    }

    /**
     * @return null
     */
    public function getLastProcessed()
    {
        return $this->lastProcessed;
    }

    /**
     * @return null
     */
    public function getLastFailed()
    {
        return $this->lastFailed;
    }

    /**
     * @return null
     */
    public function getLastSpent()
    {
        return $this->lastSpent;
    }

    /**
     * @return null
     */
    public function getLastTotal()
    {
        return $this->lastTotal;
    }

    private function clearLastResponseData()
    {
        $this->lastResponseInfo = null;
        $this->lastResponseArray = null;
        $this->lastProcessed = null;
        $this->lastFailed = null;
        $this->lastSpent = null;
        $this->lastTotal = null;
    }

    private function close()
    {
        if ($this->socket) {
            fclose($this->socket);
        }
    }

    /**
     * Connect to Zabbix Server
     * @throws SenderNetworkException
     */
    private function connect()
    {
        $this->socket = @fsockopen(
            $this->servername,
            intval($this->serverport),
            $errCode,
            $errMessage,
            $this->timeout
        );

        if (!$this->socket) {
            throw new SenderNetworkException(sprintf('%s, %s', $errCode, $errMessage));
        }
    }

    /**
     * @param mixed $socket
     * @param string $data
     *
     * @return bool|int
     */
    private function write($socket, $data)
    {
        if (!$socket) {
            throw new SenderNetworkException('socket was not writable,connect failed.');
        }
        $totalWritten = 0;
        $length = strlen($data);

        while ($totalWritten < $length) {
            $writeSize = @fwrite($socket,$data);
            if ($writeSize === false) {
                return false;
            } else {
                $totalWritten += $writeSize;
                $data = substr($data,$writeSize);
            }
        }

        return $totalWritten; 
    }

    /**
     * Read data from socket
     * @throws SenderNetworkException
     */
    private function read($socket)
    {
        if (!$socket) {
            throw new SenderNetworkException('socket was not readable, connect failed.');
        }

        $recvData = '';
        while (!feof($socket)) {
            $buffer = fread($socket,8192);
            if($buffer === false){
                return false; 
            }

            $recvData .= $buffer;
        }

        return $recvData; 
    }

    /**
     * Main
     * @throws SenderNetworkException
     * @throws SenderProtocolException
     *
     */ 
    public function send()
    {
        $sendData = $this->buildSendData();
        $datasize = strlen($sendData);
 
        $this->connect();
      
        /* send data to zabbix server */ 
        $sentsize = $this->write($this->socket,$sendData);
        if ($sentsize === false || $sentsize != $datasize) {
            throw new SenderNetworkException('Cannot receive response');
        }
        
        /* receive data from zabbix server */ 
        $recvData = $this->read($this->socket);
        if ($recvData === false) {
            throw new SenderNetworkException('Cannot receive response');
        }
        
        $this->close();
        
        $recvProtocolHeader = substr($recvData, 0, 4);
        if ($recvProtocolHeader == 'ZBXD') {
            $responseData = substr($recvData, 13);
            $responseArray = json_decode($responseData, true);
            if (is_null($responseArray)) {
                throw new SenderProtocolException('Invalid json data in receive data');
            }

            $this->lastResponseArray = $responseArray;
            $this->lastResponseInfo = $responseArray['info'];
            $parsedInfo = $this->parseResponseInfo($this->lastResponseInfo);
            $this->lastProcessed = $parsedInfo['processed'];
            $this->lastFailed = $parsedInfo['failed'];
            $this->lastSpent = $parsedInfo['spent'];
            $this->lastTotal = $parsedInfo['total'];

            if ($responseArray['response'] === 'success') {
                $this->initData();

                return true;
            } else {
                $this->clearLastResponseData();

                return false; 
            }
        } else {
            $this->clearLastResponseData();

            throw new SenderProtocolException('invalid protocol header in receive data'); 
        }
    }
}


