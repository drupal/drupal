<?php

/*
 * This file is part of the Behat\Mink.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Driver\Goutte;

use Goutte\Client as BaseClient;
use Symfony\Component\BrowserKit\Response;

/**
 * Client overrides to support Mink functionality.
 */
class Client extends BaseClient
{
    /**
     * Reads response meta tags to guess content-type charset.
     *
     * @param Response $response
     *
     * @return Response
     */
    protected function filterResponse($response)
    {
        $contentType = $response->getHeader('Content-Type');

        if (!$contentType || false === strpos($contentType, 'charset=')) {
            if (preg_match('/\<meta[^\>]+charset *= *["\']?([a-zA-Z\-0-9]+)/i', $response->getContent(), $matches)) {
                $headers = $response->getHeaders();
                $headers['Content-Type'] = $contentType.';charset='.$matches[1];

                $response = new Response($response->getContent(), $response->getStatus(), $headers);
            }
        }

        return parent::filterResponse($response);
    }
}
