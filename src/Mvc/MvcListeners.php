<?php

namespace PageHitsByItemSet\Mvc;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use ItemSetsTree\Service\ItemSetsTree;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Api\Adapter\Manager as ApiAdapterManager;
use Omeka\Entity\Item;
use Omeka\Entity\Media;

class MvcListeners extends AbstractListenerAggregate
{
    protected ServiceLocatorInterface $serviceLocator;

    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_RENDER,
            [$this, 'onRender']
        );
    }

    public function onRender(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();

        if ($routeMatch && $routeMatch->getMatchedRouteName() === 'site/resource-id') {
            $id = $routeMatch->getParam('id');

            $resource = $this->getEntityManager()->find('Omeka\Entity\Resource', $id);
            if ($resource instanceof Media) {
                $resource = $resource->getItem();
            }

            if ($resource instanceof Item) {
                $connection = $this->getConnection();
                $itemSets = $resource->getItemSets()->toArray();

                foreach ($itemSets as $itemSet) {
                    $connection->executeStatement(
                        <<<SQL
                        INSERT INTO page_hits_by_item_set_hits_aggregate (item_set_id, year, month, hits)
                        VALUES (?, YEAR(CURDATE()), MONTH(CURDATE()), 1)
                        ON DUPLICATE KEY UPDATE hits = hits + 1
                        SQL,
                        [$itemSet->getId()]
                    );
                }
            }
        }
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->serviceLocator->get('Omeka\EntityManager');
    }

    protected function getConnection(): Connection
    {
        return $this->serviceLocator->get('Omeka\Connection');
    }

    protected function getApiAdapterManager(): ApiAdapterManager
    {
        return $this->serviceLocator->get('Omeka\ApiAdapterManager');
    }
}
