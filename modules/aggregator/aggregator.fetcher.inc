<?php
// $Id$

/**
 * @file
 * Fetcher functions for the aggregator module.
 */

/**
 * Implements hook_aggregator_fetch_info().
 */
function aggregator_aggregator_fetch_info() {
  return array(
    'title' => t('Default fetcher'),
    'description' => t('Downloads data from a URL using Drupal\'s HTTP request handler.'),
  );
}

/**
 * Implements hook_aggregator_fetch().
 */
function aggregator_aggregator_fetch($feed) {
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
      watchdog('aggregator', 'The feed from %site seems to be broken, due to "%error".', array('%site' => $feed->title, '%error' => $result->code . ' ' . $result->error), WATCHDOG_WARNING);
      drupal_set_message(t('The feed from %site seems to be broken, because of error "%error".', array('%site' => $feed->title, '%error' => $result->code . ' ' . $result->error)));
  }

  return $feed->source_string === FALSE ? FALSE : TRUE;
}
