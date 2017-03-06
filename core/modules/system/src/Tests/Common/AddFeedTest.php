<?php

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
  public function testBasicFeedAddNoTitle() {
    $path = $this->randomMachineName(12);
    $external_url = 'http://' . $this->randomMachineName(12) . '/' . $this->randomMachineName(12);
    $fully_qualified_local_url = Url::fromUri('base:' . $this->randomMachineName(12), ['absolute' => TRUE])->toString();

    $path_for_title = $this->randomMachineName(12);
    $external_for_title = 'http://' . $this->randomMachineName(12) . '/' . $this->randomMachineName(12);
    $fully_qualified_for_title = Url::fromUri('base:' . $this->randomMachineName(12), ['absolute' => TRUE])->toString();

    $urls = [
      'path without title' => [
        'url' => Url::fromUri('base:' . $path, ['absolute' => TRUE])->toString(),
        'title' => '',
      ],
      'external URL without title' => [
        'url' => $external_url,
        'title' => '',
      ],
      'local URL without title' => [
        'url' => $fully_qualified_local_url,
        'title' => '',
      ],
      'path with title' => [
        'url' => Url::fromUri('base:' . $path_for_title, ['absolute' => TRUE])->toString(),
        'title' => $this->randomMachineName(12),
      ],
      'external URL with title' => [
        'url' => $external_for_title,
        'title' => $this->randomMachineName(12),
      ],
      'local URL with title' => [
        'url' => $fully_qualified_for_title,
        'title' => $this->randomMachineName(12),
      ],
    ];

    $build = [];
    foreach ($urls as $feed_info) {
      $build['#attached']['feed'][] = [$feed_info['url'], $feed_info['title']];
    }

    // Use the bare HTML page renderer to render our links.
    $renderer = $this->container->get('bare_html_page_renderer');
    $response = $renderer->renderBarePage($build, '', 'maintenance_page');
    // Glean the content from the response object.
    $this->setRawContent($response->getContent());
    // Assert that the content contains the RSS links we specified.
    foreach ($urls as $description => $feed_info) {
      $this->assertPattern($this->urlToRSSLinkPattern($feed_info['url'], $feed_info['title']), format_string('Found correct feed header for %description', ['%description' => $description]));
    }
  }

  /**
   * Creates a pattern representing the RSS feed in the page.
   */
  public function urlToRSSLinkPattern($url, $title = '') {
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
  public function testFeedIconEscaping() {
    $variables = [
      '#theme' => 'feed_icon',
      '#url' => 'node',
      '#title' => '<>&"\'',
    ];
    $text = \Drupal::service('renderer')->renderRoot($variables);
    $this->assertEqual(trim(strip_tags($text)), 'Subscribe to &lt;&gt;&amp;&quot;&#039;', 'feed_icon template escapes reserved HTML characters.');
  }

}
