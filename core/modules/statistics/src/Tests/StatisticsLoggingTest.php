<?php

namespace Drupal\statistics\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests request logging for cached and uncached pages.
 *
 * We subclass WebTestBase rather than StatisticsTestBase, because we
 * want to test requests from an anonymous user.
 *
 * @group statistics
 */
class StatisticsLoggingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'statistics', 'block', 'locale'];

  /**
   * User with permissions to create and edit pages.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authUser;

  /**
   * Associative array representing a hypothetical Drupal language.
   *
   * @var array
   */
  protected $language;

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\Client;
   */
  protected $client;

  protected function setUp() {
    parent::setUp();

    // Create Basic page node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    }

    $this->authUser = $this->drupalCreateUser([
      // For node creation.
      'access content',
      'create page content',
      'edit own page content',
      // For language negotiation administration.
      'administer languages',
      'access administration pages',
    ]);

    // Ensure we have a node page to access.
    $this->node = $this->drupalCreateNode(['title' => $this->randomMachineName(255), 'uid' => $this->authUser->id()]);

    // Add a custom language and enable path-based language negotiation.
    $this->drupalLogin($this->authUser);
    $this->language = [
      'predefined_langcode' => 'custom',
      'langcode' => 'xx',
      'label' => $this->randomMachineName(16),
      'direction' => 'ltr',
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $this->language, t('Add custom language'));
    $this->drupalPostForm('admin/config/regional/language/detection', ['language_interface[enabled][language-url]' => 1], t('Save settings'));
    $this->drupalLogout();

    // Enable access logging.
    $this->config('statistics.settings')
      ->set('count_content_views', 1)
      ->save();

    // Clear the logs.
    db_truncate('node_counter');
    $this->client = \Drupal::httpClient();
  }

  /**
   * Verifies node hit counter logging and script placement.
   */
  public function testLogging() {
    $path = 'node/' . $this->node->id();
    $module_path = drupal_get_path('module', 'statistics');
    $stats_path = base_path() . $module_path . '/statistics.php';
    $lib_path = base_path() . $module_path . '/statistics.js';
    $expected_library = '/<script src=".*?' . preg_quote($lib_path, '/.') . '.*?">/is';

    // Verify that logging scripts are not found on a non-node page.
    $this->drupalGet('node');
    $settings = $this->getDrupalSettings();
    $this->assertNoPattern($expected_library, 'Statistics library JS not found on node page.');
    $this->assertFalse(isset($settings['statistics']), 'Statistics settings not found on node page.');

    // Verify that logging scripts are not found on a non-existent node page.
    $this->drupalGet('node/9999');
    $settings = $this->getDrupalSettings();
    $this->assertNoPattern($expected_library, 'Statistics library JS not found on non-existent node page.');
    $this->assertFalse(isset($settings['statistics']), 'Statistics settings not found on node page.');

    // Verify that logging scripts are found on a valid node page.
    $this->drupalGet($path);
    $settings = $this->getDrupalSettings();
    $this->assertPattern($expected_library, 'Found statistics library JS on node page.');
    $this->assertIdentical($this->node->id(), $settings['statistics']['data']['nid'], 'Found statistics settings on node page.');

    // Verify the same when loading the site in a non-default language.
    $this->drupalGet($this->language['langcode'] . '/' . $path);
    $settings = $this->getDrupalSettings();
    $this->assertPattern($expected_library, 'Found statistics library JS on a valid node page in a non-default language.');
    $this->assertIdentical($this->node->id(), $settings['statistics']['data']['nid'], 'Found statistics settings on valid node page in a non-default language.');

    // Manually call statistics.php to simulate ajax data collection behavior.
    global $base_root;
    $post = ['nid' => $this->node->id()];
    $this->client->post($base_root . $stats_path, ['form_params' => $post]);
    $node_counter = statistics_get($this->node->id());
    $this->assertIdentical($node_counter['totalcount'], '1');
  }

}
