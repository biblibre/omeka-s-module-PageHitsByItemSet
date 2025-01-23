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
        $matchedRouteName = $routeMatch->getMatchedRouteName();

        if ($matchedRouteName === 'site/resource-id') {
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
                        INSERT INTO page_hits_by_item_set_hits_aggregate (item_set_id, year, month, hits_self, hits_inclusive)
                        VALUES (?, YEAR(CURDATE()), MONTH(CURDATE()), 1, 1)
                        ON DUPLICATE KEY UPDATE
                            hits_self = hits_self + 1,
                            hits_inclusive = hits_inclusive + 1
                        SQL,
                        [$itemSet->getId()]
                    );
                }

                $itemSetsTree = $this->getItemSetsTree();
                if ($itemSetsTree) {
                    $itemSetAdapter = $this->getApiAdapterManager()->get('item_sets');
                    foreach ($itemSets as $itemSet) {
                        $itemSetRepresentation = $itemSetAdapter->getRepresentation($itemSet);
                        $ancestors = $itemSetsTree->getAncestors($itemSetRepresentation);

                        foreach ($ancestors as $ancestor) {
                            $connection->executeStatement(
                                <<<SQL
                                INSERT INTO page_hits_by_item_set_hits_aggregate (item_set_id, year, month, hits_self, hits_inclusive)
                                VALUES (?, YEAR(CURDATE()), MONTH(CURDATE()), 0, 1)
                                ON DUPLICATE KEY UPDATE hits_inclusive = hits_inclusive + 1
                                SQL,
                                [$ancestor->id()]
                            );
                        }
                    }
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

    protected function getItemSetsTree(): ?ItemSetsTree
    {
        try {
            $itemSetsTree = $this->serviceLocator->get('ItemSetsTree');
        } catch (\Throwable) {
            $itemSetsTree = null;
        }

        return $itemSetsTree;
    }
}
