<?php

/**
 * @file
 * Announcements feed helper functions.
 */

/**
 * Returns the list of announcements.
 *
 * @return array
 *   A build array with announcements.
 */
function announcements_feed_get_announcements() {
  drupal_set_title(t('Community announcements'));
  drupal_add_css(drupal_get_path('module', 'announcements_feed')
    . '/announcements_feed.css', array(
      'group' => CSS_DEFAULT,
      'every_page' => TRUE,
    ));
  try {
    $announcements = announcements_feed_get_all_announcements();
  }
  catch (Exception $e) {
    drupal_set_message(t('An error occurred while parsing the announcements feed, check the logs for more information.'), 'error');
    return array();
  }
  $build = array();
  foreach ($announcements as $announcement) {
    $key = $announcement['featured'] ? '#featured' : '#standard';
    $build[$key][] = $announcement;
  }
  $build = array_merge($build, array(
    '#theme' => 'announcements_feed',
    '#count' => count($announcements),
    '#feed_link' => variable_get('announcements_feed_link', ANNOUNCEMENTS_FEED_DEFAULT_LINK),
  ));
  return $build;
}

/**
 * Generate an array of announcements with keys.
 *
 * @return array
 *   An array of announcements.
 */
function announcements_feed_get_all_announcements() {
  $announcements = announcements_feed_fetch();
  $announcements_feed = array();
  foreach ($announcements as $announcement) {
    $announcements_feed[] = array(
      'id' => $announcement['id'],
      'title' => $announcement['title'],
      'link' => $announcement['url'],
      'date_modified' => $announcement['date_modified'],
      'date_published' => $announcement['date_published'],
      'teaser' => $announcement['content_html'],
      'version' => $announcement['_drupalorg']['version'],
      'featured' => (bool) $announcement['_drupalorg']['featured'],
    );
  }

  return $announcements_feed;
}

/**
 * Fetches the feed either from a local cache or fresh remotely.
 *
 * The feed follows the "JSON Feed" format:
 * - https://www.jsonfeed.org/version/1.1/
 *
 * The structure of an announcement item in the feed is:
 *   - id: Id.
 *   - title: Title of the announcement.
 *   - content_html: Announcement teaser.
 *   - url: URL
 *   - date_modified: Last updated timestamp.
 *   - date_published: Created timestamp.
 *   - _drupalorg.featured: 1 if featured, 0 if not featured.
 *   - _drupalorg.version: Target version of Drupal, as a Composer version.
 *
 * @param bool $force
 *   (optional) Whether to always fetch new items or not. Defaults to FALSE.
 *
 * @return array
 *   An array of announcements from the feed relevant to the Drupal version.
 *   The array is empty if there were no matching announcements. If an error
 *   occurred while fetching/decoding the feed, it is thrown as an exception.
 *
 * @throws Exception
 */
function announcements_feed_fetch($force = FALSE) {
  $announcements = cache_get('announcements_feed');
  if ($force || empty($announcements)) {
    $announcements_feed_json_url = variable_get('announcements_feed_json_url', ANNOUNCEMENTS_FEED_DEFAULT_JSON_URL);
    $response = drupal_http_request($announcements_feed_json_url);
    if ($response->code == 200) {
      $feeds = json_decode($response->data, TRUE);
      if (!isset($feeds['items'])) {
        watchdog('announcements_feed', 'The feed format is not valid.', NULL, WATCHDOG_ERROR);
        throw new Exception('Announcements feed JSON format is invalid');
      }
      $announcements = array();
      if ($feeds['items']) {
        $announcements = $feeds['items'];
      }
      $announcements = array_filter($announcements, 'announcements_feed_filter_announcements');
      cache_set('announcements_feed', $announcements, 'cache', REQUEST_TIME + variable_get('announcements_feed_max_age', ANNOUNCEMENTS_FEED_DEFAULT_MAX_AGE));
    }
    else {
      watchdog(
        'announcements_feed',
        'The feed failed to fetch with an error code: @code, error message: @message.',
        array('@code' => $response->code, '@message' => $response->error),
        WATCHDOG_ERROR
      );
      throw new Exception($response->error, $response->code);
    }
  }
  else {
    $announcements = $announcements->data;
  }
  // The drupal.org endpoint is sorted by created date in descending order.
  // We will limit the announcements based on the configuration limit.
  $announcements_feed_limit = variable_get('announcements_feed_limit', ANNOUNCEMENTS_FEED_DEFAULT_LIMIT);
  $announcements = array_slice($announcements, 0, $announcements_feed_limit);
  // For the remaining announcements, put all the featured announcements
  // before the rest.
  uasort($announcements, 'announcements_feed_sort_featured');

  return $announcements;
}

/**
 * Sort the elements of announcements_feed by values in comparison function.
 */
function announcements_feed_sort_featured($a, $b) {
  $a_value = (int) $a['_drupalorg']['featured'];
  $b_value = (int) $b['_drupalorg']['featured'];
  if ($a_value == $b_value) {
    return 0;
  }

  return ($a_value < $b_value) ? -1 : 1;
}

/**
 * Filter the announcements relevant to the Drupal version used with valid URL controlled by drupal.org.
 *
 * @param array $announcement
 *   Announcement feed array item to check.
 *
 * @return bool
 *   Return TRUE if $announcement is relevant and the URL is valid.
 */
function announcements_feed_filter_announcements($announcement) {
  $announcement_url = '';
  $announcement_version = '';
  if (!empty($announcement['url'])) {
    $announcement_url = $announcement['url'];
  }
  if (!empty($announcement['_drupalorg']['version'])) {
    $announcement_version = $announcement['_drupalorg']['version'];
  }

  return announcements_feed_validate_url($announcement_url) && announcements_feed_is_relevant_item($announcement_version);
}

/**
 * Check whether the version given is relevant to the Drupal version used.
 *
 * @param string $version
 *   Version to check.
 *
 * @return bool
 *   Return TRUE if the version matches Drupal version.
 */
function announcements_feed_is_relevant_item($version) {
  if ($version == '*') {
    return TRUE;
  }
  // Split the version if received in || formats.
  $version_patterns = '/\|\|/';
  $all_versions = preg_split($version_patterns, $version);
  // The operation is optional and defaults to equals.
  $p_op = '(?P<operation>!=|\^|==|=|<|<=|>|>=|<>)?';
  $operations = '=';
  // Extracts major version from version string like 7, 8, 9.
  $p_major = '(?P<major>\d+)';
  // Extracts minor version from version string.
  $p_minor = '(?P<minor>(?:\d+|x)(?:-[A-Za-z]+\d+)?)';
  foreach ($all_versions as $version) {
    if (preg_match("/^\s*$p_op\s*$p_major(\.$p_minor)?/", $version, $matches)) {
      $feed_version = $matches['major'];
      if (!empty($matches['minor'])) {
        $feed_version = $matches['major'] . '.' . $matches['minor'];
      }
      if (!empty($matches['operation'])) {
        $operations = $matches['operation'];
        if ($operations == '^') {
          $operations = '>=';
        }
      }
      if (isset($operations) && version_compare(VERSION, $feed_version, $operations)) {
        return TRUE;
      }
    }
  }

  return FALSE;
}

/**
 * Check whether a link is controlled by drupal.org.
 *
 * @param string $url
 *   URL to check.
 *
 * @return bool
 *   Return TRUE if the URL is controlled by drupal.org.
 */
function announcements_feed_validate_url($url) {
  if (empty($url)) {
    return FALSE;
  }
  $host = parse_url($url, PHP_URL_HOST);

  // First character can only be a letter or a digit.
  // @see https://www.rfc-editor.org/rfc/rfc1123#page-13
  return $host && preg_match('/^([a-zA-Z0-9][a-zA-Z0-9\-_]*\.)?drupal\.org$/', $host);
}
