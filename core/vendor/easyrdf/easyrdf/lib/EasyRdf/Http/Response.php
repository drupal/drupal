<?php

/**
 * EasyRdf
 *
 * LICENSE
 *
 * Copyright (c) 2009-2013 Nicholas J Humfrey.
 * Copyright (c) 2005-2009 Zend Technologies USA Inc.
 * All rights reserved.
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
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc.
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */

/**
 * Class that represents an HTTP 1.0 / 1.1 response message.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 *             Copyright (c) 2005-2009 Zend Technologies USA Inc.
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Http_Response
{

    /**
     * The HTTP response status code
     *
     * @var int
     */
    private $status;

    /**
     * The HTTP response code as string
     * (e.g. 'Not Found' for 404 or 'Internal Server Error' for 500)
     *
     * @var string
     */
    private $message;

    /**
     * The HTTP response headers array
     *
     * @var array
     */
    private $headers = array();

    /**
     * The HTTP response body
     *
     * @var string
     */
    private $body;

    /**
     * Constructor.
     *
     * @param  int     $status HTTP Status code
     * @param  array   $headers The HTTP response headers
     * @param  string  $body The content of the response
     * @param  string  $version The HTTP Version (1.0 or 1.1)
     * @param  string  $message The HTTP response Message
     * @return object  EasyRdf_Http_Response
     */
    public function __construct(
        $status,
        $headers,
        $body = null,
        $version = '1.1',
        $message = null
    ) {
        $this->status = intval($status);
        $this->body = $body;
        $this->version = $version;
        $this->message = $message;

        foreach ($headers as $k => $v) {
            $k = ucwords(strtolower($k));
            $this->headers[$k] = $v;
        }
    }

    /**
     * Check whether the response in successful
     *
     * @return boolean
     */
    public function isSuccessful()
    {
        return ($this->status >= 200 && $this->status < 300);
    }

    /**
     * Check whether the response is an error
     *
     * @return boolean
     */
    public function isError()
    {
        return ($this->status >= 400 && $this->status < 600);
    }

    /**
     * Check whether the response is a redirection
     *
     * @return boolean
     */
    public function isRedirect()
    {
        return ($this->status >= 300 && $this->status < 400);
    }

    /**
     * Get the HTTP response status code
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Return a message describing the HTTP response code
     * (Eg. "OK", "Not Found", "Moved Permanently")
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get the response body as string
     *
     * @return string
     */
    public function getBody()
    {
        // Decode the body if it was transfer-encoded
        switch (strtolower($this->getHeader('transfer-encoding'))) {
            // Handle chunked body
            case 'chunked':
                return self::decodeChunkedBody($this->body);
                break;

            // No transfer encoding, or unknown encoding extension:
            // return body as is
            default:
                return $this->body;
                break;
        }
    }

    /**
     * Get the raw response body (as transfered "on wire") as string
     *
     * If the body is encoded (with Transfer-Encoding, not content-encoding -
     * IE "chunked" body), gzip compressed, etc. it will not be decoded.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->body;
    }

    /**
     * Get the HTTP version of the response
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Get the response headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get a specific header as string, or null if it is not set
     *
     * @param string$header
     * @return string|array|null
     */
    public function getHeader($header)
    {
        $header = ucwords(strtolower($header));
        if (array_key_exists($header, $this->headers)) {
            return $this->headers[$header];
        } else {
            return null;
        }
    }

    /**
     * Get all headers as string
     *
     * @param boolean $statusLine Whether to return the first status line (ie "HTTP 200 OK")
     * @param string  $br         Line breaks (eg. "\n", "\r\n", "<br />")
     * @return string
     */
    public function getHeadersAsString($statusLine = true, $br = "\n")
    {
        $str = '';

        if ($statusLine) {
            $str = "HTTP/{$this->version} {$this->status} {$this->message}{$br}";
        }

        // Iterate over the headers and stringify them
        foreach ($this->headers as $name => $value) {
            if (is_string($value)) {
                $str .= "{$name}: {$value}{$br}";
            } elseif (is_array($value)) {
                foreach ($value as $subval) {
                    $str .= "{$name}: {$subval}{$br}";
                }
            }
        }

        return $str;
    }

    /**
     * Create an EasyRdf_Http_Response object from a HTTP response string
     *
     * @param string $responseStr
     * @return EasyRdf_Http_Response
     */
    public static function fromString($responseStr)
    {
        // First, split body and headers
        $matches = preg_split('|(?:\r?\n){2}|m', $responseStr, 2);
        if ($matches and sizeof($matches) == 2) {
            list ($headerLines, $body) = $matches;
        } else {
            throw new EasyRdf_Exception(
                "Failed to parse HTTP response."
            );
        }

        // Split headers part to lines
        $headerLines = preg_split('|[\r\n]+|m', $headerLines);
        $status = array_shift($headerLines);
        if (preg_match("|^HTTP/([\d\.x]+) (\d+) ([^\r\n]+)|", $status, $m)) {
            $version = $m[1];
            $status = $m[2];
            $message = $m[3];
        } else {
            throw new EasyRdf_Exception(
                "Failed to parse HTTP response status line."
            );
        }

        // Process the rest of the header lines
        $headers = array();
        foreach ($headerLines as $line) {
            if (preg_match("|^([\w-]+):\s+(.+)$|", $line, $m)) {
                $hName = ucwords(strtolower($m[1]));
                $hValue = $m[2];

                if (isset($headers[$hName])) {
                    if (! is_array($headers[$hName])) {
                        $headers[$hName] = array($headers[$hName]);
                    }
                    $headers[$hName][] = $hValue;
                } else {
                    $headers[$hName] = $hValue;
                }
            }
        }

        return new EasyRdf_Http_Response($status, $headers, $body, $version, $message);
    }


    /**
     * Decode a "chunked" transfer-encoded body and return the decoded text
     *
     * @param string $body
     * @return string
     */
    public static function decodeChunkedBody($body)
    {
        $decBody = '';

        while (trim($body)) {
            if (preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $body, $m)) {
                $length = hexdec(trim($m[1]));
                $cut = strlen($m[0]);
                $decBody .= substr($body, $cut, $length);
                $body = substr($body, $cut + $length + 2);
            } else {
                throw new EasyRdf_Exception(
                    "Failed to decode chunked body in HTTP response."
                );
            }
        }

        return $decBody;
    }


    /**
     * Get the entire response as string
     *
     * @param string $br Line breaks (eg. "\n", "\r\n", "<br />")
     * @return string
     */
    public function asString($br = "\n")
    {
        return $this->getHeadersAsString(true, $br) . $br . $this->getRawBody();
    }

    /**
     * Implements magic __toString()
     *
     * @return string
     */
    public function __toString()
    {
        return $this->asString();
    }
}
