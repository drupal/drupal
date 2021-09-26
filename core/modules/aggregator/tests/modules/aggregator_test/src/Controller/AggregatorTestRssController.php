<?php

namespace Drupal\aggregator_test\Controller;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the aggregator_test module.
 */
class AggregatorTestRssController extends ControllerBase {

  /**
   * Generates a test feed and simulates last-modified and etags.
   *
   * @param bool $use_last_modified
   *   Set TRUE to send a last modified header.
   * @param bool $use_etag
   *   Set TRUE to send an etag.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Information about the current HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A feed that forces cache validation.
   */
  public function testFeed($use_last_modified, $use_etag, Request $request) {
    $response = new Response();

    $last_modified = strtotime('Sun, 19 Nov 1978 05:00:00 GMT');
    $etag = Crypt::hashBase64($last_modified);

    $if_modified_since = strtotime($request->server->get('HTTP_IF_MODIFIED_SINCE', ''));
    $if_none_match = stripslashes($request->server->get('HTTP_IF_NONE_MATCH', ''));

    // Send appropriate response. We respond with a 304 not modified on either
    // etag or on last modified.
    if ($use_last_modified) {
      $response->headers->set('Last-Modified', gmdate(DateTimePlus::RFC7231, $last_modified));
    }
    if ($use_etag) {
      $response->headers->set('ETag', $etag);
    }
    // Return 304 not modified if either last modified or etag match.
    if ($last_modified == $if_modified_since || $etag == $if_none_match) {
      $response->setStatusCode(304);
      return $response;
    }

    // The following headers force validation of cache.
    $response->headers->set('Expires', 'Sun, 19 Nov 1978 05:00:00 GMT');
    $response->headers->set('Cache-Control', 'must-revalidate');
    $response->headers->set('Content-Type', 'application/rss+xml; charset=utf-8');

    // Read actual feed from file.
    $file_name = __DIR__ . '/../../aggregator_test_rss091.xml';
    $handle = fopen($file_name, 'r');
    $feed = fread($handle, filesize($file_name));
    fclose($handle);

    $response->setContent($feed);
    return $response;
  }

  /**
   * Generates a rest redirect to the test feed.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A response that redirects users to the test feed.
   */
  public function testRedirect() {
    return $this->redirect('aggregator_test.feed', [], [], 301);
  }

}
