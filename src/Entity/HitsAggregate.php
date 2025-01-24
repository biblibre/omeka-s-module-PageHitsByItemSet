<?php

namespace PageHitsByItemSet\Entity;

use Omeka\Entity\AbstractEntity;

/**
 * @Entity
 * @Table(
 *      name="page_hits_by_item_set_hits_aggregate",
 *      uniqueConstraints={
 *          @UniqueConstraint(name="item_set_year_month", fields={"itemSet", "year", "month"})
 *      }
 * )
 */
class HitsAggregate extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Omeka\Entity\ItemSet")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $itemSet;

    /**
     * @Column(type="integer")
     */
    protected $year;

    /**
     * @Column(type="integer")
     */
    protected $month;

    /**
     * @Column(type="integer")
     */
    protected $hits;

    public function getId()
    {
        return $this->id;
    }
}
