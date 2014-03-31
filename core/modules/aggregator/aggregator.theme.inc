<?php

/**
 * @file
 * Preprocessors and theme functions of Aggregator module.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Render\Element;

/**
 * Prepares variables for aggregator item templates.
 *
 * Default template: aggregator-item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - aggregator_item: An individual feed item for display on the aggregator
 *     page.
 */
function template_preprocess_aggregator_item(&$variables) {
  $item = $variables['aggregator_item'];

  $variables['feed_url'] = check_url($item->getLink());
  $variables['feed_title'] = String::checkPlain($item->getTitle());
  $variables['content'] = aggregator_filter_xss($item->getDescription());

  $variables['source_url'] = '';
  $variables['source_title'] = '';
  $fid = $item->getFeedId();
  if (isset($item->ftitle) && $fid !== NULL) {
    $variables['source_url'] = url('aggregator/sources/' . $fid);
    $variables['source_title'] = String::checkPlain($item->ftitle);
  }
  if (date('Ymd', $item->getPostedTime()) == date('Ymd')) {
    $variables['source_date'] = t('%ago ago', array('%ago' => format_interval(REQUEST_TIME - $item->getPostedTime())));
  }
  else {
    $variables['source_date'] = format_date($item->getPostedTime(), 'medium');
  }

  $variables['attributes']['class'][] = 'feed-item';
}

/**
 * Prints the OPML page for the feed.
 *
 * @param array $variables
 *   An associative array containing:
 *   - feeds: An array of the feeds to theme.
 *
 * @return string
 *   An OPML formatted string.
 *
 * @ingroup themeable
 */
function theme_aggregator_page_opml($variables) {
  $feeds = $variables['feeds'];

  $output  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
  $output .= "<opml version=\"1.1\">\n";
  $output .= "<head>\n";
  $output .= '<title>' . String::checkPlain(\Drupal::config('system.site')->get('name')) . "</title>\n";
  $output .= '<dateModified>' . gmdate(DATE_RFC2822, REQUEST_TIME) . "</dateModified>\n";
  $output .= "</head>\n";
  $output .= "<body>\n";
  foreach ($feeds as $feed) {
    $output .= '<outline text="' . String::checkPlain($feed->label()) . '" xmlUrl="' . check_url($feed->getUrl()) . "\" />\n";
  }
  $output .= "</body>\n";
  $output .= "</opml>\n";

  return $output;
}

/**
 * Prepares variables for aggregator summary templates.
 *
 * Default template: aggregator-summary-items.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - source: A Drupal\aggregator\FeedInterface object representing the feed
 *     source.
 *   - summary_items: An array of feed items.
 */
function template_preprocess_aggregator_summary_items(&$variables) {
  $variables['title'] = String::checkPlain($variables['source']->label());
  $summary_items = array();
  foreach (Element::children($variables['summary_items']) as $key) {
    $summary_items[] = $variables['summary_items'][$key];
  }
  $variables['summary_list'] = array(
    '#theme' => 'item_list',
    '#items' => $summary_items,
  );
  $variables['source_url'] = $variables['source']->getUrl();
}

/**
 * Processes variables for aggregator summary item templates.
 *
 * Default template: aggregator-summary-item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - aggregator_item: The feed item.
 *   - view_mode: How the item is being displayed.
 */
function template_preprocess_aggregator_summary_item(&$variables) {
  $item = $variables['aggregator_item'];

  $variables['url'] = l(String::checkPlain($item->label()), check_url(url($item->getLink(), array('absolute' => TRUE))), array(
    'attributes' => array(
      'class' => array('feed-item-url'),
    ),
  ));
  $variables['age'] = array(
    '#theme' => 'datetime',
    '#attributes' => array(
      'datetime' => format_date($item->getPostedTime(), 'html_datetime', '', 'UTC'),
      'class' => array('feed-item-age',),
    ),
    '#text' => t('%age old', array('%age' => format_interval(REQUEST_TIME - $item->getPostedTime()))),
    '#html' => TRUE,
  );
}

/**
 * Prepares variables for aggregator feed source templates.
 *
 * Default template: aggregator-feed-source.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - aggregator_feed: A Drupal\aggregator\FeedInterface object representing
 *     the feed source.
 */
function template_preprocess_aggregator_feed_source(&$variables) {
  $feed = $variables['aggregator_feed'];

  $feed_icon = array(
    '#theme' => 'feed_icon',
    '#url' => $feed->getUrl(),
    '#title' => t('!title feed', array('!title' => $feed->label())),
  );
  $variables['source_icon'] = drupal_render($feed_icon);

  if ($feed->getImage() && $feed->label() && $feed->getWebsiteUrl()) {
    $image = array(
      '#theme' => 'image',
      '#path' => $feed->getImage(),
      '#alt' => $feed->label(),
    );
    $variables['source_image'] = l($image, $feed->getWebsiteUrl(), array('html' => TRUE, 'attributes' => array('class' => 'feed-image')));
  }
  else {
    $variables['source_image'] = '';
  }
  $variables['source_description'] = aggregator_filter_xss($feed->getDescription());
  $variables['source_url'] = check_url(url($feed->getWebsiteUrl(), array('absolute' => TRUE)));

  if ($feed->checked) {
    $variables['last_checked'] = t('@time ago', array('@time' => format_interval(REQUEST_TIME - $feed->getLastCheckedTime())));
  }
  else {
    $variables['last_checked'] = t('never');
  }

  if (\Drupal::currentUser()->hasPermission('administer news feeds')) {
    $variables['last_checked'] = l($variables['last_checked'], 'admin/config/services/aggregator');
  }

  $variables['attributes']['class'][] = 'feed-source';
}

/**
 * Prepares variables for individual feed item block templates.
 *
 * Default template: aggregator-block-item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: The item to be displayed.
 *   - feed: Not used.
 */
function template_preprocess_aggregator_block_item(&$variables) {
  // Display the external link to the item.
  $item = $variables['item'];
  $variables['url'] = check_url($item->getLink());
  $variables['title'] = String::checkPlain($item->label());
}
