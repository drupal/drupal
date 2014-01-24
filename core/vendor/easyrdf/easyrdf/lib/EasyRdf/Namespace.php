<?php

/**
 * EasyRdf
 *
 * LICENSE
 *
 * Copyright (c) 2009-2013 Nicholas J Humfrey.  All rights reserved.
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
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */

/**
 * A namespace registry and manipulation class.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Namespace
{
    /** Namespace registry
     *
     * List of default namespaces come from:
     *  - http://www.w3.org/2011/rdfa-context/rdfa-1.1
     *
     * With a few extras added.
     *
     */
    private static $namespaces = array(
      'bibo' => 'http://purl.org/ontology/bibo/',
      'cc' => 'http://creativecommons.org/ns#',
      'cert' => 'http://www.w3.org/ns/auth/cert#',
      'ctag' => 'http://commontag.org/ns#',
      'dc' => 'http://purl.org/dc/terms/',
      'dc11' => 'http://purl.org/dc/elements/1.1/',
      'dcterms' => 'http://purl.org/dc/terms/',
      'doap' => 'http://usefulinc.com/ns/doap#',
      'exif' => 'http://www.w3.org/2003/12/exif/ns#',
      'foaf' => 'http://xmlns.com/foaf/0.1/',
      'geo' => 'http://www.w3.org/2003/01/geo/wgs84_pos#',
      'gr' => 'http://purl.org/goodrelations/v1#',
      'grddl' => 'http://www.w3.org/2003/g/data-view#',
      'ical' => 'http://www.w3.org/2002/12/cal/icaltzd#',
      'ma' => 'http://www.w3.org/ns/ma-ont#',
      'og' => 'http://ogp.me/ns#',
      'owl' => 'http://www.w3.org/2002/07/owl#',
      'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
      'rdfa' => 'http://www.w3.org/ns/rdfa#',
      'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
      'rev' => 'http://purl.org/stuff/rev#',
      'rif' => 'http://www.w3.org/2007/rif#',
      'rss' => 'http://purl.org/rss/1.0/',
      'schema' => 'http://schema.org/',
      'sioc' => 'http://rdfs.org/sioc/ns#',
      'skos' => 'http://www.w3.org/2004/02/skos/core#',
      'skosxl' => 'http://www.w3.org/2008/05/skos-xl#',
      'synd' => 'http://purl.org/rss/1.0/modules/syndication/',
      'v' => 'http://rdf.data-vocabulary.org/#',
      'vcard' => 'http://www.w3.org/2006/vcard/ns#',
      'void' => 'http://rdfs.org/ns/void#',
      'wdr' => 'http://www.w3.org/2007/05/powder#',
      'wdrs' => 'http://www.w3.org/2007/05/powder-s#',
      'wot' => 'http://xmlns.com/wot/0.1/',
      'xhv' => 'http://www.w3.org/1999/xhtml/vocab#',
      'xml' => 'http://www.w3.org/XML/1998/namespace',
      'xsd' => 'http://www.w3.org/2001/XMLSchema#',
    );

    private static $default = null;

    /** Counter for numbering anonymous namespaces */
    private static $anonymousNamespaceCount = 0;

    /**
      * Return all the namespaces registered
      *
      * @return array Associative array of all the namespaces.
      */
    public static function namespaces()
    {
        return self::$namespaces;
    }

    /**
      * Return a namespace given its prefix.
      *
      * @param string $prefix The namespace prefix (eg 'foaf')
      * @return string The namespace URI (eg 'http://xmlns.com/foaf/0.1/')
      */
    public static function get($prefix)
    {
        if (!is_string($prefix) or $prefix === null or $prefix === '') {
            throw new InvalidArgumentException(
                "\$prefix should be a string and cannot be null or empty"
            );
        }

        if (preg_match('/\W/', $prefix)) {
            throw new InvalidArgumentException(
                "\$prefix should only contain alpha-numeric characters"
            );
        }

        $prefix = strtolower($prefix);
        if (array_key_exists($prefix, self::$namespaces)) {
            return self::$namespaces[$prefix];
        } else {
            return null;
        }
    }

    /**
      * Register a new namespace.
      *
      * @param string $prefix The namespace prefix (eg 'foaf')
      * @param string $long The namespace URI (eg 'http://xmlns.com/foaf/0.1/')
      */
    public static function set($prefix, $long)
    {
        if (!is_string($prefix) or $prefix === null or $prefix === '') {
            throw new InvalidArgumentException(
                "\$prefix should be a string and cannot be null or empty"
            );
        }

        if (preg_match('/\W/', $prefix)) {
            throw new InvalidArgumentException(
                "\$prefix should only contain alpha-numeric characters"
            );
        }

        if (!is_string($long) or $long === null or $long === '') {
            throw new InvalidArgumentException(
                "\$long should be a string and cannot be null or empty"
            );
        }

        $prefix = strtolower($prefix);
        self::$namespaces[$prefix] = $long;
    }

    /**
      * Get the default namespace
      *
      * Returns the URI of the default namespace or null
      * if no default namespace is defined.
      *
      * @return string The URI of the default namespace
      */
    public static function getDefault()
    {
        return self::$default;
    }

    /**
      * Set the default namespace
      *
      * Set the default namespace to either a URI or the prefix of
      * an already defined namespace.
      *
      * Example:
      *   EasyRdf_Namespace::setDefault('http://schema.org/');
      *
      * @param string $namespace The URI or prefix of a namespace (eg 'og')
      */
    public static function setDefault($namespace)
    {
        if (is_null($namespace) or $namespace === '') {
            self::$default = null;
        } elseif (preg_match("/^\w+$/", $namespace)) {
            if (isset(self::$namespaces[$namespace])) {
                self::$default = self::$namespaces[$namespace];
            } else {
                throw new InvalidArgumentException(
                    "Unable to set default namespace to unknown prefix: $namespace"
                );
            }
        } else {
            self::$default = $namespace;
        }
    }

    /**
      * Delete an existing namespace.
      *
      * @param string $prefix The namespace prefix (eg 'foaf')
      */
    public static function delete($prefix)
    {
        if (!is_string($prefix) or $prefix === null or $prefix === '') {
            throw new InvalidArgumentException(
                "\$prefix should be a string and cannot be null or empty"
            );
        }

        $prefix = strtolower($prefix);
        if (isset(self::$namespaces[$prefix])) {
            unset(self::$namespaces[$prefix]);
        }
    }

    /**
      * Delete the anonymous namespaces and reset the counter to 0
      */
    public static function reset()
    {
        while (self::$anonymousNamespaceCount > 0) {
            self::delete('ns'.(self::$anonymousNamespaceCount-1));
            self::$anonymousNamespaceCount--;
        }
    }

    /**
      * Try and breakup a URI into a prefix and local part
      *
      * If $createNamespace is true, and the URI isn't part of an existing
      * namespace, then EasyRdf will attempt to create a new namespace and
      * return the name of the new prefix (for example 'ns0', 'term').
      *
      * If it isn't possible to split the URI, then null will be returned.
      *
      * @param string  $uri The full URI (eg 'http://xmlns.com/foaf/0.1/name')
      * @param bool    $createNamespace If true, a new namespace will be created
      * @return array  The split URI (eg 'foaf', 'name') or null
      */
    public static function splitUri($uri, $createNamespace = false)
    {
        if ($uri === null or $uri === '') {
            throw new InvalidArgumentException(
                "\$uri cannot be null or empty"
            );
        }

        if (is_object($uri) and ($uri instanceof EasyRdf_Resource)) {
            $uri = $uri->getUri();
        } elseif (!is_string($uri)) {
            throw new InvalidArgumentException(
                "\$uri should be a string or EasyRdf_Resource"
            );
        }

        foreach (self::$namespaces as $prefix => $long) {
            if (substr($uri, 0, strlen($long)) == $long) {
                return array($prefix, substr($uri, strlen($long)));
            }
        }

        if ($createNamespace) {
            // Try and create a new namespace
            # FIXME: check the valid characters for an XML element name
            if (preg_match("/^(.+?)([\w\-]+)$/", $uri, $matches)) {
                $prefix = "ns".(self::$anonymousNamespaceCount++);
                self::set($prefix, $matches[1]);
                return array($prefix, $matches[2]);
            }
        }

        return null;
    }

    /**
      * Return the prefix namespace that a URI belongs to.
      *
      * @param string $uri A full URI (eg 'http://xmlns.com/foaf/0.1/name')
      * @return string The prefix namespace that it is a part of(eg 'foaf')
      */
    public static function prefixOfUri($uri)
    {
        if ($parts = self::splitUri($uri)) {
            return $parts[0];
        }
    }

    /**
      * Shorten a URI by substituting in the namespace prefix.
      *
      * If $createNamespace is true, and the URI isn't part of an existing
      * namespace, then EasyRdf will attempt to create a new namespace and
      * use that namespace to shorten the URI (for example ns0:term).
      *
      * If it isn't possible to shorten the URI, then null will be returned.
      *
      * @param string  $uri The full URI (eg 'http://xmlns.com/foaf/0.1/name')
      * @param bool    $createNamespace If true, a new namespace will be created
      * @return string The shortened URI (eg 'foaf:name') or null
      */
    public static function shorten($uri, $createNamespace = false)
    {
        if ($parts = self::splitUri($uri, $createNamespace)) {
            return implode(':', $parts);
        }
    }

    /**
      * Expand a shortened URI (qname) back into a full URI.
      *
      * If it isn't possible to expand the qname, for example if the namespace
      * isn't registered, then the original string will be returned.
      *
      * @param string $shortUri The short URI (eg 'foaf:name')
      * @return string The full URI (eg 'http://xmlns.com/foaf/0.1/name')
      */
    public static function expand($shortUri)
    {
        if (!is_string($shortUri) or $shortUri === '') {
            throw new InvalidArgumentException(
                "\$shortUri should be a string and cannot be null or empty"
            );
        }
        
        if ($shortUri === 'a') {
            return self::$namespaces['rdf'] . 'type';
        } elseif (preg_match("/^(\w+?):([\w\-]+)$/", $shortUri, $matches)) {
            $long = self::get($matches[1]);
            if ($long) {
                return $long . $matches[2];
            }
        } elseif (preg_match("/^(\w+)$/", $shortUri) and isset(self::$default)) {
            return self::$default . $shortUri;
        }

        return $shortUri;
    }
}
