<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\AddFeedTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Core\Page\FeedLinkElement;
use Drupal\Core\Page\HtmlPage;
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
    $fully_qualified_local_url = _url($this->randomMachineName(12), array('absolute' => TRUE));

    $path_for_title = $this->randomMachineName(12);
    $external_for_title = 'http://' . $this->randomMachineName(12) . '/' . $this->randomMachineName(12);
    $fully_qualified_for_title = _url($this->randomMachineName(12), array('absolute' => TRUE));

    // Possible permutations of _drupal_add_feed() to test.
    // - 'input_url': the path passed to _drupal_add_feed(),
    // - 'output_url': the expected URL to be found in the header.
    // - 'title' == the title of the feed as passed into _drupal_add_feed().
    $urls = array(
      'path without title' => array(
        'url' => _url($path, array('absolute' => TRUE)),
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
        'url' => _url($path_for_title, array('absolute' => TRUE)),
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

    $html_page = new HtmlPage();

    foreach ($urls as $feed_info) {
      $feed_link = new FeedLinkElement($feed_info['title'], $feed_info['url']);
      $html_page->addLinkElement($feed_link);
    }

    $this->drupalSetContent(\Drupal::service('html_page_renderer')->render($html_page));
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
    $generated_pattern = '%<link +title="' . $title . '" +type="application/rss.xml" +href="' . $url . '" +rel="alternate" */>%';
    return $generated_pattern;
  }

  /**
   * Checks that special characters are correctly escaped.
   *
   * @see http://drupal.org/node/1211668
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
