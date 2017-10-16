<?php

namespace MCD\ZabbixMonitoringBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class MCDZabbixMonitoringExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('metrics.xml');

        foreach ($config['collectors'] as $name => $colConfig) {
            $definition = $this->createCollector($colConfig['type'], $colConfig);
            $container->setDefinition('mcdzabbix_metrics.collector.'.$name, $definition);
        }

        if (!$config['default'] && 1 === count($config['collectors'])) {
            $container->setAlias('mcdzabbix_metrics.collector', 'mcdzabbix_metrics.collector.'.$name);
        } elseif ($container->hasDefinition('mcdzabbix_metrics.collector.'.$config['default'])) {
            $container->setAlias('mcdzabbix_metrics.collector', 'mcdzabbix_metrics.collector.'.$config['default']);
        } else {
            throw new \LogicException('You should select a default collector');
        }
    }

    private function createCollector($type, $config)
    {
        $definition = new ChildDefinition('mcdzabbix_metrics.collector_proto.'.$config['type']);

        // Theses listeners should be as late as possible
        $definition->addTag('kernel.event_listener', array(
            'method' => 'flush',
            'priority' => -1024,
            'event' => 'kernel.terminate',
        ));
        $definition->addTag('kernel.event_listener', array(
            'method' => 'flush',
            'priority' => -1024,
            'event' => 'console.terminate',
        ));

        if (count($config['tags']) > 0) {
            $definition->addMethodCall('setTags', array($config['tags']));
        }

        $sender = new Definition('MCD\ZabbixMonitoringBundle\Zabbix\Sender');
        if ($config['file']) {
            $senderConfig = new Definition('MCD\ZabbixMonitoringBundle\Zabbix\Agent\Config');
            $senderConfig->addArgument($config['file']);
            $sender->addMethodCall('importAgentConfig', array($senderConfig));
        } else {
            $sender->addArgument($config['host'] ?: 'localhost');
            $sender->addArgument((int) $config['port'] ?: 10051);
        }

        $definition->replaceArgument(0, $sender);
        $definition->replaceArgument(1, $config['prefix']);

        return $definition;
    }
}
