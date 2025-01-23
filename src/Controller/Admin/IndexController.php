<?php

namespace PageHitsByItemSet\Controller\Admin;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Stdlib\Paginator;

class IndexController extends AbstractActionController
{
    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function browseAction()
    {
        $this->setBrowseDefaults('hits_self');

        $page = $this->params()->fromQuery('page');
        $per_page = $this->params()->fromQuery('per_page', $this->settings()->get('pagination_per_page', Paginator::PER_PAGE));
        $sort_by = $this->params()->fromQuery('sort_by');
        $sort_order = $this->params()->fromQuery('sort_order');
        $year = $this->params()->fromQuery('year');
        $month = $this->params()->fromQuery('month');

        $sql = 'SELECT item_set_id, SUM(hits_self) hits_self, SUM(hits_inclusive) hits_inclusive FROM page_hits_by_item_set_hits_aggregate';

        $sql_conditions = [];
        $sql_conditions_bind_values = [];
        $sql_conditions_types = [];
        if ($year) {
            $sql_conditions[] = 'year = ?';
            $sql_conditions_bind_values[] = $year;
            $sql_conditions_types[] = ParameterType::INTEGER;
        }
        if ($month) {
            $sql_conditions[] = 'month = ?';
            $sql_conditions_bind_values[] = $month;
            $sql_conditions_types[] = ParameterType::INTEGER;
        }
        if ($sql_conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $sql_conditions);
        }

        $sql .= ' GROUP BY item_set_id';
        $sql .= ' ORDER BY ' . $this->connection->quoteIdentifier($sort_by) . ' ' . ($sort_order === 'asc' ? 'asc' : 'desc');
        $sql .= ' LIMIT ?, ?';
        $sql_limit_bind_values = [
            (int) $per_page * ($page - 1),
            (int) $per_page,
        ];
        $sql_limit_types = [
            ParameterType::INTEGER,
            ParameterType::INTEGER,
        ];

        $itemSetsHitsTotals = $this->connection->fetchAllAssociative(
            $sql,
            array_merge($sql_conditions_bind_values, $sql_limit_bind_values),
            array_merge($sql_conditions_types, $sql_limit_types)
        );

        $count_sql = 'SELECT COUNT(DISTINCT item_set_id) FROM page_hits_by_item_set_hits_aggregate';
        if ($sql_conditions) {
            $count_sql .= ' WHERE ' . implode(' AND ', $sql_conditions);
        }
        $totalResults = $this->connection->fetchOne($count_sql, $sql_conditions_bind_values, $sql_conditions_types);

        $years = $this->connection->fetchAllKeyValue('SELECT year, year FROM page_hits_by_item_set_hits_aggregate GROUP BY year ORDER BY year');

        $this->paginator($totalResults);

        $view = new ViewModel;
        $view->setVariable('itemSetsHitsTotals', $itemSetsHitsTotals);
        $view->setVariable('years', $years);

        return $view;
    }
}
