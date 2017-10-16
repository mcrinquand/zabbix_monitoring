MCDZabbixMonitoring
========================

This repository aims to provide easy way to implement monitoring with Zabbix.

Installation
------------

### Add the bundle as dependency with Composer

``` bash
$ php composer.phar require mcrinquand/zabbix_monitoring
```

### Enable the bundle in the kernel

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = [
        // ...
        new MCD\ZabbixMonitoringBundle\MCDZabbixMonitoringBundle(),
    ];
}
```

### Configure

Then You have to configure the bundle. 

``` yml
mcd_zabbix_monitoring:
    default: zabbix
    collectors:
        zabbix:
            type: zabbix
            prefix: apache
            host: localhost
            port: 10051
```

Type is zabbix only for the moment.

Usage
-----
It allows you to monitor data like a counter. 

The monitoring is split in two part : first is aggregation of data and then sending of data. 

### Record data

For the first part, you have to create a class to monitor your data. This class must extends 
MCD\ZabbixMonitoringBundle\Zabbix\Monitoring\AbstractMonitoring and be defined as service. It expect a filepath 
into constructor. This parameter is the path to the file where the data will be stored waiting to be send to your 
Zabbix server.

Here's an example of visitor monitorer.

``` php
<?php

namespace AppBundle\Monitorer;

use MCD\ZabbixMonitoringBundle\Zabbix\Monitoring\AbstractMonitoring;

class VisitorMonitorer extends AbstractMonitoring
{
}
```

Set VisitorMonitorer as service with filepath to save data :

``` yml
services:
    AppBundle\Monitorer\VisitorMonitorer:
        class: 'AppBundle\Monitorer\VisitorMonitorer'
        public: true
        arguments:
            $filepath: '%kernel.root_dir%/../var/monitoring/visitor.txt'
```

Record one ot four visitors :

``` php
$monitorer = $this->get('AppBundle\Monitorer\VisitorMonitorer');
// record one visitor
$monitorer->incrementValue();

// record four visitor
$monitorer->incrementValue(4);
```

### Send data

Then you can define the task to send monitored data to your Zabbix server.

You have to define a Command make it extends MCD\ZabbixMonitoringBundle\Zabbix\Command\AbstractMonitoringCommand 
and declare it as service. Give it the Monitoring class you want to send as second argument.

Then you can declare the command into you crontab to send value to Zabbix.

When the command send data, it "clear" the current count. 

Authors
-------

The bundle was originally created by [Matthieu Crinquand](https://github.com/mcrinquand).
See the list of [contributors](https://github.com/mcrinquand/zabbix_monitoring/contributors).

This bundle is inspired by the [beberlei/metrics](https://github.com/beberlei/metrics) bundle.
