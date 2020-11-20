<?php

/**
 * EasyRdf
 *
 * LICENSE
 *
 * Copyright (c) 2013 Nicholas J Humfrey.  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 * 3. The name of the author 'Nicholas J Humfrey" may be used to endorse or
 *    promote products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */

/**
 * Sub-class of EasyRdf_Resource that represents an RDF collection (rdf:List)
 *
 * This class can be used to iterate through a collection of items.
 *
 * Note that items are numbered from 1 (not 0) for consistency with RDF Containers.
 *
 * @package    EasyRdf
 * @link       http://www.w3.org/TR/xmlschema-2/#date
 * @copyright  Copyright (c) 2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Collection extends EasyRdf_Resource implements ArrayAccess, Countable, SeekableIterator
{
    private $position;
    private $current;

    /** Create a new collection - do not use this directly
     *
     * @ignore
     */
    public function __construct($uri, $graph)
    {
        $this->position = 1;
        $this->current = null;
        parent::__construct($uri, $graph);
    }

    /** Seek to a specific position in the container
     *
     * The first item is postion 1
     *
     * @param  integer  $position     The position in the container to seek to
     * @throws OutOfBoundsException
     */
    public function seek($position)
    {
        if (is_int($position) and $position > 0) {
            list($node, $actual) = $this->getCollectionNode($position);
            if ($actual === $position) {
                $this->position = $actual;
                $this->current = $node;
            } else {
                throw new OutOfBoundsException(
                    "Unable to seek to position $position in the collection"
                );
            }
        } else {
            throw new InvalidArgumentException(
                "Collection position must be a positive integer"
            );
        }
    }

    /** Rewind the iterator back to the start of the collection
     *
     */
    public function rewind()
    {
        $this->position = 1;
        $this->current = null;
    }

    /** Return the current item in the collection
     *
     * @return mixed The current item
     */
    public function current()
    {
        if ($this->position === 1) {
            return $this->get('rdf:first');
        } elseif ($this->current) {
            return $this->current->get('rdf:first');
        }
    }

    /** Return the key / current position in the collection
     *
     * Note: the first item is number 1
     *
     * @return int The current position
     */
    public function key()
    {
        return $this->position;
    }

    /** Move forward to next item in the collection
     *
     */
    public function next()
    {
        if ($this->position === 1) {
            $this->current = $this->get('rdf:rest');
        } elseif ($this->current) {
            $this->current = $this->current->get('rdf:rest');
        }
        $this->position++;
    }

    /** Checks if current position is valid
     *
     * @return bool True if the current position is valid
     */
    public function valid()
    {
        if ($this->position === 1 and $this->hasProperty('rdf:first')) {
            return true;
        } elseif ($this->current !== null and $this->current->hasProperty('rdf:first')) {
            return true;
        } else {
            return false;
        }
    }

    /** Get a node for a particular offset into the collection
     *
     * This function may not return the item you requested, if
     * it does not exist. Please check the $postion parameter
     * returned.
     *
     * If the offset is null, then the last node in the
     * collection (before rdf:nil) will be returned.
     *
     * @param  integer $offset          The offset into the collection (or null)
     * @return array   $node, $postion  The node object and postion of the node
     */
    public function getCollectionNode($offset)
    {
        $position = 1;
        $node = $this;
        $nil = $this->graph->resource('rdf:nil');
        while (($rest = $node->get('rdf:rest')) and $rest !== $nil and (is_null($offset) or ($position < $offset))) {
            $node = $rest;
            $position++;
        }
        return array($node, $position);
    }

    /** Counts the number of items in the collection
     *
     * Note that this is an slow method - it is more efficient to use
     * the iterator interface, if you can.
     *
     * @return integer The number of items in the collection
     */
    public function count()
    {
        // Find the end of the collection
        list($node, $position) = $this->getCollectionNode(null);
        if (!$node->hasProperty('rdf:first')) {
            return 0;
        } else {
            return $position;
        }
    }

    /** Append an item to the end of the collection
     *
     * @param  mixed $value      The value to append
     * @return integer           The number of values appended (1 or 0)
     */
    public function append($value)
    {
        // Find the end of the collection
        list($node, $position) = $this->getCollectionNode(null);
        $rest = $node->get('rdf:rest');

        if ($node === $this and is_null($rest)) {
            $node->set('rdf:first', $value);
            $node->addResource('rdf:rest', 'rdf:nil');
        } else {
            $new = $this->graph->newBnode();
            $node->set('rdf:rest', $new);
            $new->add('rdf:first', $value);
            $new->addResource('rdf:rest', 'rdf:nil');
        }

        return 1;
    }

    /** Array Access: check if a position exists in collection using array syntax
     *
     * Example: isset($list[2])
     */
    public function offsetExists($offset)
    {
        if (is_int($offset) and $offset > 0) {
            list($node, $position) = $this->getCollectionNode($offset);
            return ($node and $position === $offset and $node->hasProperty('rdf:first'));
        } else {
            throw new InvalidArgumentException(
                "Collection offset must be a positive integer"
            );
        }
    }

    /** Array Access: get an item at a specified position in collection using array syntax
     *
     * Example: $item = $list[2];
     */
    public function offsetGet($offset)
    {
        if (is_int($offset) and $offset > 0) {
            list($node, $position) = $this->getCollectionNode($offset);
            if ($node and $position === $offset) {
                return $node->get('rdf:first');
            }
        } else {
            throw new InvalidArgumentException(
                "Collection offset must be a positive integer"
            );
        }
    }

    /**
     * Array Access: set an item at a positon in collection using array syntax
     *
     * Example: $list[2] = $item;
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            // No offset - append to end of collection
            $this->append($value);
        } elseif (is_int($offset) and $offset > 0) {
            list($node, $position) = $this->getCollectionNode($offset);

            // Create nodes, if they are missing
            while ($position < $offset) {
                $new = $this->graph->newBnode();
                $node->set('rdf:rest', $new);
                $new->addResource('rdf:rest', 'rdf:nil');
                $node = $new;
                $position++;
            }

            // Terminate the list
            if (!$node->hasProperty('rdf:rest')) {
                $node->addResource('rdf:rest', 'rdf:nil');
            }

            return $node->set('rdf:first', $value);
        } else {
            throw new InvalidArgumentException(
                "Collection offset must be a positive integer"
            );
        }
    }

    /**
     * Array Access: delete an item at a specific postion using array syntax
     *
     * Example: unset($seq[2]);
     */
    public function offsetUnset($offset)
    {
        if (is_int($offset) and $offset > 0) {
            list($node, $position) = $this->getCollectionNode($offset);
        } else {
            throw new InvalidArgumentException(
                "Collection offset must be a positive integer"
            );
        }

        // Does the item exist?
        if ($node and $position === $offset) {
            $nil = $this->graph->resource('rdf:nil');
            if ($position === 1) {
                $rest = $node->get('rdf:rest');
                if ($rest and $rest !== $nil) {
                    // Move second value, so we can keep the head of list
                    $node->set('rdf:first', $rest->get('rdf:first'));
                    $node->set('rdf:rest', $rest->get('rdf:rest'));
                    $rest->delete('rdf:first');
                    $rest->delete('rdf:rest');
                } else {
                    // Just remove the value
                    $node->delete('rdf:first');
                    $node->delete('rdf:rest');
                }
            } else {
                // Remove the value and re-link the list
                $node->delete('rdf:first');
                $rest = $node->get('rdf:rest');
                $previous = $node->get('^rdf:rest');
                if (is_null($rest)) {
                    $rest = $nil;
                }
                if ($previous) {
                    $previous->set('rdf:rest', $rest);
                }
            }
        }
    }
}
