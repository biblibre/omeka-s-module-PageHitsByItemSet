<?php
namespace PageHitsByItemSet\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use PageHitsByItemSet\Controller\Admin\IndexController;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $connection = $services->get('Omeka\Connection');

        $controller = new IndexController($connection);

        return $controller;
    }
}
