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
 * A RFC3986 compliant URI parser
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @link       http://www.ietf.org/rfc/rfc3986.txt
 */
class EasyRdf_ParsedUri
{
    // For all URIs:
    private $scheme = null;
    private $fragment = null;

    // For hierarchical URIs:
    private $authority = null;
    private $path = null;
    private $query = null;

    const URI_REGEX = "|^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?|";

    /** Constructor for creating a new parsed URI
     *
     * The $uri parameter can either be a string or an
     * associative array with the following keys:
     * scheme, authority, path, query, fragment
     *
     * @param  mixed $uri  The URI as a string or an array
     * @return object EasyRdf_ParsedUri
     */
    public function __construct($uri = null)
    {
        if (is_string($uri)) {
            if (preg_match(self::URI_REGEX, $uri, $matches)) {
                if (!empty($matches[1])) {
                    $this->scheme = isset($matches[2]) ? $matches[2] : '';
                }
                if (!empty($matches[3])) {
                    $this->authority = isset($matches[4]) ? $matches[4] : '';
                }
                $this->path = isset($matches[5]) ? $matches[5] : '';
                if (!empty($matches[6])) {
                    $this->query = isset($matches[7]) ? $matches[7] : '';
                }
                if (!empty($matches[8])) {
                    $this->fragment = isset($matches[9]) ? $matches[9] : '';
                }
            }
        } elseif (is_array($uri)) {
            $this->scheme = isset($uri['scheme']) ? $uri['scheme'] : null;
            $this->authority = isset($uri['authority']) ? $uri['authority'] : null;
            $this->path = isset($uri['path']) ? $uri['path'] : null;
            $this->query = isset($uri['query']) ? $uri['query'] : null;
            $this->fragment = isset($uri['fragment']) ? $uri['fragment'] : null;
        }
    }


    /** Returns true if this is an absolute (complete) URI
     * @return boolean
     */
    public function isAbsolute()
    {
        return $this->scheme !== null;
    }

    /** Returns true if this is an relative (partial) URI
     * @return boolean
     */
    public function isRelative()
    {
        return $this->scheme === null;
    }

    /** Returns the scheme of the URI (e.g. http)
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /** Sets the scheme of the URI (e.g. http)
     * @param string $scheme The new value for the scheme of the URI
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
    }

    /** Returns the authority of the URI (e.g. www.example.com:8080)
     * @return string
     */
    public function getAuthority()
    {
        return $this->authority;
    }

    /** Sets the authority of the URI (e.g. www.example.com:8080)
     * @param string $authority The new value for the authority component of the URI
     */
    public function setAuthority($authority)
    {
        $this->authority = $authority;
    }

    /** Returns the path of the URI (e.g. /foo/bar)
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /** Set the path of the URI (e.g. /foo/bar)
     * @param string $path The new value for the path component of the URI
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /** Returns the query string part of the URI (e.g. foo=bar)
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /** Set the query string of the URI (e.g. foo=bar)
     * @param string $query The new value for the query string component of the URI
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /** Returns the fragment part of the URI (i.e. after the #)
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /** Set the fragment of the URI (i.e. after the #)
     * @param string $fragment The new value for the fragment component of the URI
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;
    }


    /**
     * Normalises the path of this URI if it has one. Normalising a path means
     * that any unnecessary '.' and '..' segments are removed. For example, the
     * URI http://example.com/a/b/../c/./d would be normalised to
     * http://example.com/a/c/d
     *
     * @return object EasyRdf_ParsedUri
     */
    public function normalise()
    {
        if (empty($this->path)) {
            return $this;
        }

        // Remove ./ from the start
        if (substr($this->path, 0, 2) == './') {
            // Remove both characters
            $this->path = substr($this->path, 2);
        }

        // Remove /. from the end
        if (substr($this->path, -2) == '/.') {
            // Remove only the last dot, not the slash!
            $this->path = substr($this->path, 0, -1);
        }

        if (substr($this->path, -3) == '/..') {
            $this->path .= '/';
        }

        // Split the path into its segments
        $segments = explode('/', $this->path);
        $newSegments = array();

        // Remove all unnecessary '.' and '..' segments
        foreach ($segments as $segment) {
            if ($segment == '..') {
                // Remove the previous part of the path
                $count = count($newSegments);
                if ($count > 0 && $newSegments[$count-1]) {
                    array_pop($newSegments);
                }
            } elseif ($segment == '.') {
                // Ignore
                continue;
            } else {
                array_push($newSegments, $segment);
            }
        }

        // Construct the new normalised path
        $this->path = implode($newSegments, '/');

        // Allow easy chaining of methods
        return $this;
    }

    /**
     * Resolves a relative URI using this URI as the base URI.
     */
    public function resolve($relUri)
    {
        // If it is a string, then convert it to a parsed object
        if (is_string($relUri)) {
            $relUri = new EasyRdf_ParsedUri($relUri);
        }

        // This code is based on the pseudocode in section 5.2.2 of RFC3986
        $target = new EasyRdf_ParsedUri();
        if ($relUri->scheme) {
            $target->scheme = $relUri->scheme;
            $target->authority = $relUri->authority;
            $target->path = $relUri->path;
            $target->query = $relUri->query;
        } else {
            if ($relUri->authority) {
                $target->authority = $relUri->authority;
                $target->path = $relUri->path;
                $target->query = $relUri->query;
            } else {
                if (empty($relUri->path)) {
                    $target->path = $this->path;
                    if ($relUri->query) {
                        $target->query = $relUri->query;
                    } else {
                        $target->query = $this->query;
                    }
                } else {
                    if (substr($relUri->path, 0, 1) == '/') {
                        $target->path = $relUri->path;
                    } else {
                        $path = $this->path;
                        $lastSlash = strrpos($path, '/');
                        if ($lastSlash !== false) {
                            $path = substr($path, 0, $lastSlash + 1);
                        } else {
                            $path = '/';
                        }

                        $target->path .= $path . $relUri->path;
                    }
                    $target->query = $relUri->query;
                }
                $target->authority = $this->authority;
            }
            $target->scheme = $this->scheme;
        }

        $target->fragment = $relUri->fragment;

        $target->normalise();

        return $target;
    }

    /** Convert the parsed URI back into a string
     *
     * @return string The URI as a string
     */
    public function toString()
    {
        $str = '';
        if ($this->scheme !== null) {
            $str .= $this->scheme . ':';
        }
        if ($this->authority !== null) {
            $str .= '//' . $this->authority;
        }
        $str .= $this->path;
        if ($this->query !== null) {
            $str .= '?' . $this->query;
        }
        if ($this->fragment !== null) {
            $str .= '#' . $this->fragment;
        }
        return $str;
    }

    /** Magic method to convert the URI, when casted, back to a string
     *
     * @return string The URI as a string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
