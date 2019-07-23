<?php

namespace Drupal\Tests\statistics\Functional;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Generates text using placeholders for dummy content to check statistics token
 * replacement.
 *
 * @group statistics
 */
class StatisticsTokenReplaceTest extends StatisticsTestBase {

  /**
   * Creates a node, then tests the statistics tokens generated from it.
   */
  public function testStatisticsTokenReplacement() {
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    // Create user and node.
    $user = $this->drupalCreateUser(['create page content']);
    $this->drupalLogin($user);
    $node = $this->drupalCreateNode(['type' => 'page', 'uid' => $user->id()]);

    // Hit the node.
    $this->drupalGet('node/' . $node->id());
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $node->id();
    $post = http_build_query(['nid' => $nid]);
    $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
    global $base_url;
    $stats_path = $base_url . '/' . drupal_get_path('module', 'statistics') . '/statistics.php';
    $client = \Drupal::httpClient();
    $client->post($stats_path, ['headers' => $headers, 'body' => $post]);
    /** @var \Drupal\statistics\StatisticsViewsResult $statistics */
    $statistics = \Drupal::service('statistics.storage.node')->fetchView($node->id());

    // Generate and test tokens.
    $tests = [];
    $tests['[node:total-count]'] = 1;
    $tests['[node:day-count]'] = 1;
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = $this->container->get('date.formatter');
    $tests['[node:last-view]'] = $date_formatter->format($statistics->getTimestamp());
    $tests['[node:last-view:short]'] = $date_formatter->format($statistics->getTimestamp(), 'short');

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = \Drupal::token()->replace($input, ['node' => $node], ['langcode' => $language_interface->getId()]);
      $this->assertEqual($output, $expected, new FormattableMarkup('Statistics token %token replaced.', ['%token' => $input]));
    }
  }

}
