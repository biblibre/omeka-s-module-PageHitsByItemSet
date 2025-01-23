<?php
namespace PageHitsByItemSet\Service\Mvc;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use PageHitsByItemSet\Mvc\MvcListeners;

class MvcListenersFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $service = new MvcListeners($services);

        return $service;
    }
}
