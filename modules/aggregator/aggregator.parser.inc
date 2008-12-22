<?php
// $Id$

/**
 * @file
 * Parser functions for the aggregator module.
 */

/**
 * Implementation of hook_aggregator_parse_info().
 */
function aggregator_aggregator_parse_info() {
  return array(
    'title' => t('Default parser'),
    'description' => t('Parses RSS, Atom and RDF feeds.'),
  );
}

/**
 * Implementation of hook_aggregator_parse().
 */
function aggregator_aggregator_parse($feed) {
  global $channel, $image;

  // Filter the input data.
  if (aggregator_parse_feed($feed->source_string, $feed)) {
    $modified = empty($feed->http_headers['Last-Modified']) ? 0 : strtotime($feed->http_headers['Last-Modified']);

    // Prepare the channel data.
    foreach ($channel as $key => $value) {
      $channel[$key] = trim($value);
    }

    // Prepare the image data (if any).
    foreach ($image as $key => $value) {
      $image[$key] = trim($value);
    }

    if (!empty($image['LINK']) && !empty($image['URL']) && !empty($image['TITLE'])) {
      $image = l(theme('image', $image['URL'], $image['TITLE']), $image['LINK'], array('html' => TRUE));
    }
    else {
      $image = '';
    }

    $etag = empty($feed->http_headers['ETag']) ? '' : $feed->http_headers['ETag'];
    // Update the feed data.
    db_merge('aggregator_feed')
      ->key(array('fid' => $feed->fid))
      ->fields(array(
        'url' => $feed->url,
        'checked' => REQUEST_TIME,
        'link' => !empty($channel['LINK']) ? $channel['LINK'] : '',
        'description' => !empty($channel['DESCRIPTION']) ? $channel['DESCRIPTION'] : '',
        'image' => $image,
        'hash' => md5($feed->source_string),
        'etag' => $etag,
        'modified' => $modified,
      ))
      ->execute();

    // Clear the cache.
    cache_clear_all();

    if (isset($feed->redirected)) {
      watchdog('aggregator', 'Updated URL for feed %title to %url.', array('%title' => $feed->title, '%url' => $feed->url));
    }

    watchdog('aggregator', 'There is new syndicated content from %site.', array('%site' => $feed->title));
    drupal_set_message(t('There is new syndicated content from %site.', array('%site' => $feed->title)));

  }
}

/**
 * Parse a feed and store its items.
 *
 * @param $data
 *   The feed data.
 * @param $feed
 *   An object describing the feed to be parsed.
 * @return
 *   FALSE on error, TRUE otherwise.
 */
function aggregator_parse_feed(&$data, $feed) {
  global $items, $image, $channel;

  // Unset the global variables before we use them.
  unset($GLOBALS['element'], $GLOBALS['item'], $GLOBALS['tag']);
  $items = array();
  $image = array();
  $channel = array();

  // Parse the data.
  $xml_parser = drupal_xml_parser_create($data);
  xml_set_element_handler($xml_parser, 'aggregator_element_start', 'aggregator_element_end');
  xml_set_character_data_handler($xml_parser, 'aggregator_element_data');

  if (!xml_parse($xml_parser, $data, 1)) {
    watchdog('aggregator', 'The feed from %site seems to be broken, due to an error "%error" on line %line.', array('%site' => $feed->title, '%error' => xml_error_string(xml_get_error_code($xml_parser)), '%line' => xml_get_current_line_number($xml_parser)), WATCHDOG_WARNING);
    drupal_set_message(t('The feed from %site seems to be broken, because of error "%error" on line %line.', array('%site' => $feed->title, '%error' => xml_error_string(xml_get_error_code($xml_parser)), '%line' => xml_get_current_line_number($xml_parser))), 'error');
    return FALSE;
  }
  xml_parser_free($xml_parser);

  // We reverse the array such that we store the first item last, and the last
  // item first. In the database, the newest item should be at the top.
  $items = array_reverse($items);

  // Initialize items array.
  $feed->items = array();
  foreach ($items as $item) {

    // Prepare the item:
    foreach ($item as $key => $value) {
      $item[$key] = trim($value);
    }

    // Resolve the item's title. If no title is found, we use up to 40
    // characters of the description ending at a word boundary, but not
    // splitting potential entities.
    if (!empty($item['TITLE'])) {
      $item['TITLE'] = $item['TITLE'];
    }
    elseif (!empty($item['DESCRIPTION'])) {
      $item['TITLE'] = preg_replace('/^(.*)[^\w;&].*?$/', "\\1", truncate_utf8($item['DESCRIPTION'], 40));
    }
    else {
      $item['TITLE'] = '';
    }

    // Resolve the items link.
    if (!empty($item['LINK'])) {
      $item['LINK'] = $item['LINK'];
    }
    else {
      $item['LINK'] = $feed->link;
    }
    $item['GUID'] = isset($item['GUID']) ? $item['GUID'] : '';

    // Atom feeds have a CONTENT and/or SUMMARY tag instead of a DESCRIPTION tag.
    if (!empty($item['CONTENT:ENCODED'])) {
      $item['DESCRIPTION'] = $item['CONTENT:ENCODED'];
    }
    elseif (!empty($item['SUMMARY'])) {
      $item['DESCRIPTION'] = $item['SUMMARY'];
    }
    elseif (!empty($item['CONTENT'])) {
      $item['DESCRIPTION'] = $item['CONTENT'];
    }

    // Try to resolve and parse the item's publication date.
    $date = '';
    foreach (array('PUBDATE', 'DC:DATE', 'DCTERMS:ISSUED', 'DCTERMS:CREATED', 'DCTERMS:MODIFIED', 'ISSUED', 'CREATED', 'MODIFIED', 'PUBLISHED', 'UPDATED') as $key) {
      if (!empty($item[$key])) {
        $date = $item[$key];
        break;
      }
    }

    $item['TIMESTAMP'] = strtotime($date);

    if ($item['TIMESTAMP'] === FALSE) {
      $item['TIMESTAMP'] = aggregator_parse_w3cdtf($date); // Aggregator_parse_w3cdtf() returns FALSE on failure.
    }

    $item += array('AUTHOR' => '', 'DESCRIPTION' => '');

    // Store on $feed object. This is where processors will look for parsed items.
    $feed->items[] = $item;
  }

  return TRUE;
}

/**
 * Callback function used by the XML parser.
 */
function aggregator_element_start($parser, $name, $attributes) {
  global $item, $element, $tag, $items, $channel;

  switch ($name) {
    case 'IMAGE':
    case 'TEXTINPUT':
    case 'CONTENT':
    case 'SUMMARY':
    case 'TAGLINE':
    case 'SUBTITLE':
    case 'LOGO':
    case 'INFO':
      $element = $name;
      break;
    case 'ID':
      if ($element != 'ITEM') {
        $element = $name;
      }
    case 'LINK':
      if (!empty($attributes['REL']) && $attributes['REL'] == 'alternate') {
        if ($element == 'ITEM') {
          $items[$item]['LINK'] = $attributes['HREF'];
        }
        else {
          $channel['LINK'] = $attributes['HREF'];
        }
      }
      break;
    case 'ITEM':
      $element = $name;
      $item += 1;
      break;
    case 'ENTRY':
      $element = 'ITEM';
      $item += 1;
      break;
  }

  $tag = $name;
}

/**
 * Call-back function used by the XML parser.
 */
function aggregator_element_end($parser, $name) {
  global $element;

  switch ($name) {
    case 'IMAGE':
    case 'TEXTINPUT':
    case 'ITEM':
    case 'ENTRY':
    case 'CONTENT':
    case 'INFO':
      $element = '';
      break;
    case 'ID':
      if ($element == 'ID') {
        $element = '';
      }
  }
}

/**
 * Callback function used by the XML parser.
 */
function aggregator_element_data($parser, $data) {
  global $channel, $element, $items, $item, $image, $tag;
  $items += array($item => array());
  switch ($element) {
    case 'ITEM':
      $items[$item] += array($tag => '');
      $items[$item][$tag] .= $data;
      break;
    case 'IMAGE':
    case 'LOGO':
      $image += array($tag => '');
      $image[$tag] .= $data;
      break;
    case 'LINK':
      if ($data) {
        $items[$item] += array($tag => '');
        $items[$item][$tag] .= $data;
      }
      break;
    case 'CONTENT':
      $items[$item] += array('CONTENT' => '');
      $items[$item]['CONTENT'] .= $data;
      break;
    case 'SUMMARY':
      $items[$item] += array('SUMMARY' => '');
      $items[$item]['SUMMARY'] .= $data;
      break;
    case 'TAGLINE':
    case 'SUBTITLE':
      $channel += array('DESCRIPTION' => '');
      $channel['DESCRIPTION'] .= $data;
      break;
    case 'INFO':
    case 'ID':
    case 'TEXTINPUT':
      // The sub-element is not supported. However, we must recognize
      // it or its contents will end up in the item array.
      break;
    default:
      $channel += array($tag => '');
      $channel[$tag] .= $data;
  }
}

/**
 * Parse the W3C date/time format, a subset of ISO 8601.
 *
 * PHP date parsing functions do not handle this format.
 * See http://www.w3.org/TR/NOTE-datetime for more information.
 * Originally from MagpieRSS (http://magpierss.sourceforge.net/).
 *
 * @param $date_str
 *   A string with a potentially W3C DTF date.
 * @return
 *   A timestamp if parsed successfully or FALSE if not.
 */
function aggregator_parse_w3cdtf($date_str) {
  if (preg_match('/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(:(\d{2}))?(?:([-+])(\d{2}):?(\d{2})|(Z))?/', $date_str, $match)) {
    list($year, $month, $day, $hours, $minutes, $seconds) = array($match[1], $match[2], $match[3], $match[4], $match[5], $match[6]);
    // Calculate the epoch for current date assuming GMT.
    $epoch = gmmktime($hours, $minutes, $seconds, $month, $day, $year);
    if ($match[10] != 'Z') { // Z is zulu time, aka GMT
      list($tz_mod, $tz_hour, $tz_min) = array($match[8], $match[9], $match[10]);
      // Zero out the variables.
      if (!$tz_hour) {
        $tz_hour = 0;
      }
      if (!$tz_min) {
        $tz_min = 0;
      }
      $offset_secs = (($tz_hour * 60) + $tz_min) * 60;
      // Is timezone ahead of GMT?  If yes, subtract offset.
      if ($tz_mod == '+') {
        $offset_secs *= -1;
      }
      $epoch += $offset_secs;
    }
    return $epoch;
  }
  else {
    return FALSE;
  }
}
