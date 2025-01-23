<?php

namespace PageHitsByItemSet;

use Omeka\Module\AbstractModule;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');

        $connection->executeStatement(<<<SQL
            CREATE TABLE page_hits_by_item_set_hits_aggregate (
                id INT AUTO_INCREMENT NOT NULL,
                item_set_id INT NOT NULL,
                year INT NOT NULL,
                month INT NOT NULL,
                hits_self INT NOT NULL,
                hits_inclusive INT NOT NULL,
                INDEX IDX_686DAA85960278D7 (item_set_id),
                UNIQUE INDEX item_set_year_month (item_set_id, year, month),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
    }

    public function handleConfigForm(AbstractController $controller)
    {
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
    }

    public function getConfig()
    {
        return require __DIR__ . '/config/module.config.php';
    }
}
