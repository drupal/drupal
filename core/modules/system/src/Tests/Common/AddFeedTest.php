<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\AddFeedTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Make sure that attaching feeds works correctly with various constructs.
 *
 * @group Common
 */
class AddFeedTest extends WebTestBase {

  /**
   * Tests attaching feeds with paths, URLs, and titles.
   */
  function testBasicFeedAddNoTitle() {
    $path = $this->randomMachineName(12);
    $external_url = 'http://' . $this->randomMachineName(12) . '/' . $this->randomMachineName(12);
    $fully_qualified_local_url = Url::fromUri('base:' . $this->randomMachineName(12), array('absolute' => TRUE))->toString();

    $path_for_title = $this->randomMachineName(12);
    $external_for_title = 'http://' . $this->randomMachineName(12) . '/' . $this->randomMachineName(12);
    $fully_qualified_for_title = Url::fromUri('base:' . $this->randomMachineName(12), array('absolute' => TRUE))->toString();

    $urls = array(
      'path without title' => array(
        'url' => Url::fromUri('base:' . $path, array('absolute' => TRUE))->toString(),
        'title' => '',
      ),
      'external URL without title' => array(
        'url' => $external_url,
        'title' => '',
      ),
      'local URL without title' => array(
        'url' => $fully_qualified_local_url,
        'title' => '',
      ),
      'path with title' => array(
        'url' => Url::fromUri('base:' . $path_for_title, array('absolute' => TRUE))->toString(),
        'title' => $this->randomMachineName(12),
      ),
      'external URL with title' => array(
        'url' => $external_for_title,
        'title' => $this->randomMachineName(12),
      ),
      'local URL with title' => array(
        'url' => $fully_qualified_for_title,
        'title' => $this->randomMachineName(12),
      ),
    );

    $build = [];
    foreach ($urls as $feed_info) {
      $build['#attached']['feed'][] = [$feed_info['url'], $feed_info['title']];
    }

    drupal_process_attached($build);

    $this->setRawContent(drupal_get_html_head());
    foreach ($urls as $description => $feed_info) {
      $this->assertPattern($this->urlToRSSLinkPattern($feed_info['url'], $feed_info['title']), format_string('Found correct feed header for %description', array('%description' => $description)));
    }
  }

  /**
   * Creates a pattern representing the RSS feed in the page.
   */
  function urlToRSSLinkPattern($url, $title = '') {
    // Escape any regular expression characters in the URL ('?' is the worst).
    $url = preg_replace('/([+?.*])/', '[$0]', $url);
    $generated_pattern = '%<link +href="' . $url . '" +rel="alternate" +title="' . $title . '" +type="application/rss.xml" */>%';
    return $generated_pattern;
  }

  /**
   * Checks that special characters are correctly escaped.
   *
   * @see https://www.drupal.org/node/1211668
   */
  function testFeedIconEscaping() {
    $variables = array(
      '#theme' => 'feed_icon',
      '#url' => 'node',
      '#title' => '<>&"\'',
    );
    $text = drupal_render($variables);
    preg_match('/title="(.*?)"/', $text, $matches);
    $this->assertEqual($matches[1], 'Subscribe to &amp;&quot;&#039;', 'feed_icon template escapes reserved HTML characters.');
  }
}
