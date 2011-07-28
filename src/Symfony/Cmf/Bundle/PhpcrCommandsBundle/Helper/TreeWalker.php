<?php

/*
 * This file is part of the Symfony/Cmf/PhpcrCommandsBundle
 *
 * (c) Daniel Barsotti <daniel.barsotti@liip.ch>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Cmf\Bundle\PhpcrCommandsBundle\Helper;

use PHPCR\ItemVisitorInterface;
use Jackalope\Node;
use Jackalope\Property;

class TreeWalker
{
    /**
     * @var ItemVisitorInterface
     */
    protected $node_visitor;

    /**
     * @var ItemVisitorInterface
     */
    protected $property_visitor;

    /**
     * @var array()
     */
    protected $node_filters = array();

    /**
     * @var array()
     */
    protected $property_filters = array();

    /**
     * @param ItemVisitorInterface $node_visitor The visitor for the nodes
     * @param ItemVisitorInterface $property_visitor The visitor for the nodes properties
     */
    public function __construct(ItemVisitorInterface $node_visitor, ItemVisitorInterface $property_visitor = null)
    {
        $this->node_visitor = $node_visitor;
        $this->property_visitor = $property_visitor;
    }

    /**
     * Add a filter to select the nodes that will be traversed
     * @param TreeWalkerFilterInterface $filter
     */
    public function addNodeFilter(TreeWalkerFilterInterface $filter)
    {
        if (!array_search($filter, $this->node_filters)) {
            $this->node_filters[] = $filter;
        }
    }

    /**
     * Add a filter to select the properties that will be traversed
     * @param TreeWalkerFilterInterface $filter
     */
    public function addPropertyFilter(TreeWalkerFilterInterface $filter)
    {
        if (!array_search($filter, $this->property_filters)) {
            $this->property_filters[] = $filter;
        }
    }

    /**
     * Return whether a node must be traversed or not
     * @param Node $node
     * @return boolean
     */
    protected function mustVisitNode(Node $node)
    {
        foreach($this->node_filters as $filter) {
            if (! $filter->must_visit($node)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return whether a node property must be traversed or not
     * @param Property $property
     * @return boolean
     */
    protected function mustVisitProperty(Property $property)
    {
        foreach($this->property_filters as $filter) {
            if (! $filter->must_visit($property)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Traverse a node
     * @param Node $node
     * @param int $level Recursion level
     */
    public function traverse(Node $node, $level = 0)
    {
        if ($this->mustVisitNode($node)) {

            // Visit node
            $this->node_visitor->setLevel($level);
            $node->accept($this->node_visitor);

            // Visit properties
            if ($this->property_visitor !== null) {
                foreach ($node->getProperties() as $prop) {
                    if ($this->mustVisitProperty($prop)) {
                        $this->property_visitor->setLevel($level);
                        $prop->accept($this->property_visitor);
                    }
                }
            }

            // Visit children
            foreach($node->getNodes() as $child) {
                $this->traverse($child, $level + 1);
            }
        }
    }
}