<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\aggregator\parser\DefaultParser.
 */

namespace Drupal\aggregator\Plugin\aggregator\parser;

use Drupal\aggregator\Plugin\ParserInterface;
use Drupal\aggregator\Plugin\Core\Entity\Feed;
use Drupal\aggregator\Annotation\AggregatorParser;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a default parser implementation.
 *
 * Parses RSS, Atom and RDF feeds.
 *
 * @AggregatorParser(
 *   id = "aggregator",
 *   title = @Translation("Default parser"),
 *   description = @Translation("Default parser for RSS, Atom and RDF feeds.")
 * )
 */
class DefaultParser implements ParserInterface {

  /**
   * The extracted channel info.
   *
   * @var array
   */
  protected $channel = array();

  /**
   * The extracted image info.
   *
   * @var array
   */
  protected $image = array();

  /**
   * The extracted items.
   *
   * @var array
   */
  protected $items = array();

  /**
   * The element that is being processed.
   *
   * @var array
   */
  protected $element = array();

  /**
   * The tag that is being processed.
   *
   * @var string
   */
  protected $tag = '';

  /**
   * Key that holds the number of processed "entry" and "item" tags.
   *
   * @var int
   */
  protected $item;

  /**
   * Implements \Drupal\aggregator\Plugin\ParserInterface::parse().
   */
  public function parse(Feed $feed) {
    // Filter the input data.
    if ($this->parseFeed($feed->source_string, $feed)) {

      // Prepare the channel data.
      foreach ($this->channel as $key => $value) {
        $this->channel[$key] = trim($value);
      }

      // Prepare the image data (if any).
      foreach ($this->image as $key => $value) {
        $this->image[$key] = trim($value);
      }

      // Add parsed data to the feed object.
      $feed->link->value = !empty($channel['link']) ? $channel['link'] : '';
      $feed->description->value = !empty($channel['description']) ? $channel['description'] : '';
      $feed->image->value = !empty($image['url']) ? $image['url'] : '';

      // Clear the page and block caches.
      cache_invalidate_tags(array('content' => TRUE));

      return TRUE;
    }

    return FALSE;
  }


  /**
   * Parses a feed and stores its items.
   *
   * @param string $data
   *   The feed data.
   * @param \Drupal\aggregator\Plugin\Core\Entity\Feed $feed
   *   An object describing the feed to be parsed.
   *
   * @return bool
   *   FALSE on error, TRUE otherwise.
   */
  protected function parseFeed(&$data, Feed $feed) {
    // Parse the data.
    $xml_parser = drupal_xml_parser_create($data);
    xml_set_element_handler($xml_parser, array($this, 'elementStart'), array($this, 'elementEnd'));
    xml_set_character_data_handler($xml_parser, array($this, 'elementData'));

    if (!xml_parse($xml_parser, $data, 1)) {
      watchdog('aggregator', 'The feed from %site seems to be broken due to an error "%error" on line %line.', array('%site' => $feed->label(), '%error' => xml_error_string(xml_get_error_code($xml_parser)), '%line' => xml_get_current_line_number($xml_parser)), WATCHDOG_WARNING);
      drupal_set_message(t('The feed from %site seems to be broken because of error "%error" on line %line.', array('%site' => $feed->label(), '%error' => xml_error_string(xml_get_error_code($xml_parser)), '%line' => xml_get_current_line_number($xml_parser))), 'error');
      return FALSE;
    }
    xml_parser_free($xml_parser);

    // We reverse the array such that we store the first item last, and the last
    // item first. In the database, the newest item should be at the top.
    $this->items = array_reverse($this->items);

    // Initialize items array.
    $feed->items = array();
    foreach ($this->items as $item) {

      // Prepare the item:
      foreach ($item as $key => $value) {
        $item[$key] = trim($value);
      }

      // Resolve the item's title. If no title is found, we use up to 40
      // characters of the description ending at a word boundary, but not
      // splitting potential entities.
      if (!empty($item['title'])) {
        $item['title'] = $item['title'];
      }
      elseif (!empty($item['description'])) {
        $item['title'] = preg_replace('/^(.*)[^\w;&].*?$/', "\\1", truncate_utf8($item['description'], 40));
      }
      else {
        $item['title'] = '';
      }

      // Resolve the items link.
      if (!empty($item['link'])) {
        $item['link'] = $item['link'];
      }
      else {
        $item['link'] = $feed->link->value;
      }

      // Atom feeds have an ID tag instead of a GUID tag.
      if (!isset($item['guid'])) {
        $item['guid'] = isset($item['id']) ? $item['id'] : '';
      }

      // Atom feeds have a content and/or summary tag instead of a description tag.
      if (!empty($item['content:encoded'])) {
        $item['description'] = $item['content:encoded'];
      }
      elseif (!empty($item['summary'])) {
        $item['description'] = $item['summary'];
      }
      elseif (!empty($item['content'])) {
        $item['description'] = $item['content'];
      }

      // Try to resolve and parse the item's publication date.
      $date = '';
      foreach (array('pubdate', 'dc:date', 'dcterms:issued', 'dcterms:created', 'dcterms:modified', 'issued', 'created', 'modified', 'published', 'updated') as $key) {
        if (!empty($item[$key])) {
          $date = $item[$key];
          break;
        }
      }

      $item['timestamp'] = strtotime($date);

      if ($item['timestamp'] === FALSE) {
        $item['timestamp'] = $this->parseW3cdtf($date); // Aggregator_parse_w3cdtf() returns FALSE on failure.
      }

      // Resolve dc:creator tag as the item author if author tag is not set.
      if (empty($item['author']) && !empty($item['dc:creator'])) {
        $item['author'] = $item['dc:creator'];
      }

      $item += array('author' => '', 'description' => '');

      // Store on $feed object. This is where processors will look for parsed items.
      $feed->items[] = $item;
    }

    return TRUE;
  }

  /**
   * XML parser callback: Perform an action when an opening tag is encountered.
   *
   * @param resource $parser
   *   A reference to the XML parser calling the handler.
   * @param string $name
   *   The name of the element for which this handler is called.
   * @param array $attributes
   *   An associative array with the element's attributes (if any).
   */
  protected function elementStart($parser, $name, $attributes) {
    $name = strtolower($name);
    switch ($name) {
      case 'image':
      case 'textinput':
      case 'summary':
      case 'tagline':
      case 'subtitle':
      case 'logo':
      case 'info':
        $this->element = $name;
        break;
      case 'id':
      case 'content':
        if ($this->element != 'item') {
          $this->element = $name;
        }
      case 'link':
        // According to RFC 4287, link elements in Atom feeds without a 'rel'
        // attribute should be interpreted as though the relation type is
        // "alternate".
        if (!empty($attributes['HREF']) && (empty($attributes['REL']) || $attributes['REL'] == 'alternate')) {
          if ($this->element == 'item') {
            $this->items[$this->item]['link'] = $attributes['HREF'];
          }
          else {
            $this->channel['link'] = $attributes['HREF'];
          }
        }
        break;
      case 'item':
        $this->element = $name;
        $this->item += 1;
        break;
      case 'entry':
        $this->element = 'item';
        $this->item += 1;
        break;
    }

    $this->tag = $name;
  }

  /**
   * XML parser callback: Perform an action when a closing tag is encountered.
   *
   * @param resource $parser
   *   A reference to the XML parser calling the handler.
   * @param string $name
   *   The name of the element for which this handler is called.
   * @param array $attributes
   *   An associative array with the element's attributes (if any).
   */
  protected function elementEnd($parser, $name) {
    switch ($name) {
      case 'image':
      case 'textinput':
      case 'item':
      case 'entry':
      case 'info':
        $this->element = '';
        break;
      case 'id':
      case 'content':
        if ($this->element == $name) {
          $this->element = '';
        }
    }
  }

  /**
   * XML parser callback: Perform an action when data is encountered.
   *
   * @param resource $parser
   *   A reference to the XML parser calling the handler.
   * @param string $name
   *   The name of the element for which this handler is called.
   * @param array $attributes
   *   An associative array with the element's attributes (if any).
   */
  function elementData($parser, $data) {
    $this->items += array($this->item => array());
    switch ($this->element) {
      case 'item':
        $this->items[$this->item] += array($this->tag => '');
        $this->items[$this->item][$this->tag] .= $data;
        break;
      case 'image':
      case 'logo':
        $this->image += array($this->tag => '');
        $this->image[$this->tag] .= $data;
        break;
      case 'link':
        if ($data) {
          $this->items[$this->item] += array($tag => '');
          $this->items[$this->item][$this->tag] .= $data;
        }
        break;
      case 'content':
        $this->items[$this->item] += array('content' => '');
        $this->items[$this->item]['content'] .= $data;
        break;
      case 'summary':
        $this->items[$this->item] += array('summary' => '');
        $this->items[$this->item]['summary'] .= $data;
        break;
      case 'tagline':
      case 'subtitle':
        $this->channel += array('description' => '');
        $this->channel['description'] .= $data;
        break;
      case 'info':
      case 'id':
      case 'textinput':
        // The sub-element is not supported. However, we must recognize
        // it or its contents will end up in the item array.
        break;
      default:
        $this->channel += array($this->tag => '');
        $this->channel[$this->tag] .= $data;
    }
  }

  /**
   * Parses the W3C date/time format, a subset of ISO 8601.
   *
   * PHP date parsing functions do not handle this format. See
   * http://www.w3.org/TR/NOTE-datetime for more information. Originally from
   * MagpieRSS (http://magpierss.sourceforge.net/).
   *
   * @param string $date_str
   *   A string with a potentially W3C DTF date.
   *
   * @return int|false
   *   A timestamp if parsed successfully or FALSE if not.
   */
  function parseW3cdtf($date_str) {
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
}
