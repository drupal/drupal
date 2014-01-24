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
 * Functions for comparing two graphs with each other
 *
 * Based on rdf-isomorphic.rb by Ben Lavender:
 * https://github.com/ruby-rdf/rdf-isomorphic
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Isomorphic
{
    /**
     * Check if one graph is isomorphic (equal) to another graph
     *
     * For example:
     *    $graphA = EasyRdf_Graph::newAndLoad('http://example.com/a.ttl');
     *    $graphB = EasyRdf_Graph::newAndLoad('http://example.com/b.ttl');
     *    if (EasyRdf_Isomorphic::isomorphic($graphA, $graphB)) print "Equal!";
     *
     * @param  object EasyRdf_Graph  $graphA  The first graph to be compared
     * @param  object EasyRdf_Graph  $graphB  The second graph to be compared
     * @return boolean True if the two graphs are isomorphic
     */
    public static function isomorphic($graphA, $graphB)
    {
        return is_array(self::bijectionBetween($graphA, $graphB));
    }

    /**
     * Returns an associative array of bnode identifiers representing an isomorphic
     * bijection of one EasyRdf_Graph to another EasyRdf_Graph's blank nodes or
     * null if a bijection cannot be found.
     *
     * @param  object EasyRdf_Graph  $graphA  The first graph to be compared
     * @param  object EasyRdf_Graph  $graphB  The second graph to be compared
     * @return array bnode mapping from $graphA to $graphB
     */
    public static function bijectionBetween($graphA, $graphB)
    {
        $bnodesA = array();
        $bnodesB = array();
        $statementsA = array();
        $statementsB = array();

        // Quick initial check: are there differing numbers of subjects?
        if (self::countSubjects($graphA) != self::countSubjects($graphB)) {
            return null;
        }

        // Check if all the statements in Graph A exist in Graph B
        $groundedStatementsMatch = self::groundedStatementsMatch($graphA, $graphB, $bnodesA, $statementsA);

        if ($groundedStatementsMatch) {
            // Check if all the statements in Graph B exist in Graph A
            $groundedStatementsMatch = self::groundedStatementsMatch($graphB, $graphA, $bnodesB, $statementsB);
        }

        if ($groundedStatementsMatch === false) {
            // The grounded statements do not match
            return null;
        } elseif (count($bnodesA) > 0 or count($bnodesB > 0)) {
            // There are blank nodes - build a bi-jection
            return self::buildBijectionTo($statementsA, $bnodesA, $statementsB, $bnodesB);
        } else {
            // No bnodes and the grounded statements match
            return array();
        }
    }

    /**
     * Count the number of subjects in a graph
     * @ignore
     */
    private static function countSubjects($graph)
    {
        return count($graph->toRdfPhp());
    }

    /**
     * Check if all the statements in $graphA also appear in $graphB
     * @ignore
     */
    private static function groundedStatementsMatch($graphA, $graphB, &$bnodes, &$anonStatements)
    {
        $groundedStatementsMatch = true;

        foreach ($graphA->toRdfPhp() as $subject => $properties) {
            if (substr($subject, 0, 2) == '_:') {
                array_push($bnodes, $subject);
                $subjectIsBnode = true;
            } else {
                $subjectIsBnode = false;
            }

            foreach ($properties as $property => $values) {
                foreach ($values as $value) {
                    if ($value['type'] == 'uri' and substr($value['value'], 0, 2) == '_:') {
                        array_push($bnodes, $value['value']);
                        $objectIsBnode = true;
                    } else {
                        $objectIsBnode = false;
                    }

                    if ($groundedStatementsMatch and
                        $subjectIsBnode === false and
                        $objectIsBnode === false and
                        $graphB->hasProperty($subject, $property, $value) === false
                    ) {
                        $groundedStatementsMatch = false;
                    }

                    if ($subjectIsBnode or $objectIsBnode) {
                        array_push(
                            $anonStatements,
                            array(
                                array('type' => $subjectIsBnode ? 'bnode' : 'uri', 'value' => $subject),
                                array('type' => 'uri', 'value' => $property),
                                $value
                            )
                        );
                    }
                }
            }
        }

        return $groundedStatementsMatch;
    }

    /**
     * The main recursive bijection algorithm.
     *
     * This algorithm is very similar to the one explained by Jeremy Carroll in
     * http://www.hpl.hp.com/techreports/2001/HPL-2001-293.pdf. Page 12 has the
     * relevant pseudocode.
     *
     * @ignore
     */
    private static function buildBijectionTo
    (
        $statementsA,
        $nodesA,
        $statementsB,
        $nodesB,
        $groundedHashesA = array(),
        $groundedHashesB = array()
    ) {

        // Create a hash signature of every node, based on the signature of
        // statements it exists in.
        // We also save hashes of nodes that cannot be reliably known; we will use
        // that information to eliminate possible recursion combinations.
        //
        // Any mappings given in the method parameters are considered grounded.
        list($hashesA, $ungroundedHashesA) = self::hashNodes($statementsA, $nodesA, $groundedHashesA);
        list($hashesB, $ungroundedHashesB) = self::hashNodes($statementsB, $nodesB, $groundedHashesB);

        // Grounded hashes are built at the same rate between the two graphs (if
        // they are isomorphic).  If there exists a grounded node in one that is
        // not in the other, we can just return.  Ungrounded nodes might still
        // conflict, so we don't check them.  This is a little bit messy in the
        // middle of the method, and probably slows down isomorphic checks,  but
        // prevents almost-isomorphic cases from getting nutty.
        foreach ($hashesA as $nodeA => $hashA) {
            if (!in_array($hashA, $hashesB)) {
                return null;
            }
        }
        foreach ($hashesB as $nodeB => $hashB) {
            if (!in_array($hashB, $hashesA)) {
                return null;
            }
        }

        // Using the created hashes, map nodes to other_nodes
        // Ungrounded hashes will also be equal, but we keep the distinction
        // around for when we recurse later (we only recurse on ungrounded nodes)
        $bijection = array();
        foreach ($nodesA as $nodeA) {
            $foundNode = null;
            foreach ($ungroundedHashesB as $nodeB => $hashB) {
                if ($ungroundedHashesA[$nodeA] == $hashB) {
                    $foundNode = $nodeB;
                }
            }

            if ($foundNode) {
                $bijection[$nodeA] = $foundNode;

                // Deletion is required to keep counts even; two nodes with identical
                // signatures can biject to each other at random.
                unset($ungroundedHashesB[$foundNode]);
            }
        }

        // bijection is now a mapping of nodes to other_nodes.  If all are
        // accounted for on both sides, we have a bijection.
        //
        // If not, we will speculatively mark pairs with matching ungrounded
        // hashes as bijected and recurse.
        $bijectionA = array_keys($bijection);
        $bijectionB = array_values($bijection);
        sort($bijectionA);
        sort($nodesA);
        sort($bijectionB);
        sort($nodesB);
        if ($bijectionA != $nodesA or $bijectionB != $nodesB) {
            $bijection = null;

            foreach ($nodesA as $nodeA) {

                // We don't replace grounded nodes' hashes
                if (isset($hashesA[$nodeA])) {
                    continue;
                }

                foreach ($nodesB as $nodeB) {
                    // We don't replace grounded nodesB's hashes
                    if (isset($hashesB[$nodeB])) {
                        continue;
                    }

                    // The ungrounded signature must match for this to potentially work
                    if ($ungroundedHashesA[$nodeA] != $ungroundedHashesB[$nodeB]) {
                        continue;
                    }

                    $hash = sha1($nodeA);
                    $hashesA[$nodeA] = $hash;
                    $hashesB[$nodeB] = $hash;
                    $bijection = self::buildBijectionTo(
                        $statementsA,
                        $nodesA,
                        $statementsB,
                        $nodesA,
                        $hashesA,
                        $hashesB
                    );
                }
            }
        }

        return $bijection;
    }

    /**
     * Given a set of statements, create a mapping of node => SHA1 for a given
     * set of blank nodes.  grounded_hashes is a mapping of node => SHA1 pairs
     * that we will take as a given, and use those to make more specific
     * signatures of other nodes.
     *
     * Returns a tuple of associative arrats: one of grounded hashes, and one of all
     * hashes.  grounded hashes are based on non-blank nodes and grounded blank
     * nodes, and can be used to determine if a node's signature matches
     * another.
     *
     * @ignore
     */
    private static function hashNodes($statements, $nodes, $groundedHahes)
    {
        $hashes = $groundedHahes;
        $ungroundedHashes = array();
        $hashNeeded = true;

        // We may have to go over the list multiple times.  If a node is marked as
        // grounded, other nodes can then use it to decide their own state of
        // grounded.
        while ($hashNeeded) {
            $startingGroundedNodes = count($hashes);
            foreach ($nodes as $node) {
                if (!isset($hashes[$node])) {
                    $hash = self::nodeHashFor($node, $statements, $hashes);
                    if (self::nodeIsGrounded($node, $statements, $hashes)) {
                        $hashes[$node] = $hash;
                    }
                }
                $ungroundedHashes[$node] = $hash;
            }

            // after going over the list, any nodes with a unique hash can be marked
            // as grounded, even if we have not tied them back to a root yet.
            $uniques = array();
            foreach ($ungroundedHashes as $node => $hash) {
                $uniques[$hash] = isset($uniques[$hash]) ? false : $node;
            }
            foreach ($uniques as $hash => $node) {
                if ($node) {
                    $hashes[$node] = $hash;
                }
            }
            $hashNeeded = ($startingGroundedNodes != count($hashes));
        }

        return array($hashes, $ungroundedHashes);
    }

    /**
     * Generate a hash for a node based on the signature of the statements it
     * appears in.  Signatures consist of grounded elements in statements
     * associated with a node, that is, anything but an ungrounded anonymous
     * node.  Creating the hash is simply hashing a sorted list of each
     * statement's signature, which is itself a concatenation of the string form
     * of all grounded elements.
     *
     * Nodes other than the given node are considered grounded if they are a
     * member in the given hash.
     *
     * Returns a tuple consisting of grounded being true or false and the string
     * for the hash
     *
     * @ignore
     */
    private static function nodeHashFor($node, $statements, $hashes)
    {
        $statement_signatures = array();
        foreach ($statements as $statement) {
            foreach ($statement as $n) {
                if ($n['type'] != 'literal' and $n['value'] == $node) {
                    array_push(
                        $statement_signatures,
                        self::hashStringFor($statement, $hashes, $node)
                    );
                }
            }
        }

        // Note that we sort the signatures--without a canonical ordering,
        // we might get different hashes for equivalent nodes
        sort($statement_signatures);

        // Convert statements into one long string and hash it
        return sha1(implode('', $statement_signatures));
    }

    /**
     * Returns true if a given node is grounded
     * A node is groundd if it is not a blank node or it is included
     * in the given mapping of grounded nodes.
     *
     * @ignore
     */
    private static function nodeIsGrounded($node, $statements, $hashes)
    {
        $grounded = true;
        foreach ($statements as $statement) {
            if (in_array($node, $statement)) {
                foreach ($statement as $resource) {
                    if ($node['type'] != 'bnode' or
                        isset($hashes[$node['value']]) or
                        $resource == $node
                    ) {
                        $grounded = false;
                    }
                }
            }
        }
        return $grounded;
    }

    /**
     * Provide a string signature for the given statement, collecting
     * string signatures for grounded node elements.
     *
     * @ignore
     */
    private static function hashStringFor($statement, $hashes, $node)
    {
        $str = "";
        foreach ($statement as $r) {
            $str .= self::stringForNode($r, $hashes, $node);
        }
        return $str;
    }

    /**
     * Provides a string for the given node for use in a string signature
     * Non-anonymous nodes will return their string form.  Grounded anonymous
     * nodes will return their hashed form.
     *
     * @ignore
     */
    private static function stringForNode($node, $hashes, $target)
    {
        if (is_null($node)) {
            return "";
        } elseif ($node['type'] == 'bnode') {
            if ($node['value'] == $target) {
                return "itself";
            } elseif (isset($hashes[$node['value']])) {
                return $hashes[$node['value']];
            } else {
                return "a blank node";
            }
        } else {
            $s = new EasyRdf_Serialiser_Ntriples();
            return $s->serialiseValue($node);
        }
    }
}
