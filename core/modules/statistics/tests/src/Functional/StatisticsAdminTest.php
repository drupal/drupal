<?php

namespace Drupal\Tests\statistics\Functional;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests the statistics admin.
 *
 * @group statistics
 */
class StatisticsAdminTest extends BrowserTestBase {

  use CronRunTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'statistics'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user that has permission to administer statistics.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $privilegedUser;

  /**
   * A page node for which to check content statistics.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $testNode;

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set the max age to 0 to simplify testing.
    $this->config('statistics.settings')->set('display_max_age', 0)->save();

    // Create Basic page node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    }
    $this->privilegedUser = $this->drupalCreateUser([
      'administer statistics',
      'view post access counter',
      'create page content',
    ]);
    $this->drupalLogin($this->privilegedUser);
    $this->testNode = $this->drupalCreateNode(['type' => 'page', 'uid' => $this->privilegedUser->id()]);
    $this->client = \Drupal::httpClient();
  }

  /**
   * Verifies that the statistics settings page works.
   */
  public function testStatisticsSettings() {
    $config = $this->config('statistics.settings');
    $this->assertEmpty($config->get('count_content_views'), 'Count content view log is disabled by default.');

    // Enable counter on content view.
    $edit['statistics_count_content_views'] = 1;
    $this->drupalGet('admin/config/system/statistics');
    $this->submitForm($edit, 'Save configuration');
    $config = $this->config('statistics.settings');
    $this->assertNotEmpty($config->get('count_content_views'), 'Count content view log is enabled.');

    // Hit the node.
    $this->drupalGet('node/' . $this->testNode->id());
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $this->testNode->id();
    $post = ['nid' => $nid];
    global $base_url;
    $stats_path = $base_url . '/' . $this->getModulePath('statistics') . '/statistics.php';
    $this->client->post($stats_path, ['form_params' => $post]);

    // Hit the node again (the counter is incremented after the hit, so
    // "1 view" will actually be shown when the node is hit the second time).
    $this->drupalGet('node/' . $this->testNode->id());
    $this->client->post($stats_path, ['form_params' => $post]);
    $this->assertSession()->pageTextContains('1 view');

    $this->drupalGet('node/' . $this->testNode->id());
    $this->client->post($stats_path, ['form_params' => $post]);
    $this->assertSession()->pageTextContains('2 views');

    // Increase the max age to test that nodes are no longer immediately
    // updated, visit the node once more to populate the cache.
    $this->config('statistics.settings')->set('display_max_age', 3600)->save();
    $this->drupalGet('node/' . $this->testNode->id());
    $this->assertSession()->pageTextContains('3 views');

    $this->client->post($stats_path, ['form_params' => $post]);
    $this->drupalGet('node/' . $this->testNode->id());
    // Verify that views counter was not updated.
    $this->assertSession()->pageTextContains('3 views');
  }

  /**
   * Tests that when a node is deleted, the node counter is deleted too.
   */
  public function testDeleteNode() {
    $this->config('statistics.settings')->set('count_content_views', 1)->save();

    $this->drupalGet('node/' . $this->testNode->id());
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $this->testNode->id();
    $post = ['nid' => $nid];
    global $base_url;
    $stats_path = $base_url . '/' . $this->getModulePath('statistics') . '/statistics.php';
    $this->client->post($stats_path, ['form_params' => $post]);

    $connection = Database::getConnection();
    $result = $connection->select('node_counter', 'n')
      ->fields('n', ['nid'])
      ->condition('n.nid', $this->testNode->id())
      ->execute()
      ->fetchAssoc();
    $this->assertEquals($result['nid'], $this->testNode->id(), 'Verifying that the node counter is incremented.');

    $this->testNode->delete();

    $result = $connection->select('node_counter', 'n')
      ->fields('n', ['nid'])
      ->condition('n.nid', $this->testNode->id())
      ->execute()
      ->fetchAssoc();
    $this->assertFalse($result, 'Verifying that the node counter is deleted.');
  }

  /**
   * Tests that cron clears day counts and expired access logs.
   */
  public function testExpiredLogs() {
    $this->config('statistics.settings')
      ->set('count_content_views', 1)
      ->save();
    \Drupal::state()->set('statistics.day_timestamp', 8640000);

    $this->drupalGet('node/' . $this->testNode->id());
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $this->testNode->id();
    $post = ['nid' => $nid];
    global $base_url;
    $stats_path = $base_url . '/' . $this->getModulePath('statistics') . '/statistics.php';
    $this->client->post($stats_path, ['form_params' => $post]);
    $this->drupalGet('node/' . $this->testNode->id());
    $this->client->post($stats_path, ['form_params' => $post]);
    $this->assertSession()->pageTextContains('1 view');

    // statistics_cron() will subtract
    // statistics.settings:accesslog.max_lifetime config from REQUEST_TIME in
    // the delete query, so wait two secs here to make sure the access log will
    // be flushed for the node just hit.
    sleep(2);
    $this->cronRun();

    // Verify that no hit URL is found.
    $this->drupalGet('admin/reports/pages');
    $this->assertSession()->pageTextNotContains('node/' . $this->testNode->id());

    $result = Database::getConnection()->select('node_counter', 'nc')
      ->fields('nc', ['daycount'])
      ->condition('nid', $this->testNode->id(), '=')
      ->execute()
      ->fetchField();
    $this->assertEmpty($result, 'Daycount is zero.');
  }

}
