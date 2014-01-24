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
 * Sub-class of EasyRdf_Resource that represents an RDF container
 * (rdf:Alt, rdf:Bag and rdf:Seq)
 *
 * This class can be used to iterate through a list of items.
 *
 * @package    EasyRdf
 * @link       http://www.w3.org/TR/xmlschema-2/#date
 * @copyright  Copyright (c) 2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Container extends EasyRdf_Resource implements ArrayAccess, Countable, SeekableIterator
{
    private $position;

    /** Create a new container - do not use this directly
     *
     * @ignore
     */
    public function __construct($uri, $graph)
    {
        $this->position = 1;
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
            if ($this->hasProperty('rdf:_'.$position)) {
                $this->position = $position;
            } else {
                throw new OutOfBoundsException(
                    "Unable to seek to position $position in the container"
                );
            }
        } else {
            throw new InvalidArgumentException(
                "Container position must be a positive integer"
            );
        }
    }

    /** Rewind the iterator back to the start of the container (item 1)
     *
     */
    public function rewind()
    {
        $this->position = 1;
    }

    /** Return the current item in the container
     *
     * @return mixed The current item
     */
    public function current()
    {
        return $this->get('rdf:_'.$this->position);
    }

    /** Return the key / current position in the container
     *
     * @return int The current position
     */
    public function key()
    {
        return $this->position;
    }

    /** Move forward to next item in the container
     *
     */
    public function next()
    {
        $this->position++;
    }

    /** Checks if current position is valid
     *
     * @return bool True if the current position is valid
     */
    public function valid()
    {
        return $this->hasProperty('rdf:_'.$this->position);
    }

    /** Counts the number of items in the container
     *
     * Note that this is an slow method - it is more efficient to use
     * the iterator interface, if you can.
     *
     * @return integer The number of items in the container
     */
    public function count()
    {
        $pos = 1;
        while ($this->hasProperty('rdf:_'.$pos)) {
            $pos++;
        }
        return $pos - 1;
    }

    /** Append an item to the end of the container
     *
     * @param  mixed $value      The value to append
     * @return integer           The number of values appended (1 or 0)
     */
    public function append($value)
    {
        // Find the end of the list
        $pos = 1;
        while ($this->hasProperty('rdf:_'.$pos)) {
            $pos++;
        }

        // Add the item
        return $this->add('rdf:_'.$pos, $value);
    }

    /** Array Access: check if a position exists in container using array syntax
     *
     * Example: isset($seq[2])
     */
    public function offsetExists($offset)
    {
        if (is_int($offset) and $offset > 0) {
            return $this->hasProperty('rdf:_'.$offset);
        } else {
            throw new InvalidArgumentException(
                "Container position must be a positive integer"
            );
        }
    }

    /** Array Access: get an item at a specified position in container using array syntax
     *
     * Example: $item = $seq[2];
     */
    public function offsetGet($offset)
    {
        if (is_int($offset) and $offset > 0) {
            return $this->get('rdf:_'.$offset);
        } else {
            throw new InvalidArgumentException(
                "Container position must be a positive integer"
            );
        }
    }

    /**
     * Array Access: set an item at a positon in container using array syntax
     *
     * Example: $seq[2] = $item;
     *
     * Warning: creating gaps in the sequence will result in unexpected behavior
     */
    public function offsetSet($offset, $value)
    {
        if (is_int($offset) and $offset > 0) {
            return $this->set('rdf:_'.$offset, $value);
        } elseif (is_null($offset)) {
            return $this->append($value);
        } else {
            throw new InvalidArgumentException(
                "Container position must be a positive integer"
            );
        }
    }

    /**
     * Array Access: delete an item at a specific postion using array syntax
     *
     * Example: unset($seq[2]);
     *
     * Warning: creating gaps in the sequence will result in unexpected behavior
     */
    public function offsetUnset($offset)
    {
        if (is_int($offset) and $offset > 0) {
            return $this->delete('rdf:_'.$offset);
        } else {
            throw new InvalidArgumentException(
                "Container position must be a positive integer"
            );
        }
    }
}
