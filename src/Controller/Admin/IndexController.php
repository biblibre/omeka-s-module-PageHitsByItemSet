<?php

namespace PageHitsByItemSet\Controller\Admin;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Stdlib\Paginator;
use Omeka\Module\Manager as ModuleManager;

class IndexController extends AbstractActionController
{
    protected Connection $connection;
    protected ModuleManager $moduleManager;

    public function __construct(Connection $connection, ModuleManager $moduleManager)
    {
        $this->connection = $connection;
        $this->moduleManager = $moduleManager;
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
            $join_condition = ' AND ' . implode(' AND ', $sql_conditions);
        } else {
            $join_condition = '';
        }

        if ($this->isModuleActive('ItemSetsTree')) {
            $cte = <<<SQL
                with recursive hits (item_set_id, parent_item_set_id, hits_self, hits_inclusive) as (
                    select item_set.id, item_sets_tree_edge.parent_item_set_id, coalesce(h.hits, 0), coalesce(h.hits, 0)
                    from item_set
                    left join item_sets_tree_edge on (item_sets_tree_edge.item_set_id = item_set.id)
                    left join page_hits_by_item_set_hits_aggregate h on (item_set.id = h.item_set_id $join_condition)
                    where not exists (select * from item_sets_tree_edge where item_sets_tree_edge.parent_item_set_id = item_set.id)
                    union
                    select item_set.id, item_sets_tree_edge.parent_item_set_id, coalesce(h.hits, 0), coalesce(h.hits, 0) + hits.hits_inclusive
                    from item_set
                    left join item_sets_tree_edge on (item_sets_tree_edge.item_set_id = item_set.id)
                    left join page_hits_by_item_set_hits_aggregate h on (item_set.id = h.item_set_id $join_condition)
                    join hits on (hits.parent_item_set_id = item_set.id)
                )
            SQL;
            $cte_bind_values = array_merge($sql_conditions_bind_values, $sql_conditions_bind_values);
            $cte_types = array_merge($sql_conditions_types, $sql_conditions_types);
        } else {
            $cte = <<<SQL
                with hits (item_set_id, hits_self, hits_inclusive) as (
                    select item_set.id, coalesce(h.hits, 0), coalesce(h.hits, 0)
                    from item_set
                    left join page_hits_by_item_set_hits_aggregate h on (item_set.id = h.item_set_id $join_condition)
                )
            SQL;
            $cte_bind_values = $sql_conditions_bind_values;
            $cte_types = $sql_conditions_types;
        }

        $sql = "$cte SELECT item_set_id, SUM(hits_self) hits_self, SUM(hits_inclusive) hits_inclusive FROM hits";

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
            array_merge($cte_bind_values, $sql_limit_bind_values),
            array_merge($cte_types, $sql_limit_types)
        );

        $count_sql = "$cte SELECT COUNT(DISTINCT item_set_id) FROM hits";
        $totalResults = $this->connection->fetchOne($count_sql, $cte_bind_values, $cte_types);

        $years = $this->connection->fetchAllKeyValue('SELECT year, year FROM page_hits_by_item_set_hits_aggregate GROUP BY year ORDER BY year');

        $this->paginator($totalResults);

        $view = new ViewModel;
        $view->setVariable('itemSetsHitsTotals', $itemSetsHitsTotals);
        $view->setVariable('years', $years);

        return $view;
    }

    protected function isModuleActive($moduleName): bool
    {
        if (!$this->moduleManager->isRegistered($moduleName)) {
            return false;
        }

        return $this->moduleManager->getModule($moduleName)->getState() === \Omeka\Module\Manager::STATE_ACTIVE;
    }
}
