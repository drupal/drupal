<?php

/**
 * @file
 * Definition of Drupal\aggregator\Plugin\aggregator\fetcher\DefaultFetcher.
 */

namespace Drupal\aggregator\Plugin\aggregator\fetcher;

use Drupal\aggregator\Plugin\FetcherInterface;

/**
 * Defines a default fetcher implementation.
 *
 * Uses drupal_http_request() to download the feed.
 */
class DefaultFetcher implements FetcherInterface {

  /**
   * Implements Drupal\aggregator\Plugin\FetcherInterface::fetch().
   */
  function fetch(&$feed) {
    $feed->source_string = FALSE;

    // Generate conditional GET headers.
    $headers = array();
    if ($feed->etag) {
      $headers['If-None-Match'] = $feed->etag;
    }
    if ($feed->modified) {
      $headers['If-Modified-Since'] = gmdate(DATE_RFC1123, $feed->modified);
    }

    // Request feed.
    $result = drupal_http_request($feed->url, array('headers' => $headers));

    // Process HTTP response code.
    switch ($result->code) {
      case 304:
        break;
      case 301:
        $feed->url = $result->redirect_url;
        // Do not break here.
      case 200:
      case 302:
      case 307:
        if (!isset($result->data)) {
          $result->data = '';
        }
        if (!isset($result->headers)) {
          $result->headers = array();
        }
        $feed->source_string = $result->data;
        $feed->http_headers = $result->headers;
        break;
      default:
        watchdog('aggregator', 'The feed from %site seems to be broken due to "%error".', array('%site' => $feed->title, '%error' => $result->code . ' ' . $result->error), WATCHDOG_WARNING);
        drupal_set_message(t('The feed from %site seems to be broken because of error "%error".', array('%site' => $feed->title, '%error' => $result->code . ' ' . $result->error)));
    }

    return !($feed->source_string === FALSE);
  }
}
