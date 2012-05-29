<?php

/**
 * @file
 * Definition of Drupal\statistics\Tests\StatisticsAdminTest.
 */

namespace Drupal\statistics\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the statistics administration screen.
 */
class StatisticsAdminTest extends WebTestBase {

  /**
   * A user that has permission to administer and access statistics.
   *
   * @var object|FALSE
   *
   * A fully loaded user object, or FALSE if user creation failed.
   */
  protected $privileged_user;

  /**
   * A page node for which to check access statistics.
   *
   * @var object
   */
  protected $test_node;

  public static function getInfo() {
    return array(
      'name' => 'Test statistics admin.',
      'description' => 'Tests the statistics admin.',
      'group' => 'Statistics'
    );
  }

  function setUp() {
    parent::setUp(array('node', 'statistics'));

    // Create Basic page node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    }
    $this->privileged_user = $this->drupalCreateUser(array('access statistics', 'administer statistics', 'view post access counter', 'create page content'));
    $this->drupalLogin($this->privileged_user);
    $this->test_node = $this->drupalCreateNode(array('type' => 'page', 'uid' => $this->privileged_user->uid));
  }

  /**
   * Verifies that the statistics settings page works.
   */
  function testStatisticsSettings() {
    $this->assertFalse(variable_get('statistics_enable_access_log', 0), t('Access log is disabled by default.'));
    $this->assertFalse(variable_get('statistics_count_content_views', 0), t('Count content view log is disabled by default.'));

    $this->drupalGet('admin/reports/pages');
    $this->assertRaw(t('No statistics available.'), t('Verifying text shown when no statistics is available.'));

    // Enable access log and counter on content view.
    $edit['statistics_enable_access_log'] = 1;
    $edit['statistics_count_content_views'] = 1;
    $this->drupalPost('admin/config/system/statistics', $edit, t('Save configuration'));
    $this->assertTrue(variable_get('statistics_enable_access_log'), t('Access log is enabled.'));
    $this->assertTrue(variable_get('statistics_count_content_views'), t('Count content view log is enabled.'));

    // Hit the node.
    $this->drupalGet('node/' . $this->test_node->nid);
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $this->test_node->nid;
    $post = http_build_query(array('nid' => $nid));
    $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
    global $base_url;
    $stats_path = $base_url . '/' . drupal_get_path('module', 'statistics'). '/statistics.php';
    drupal_http_request($stats_path, array('method' => 'POST', 'data' => $post, 'headers' => $headers, 'timeout' => 10000));

    $this->drupalGet('admin/reports/pages');
    $this->assertText('node/1', t('Test node found.'));

    // Hit the node again (the counter is incremented after the hit, so
    // "1 view" will actually be shown when the node is hit the second time).
    $this->drupalGet('node/' . $this->test_node->nid);
    drupal_http_request($stats_path, array('method' => 'POST', 'data' => $post, 'headers' => $headers, 'timeout' => 10000));
    $this->assertText('1 view', t('Node is viewed once.'));

    $this->drupalGet('node/' . $this->test_node->nid);
    drupal_http_request($stats_path, array('method' => 'POST', 'data' => $post, 'headers' => $headers, 'timeout' => 10000));
    $this->assertText('2 views', t('Node is viewed 2 times.'));
  }

  /**
   * Tests that when a node is deleted, the node counter is deleted too.
   */
  function testDeleteNode() {
    variable_set('statistics_count_content_views', 1);

    $this->drupalGet('node/' . $this->test_node->nid);
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $this->test_node->nid;
    $post = http_build_query(array('nid' => $nid));
    $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
    global $base_url;
    $stats_path = $base_url . '/' . drupal_get_path('module', 'statistics'). '/statistics.php';
    drupal_http_request($stats_path, array('method' => 'POST', 'data' => $post, 'headers' => $headers, 'timeout' => 10000));

    $result = db_select('node_counter', 'n')
      ->fields('n', array('nid'))
      ->condition('n.nid', $this->test_node->nid)
      ->execute()
      ->fetchAssoc();
    $this->assertEqual($result['nid'], $this->test_node->nid, 'Verifying that the node counter is incremented.');

    node_delete($this->test_node->nid);

    $result = db_select('node_counter', 'n')
      ->fields('n', array('nid'))
      ->condition('n.nid', $this->test_node->nid)
      ->execute()
      ->fetchAssoc();
    $this->assertFalse($result, 'Verifying that the node counter is deleted.');
  }

  /**
   * Tests that accesslog reflects when a user is deleted.
   */
  function testDeleteUser() {
    variable_set('statistics_enable_access_log', 1);

    variable_set('user_cancel_method', 'user_cancel_delete');
    $this->drupalLogout($this->privileged_user);
    $account = $this->drupalCreateUser(array('access content', 'cancel account'));
    $this->drupalLogin($account);
    $this->drupalGet('node/' . $this->test_node->nid);

    $account = user_load($account->uid, TRUE);

    $this->drupalGet('user/' . $account->uid . '/edit');
    $this->drupalPost(NULL, NULL, t('Cancel account'));

    $timestamp = time();
    $this->drupalPost(NULL, NULL, t('Cancel account'));
    // Confirm account cancellation request.
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    preg_match('@http.+?(user/\d+/cancel/confirm/\d+/[^\s]+)@', $mail['body'], $matches);
    $path = $matches[1];
    $this->drupalGet($path);
    $this->assertFalse(user_load($account->uid, TRUE), t('User is not found in the database.'));

    $this->drupalGet('admin/reports/visitors');
    $this->assertNoText($account->name, t('Did not find user in visitor statistics.'));
  }

  /**
   * Tests that cron clears day counts and expired access logs.
   */
  function testExpiredLogs() {
    variable_set('statistics_enable_access_log', 1);
    variable_set('statistics_count_content_views', 1);
    variable_set('statistics_day_timestamp', 8640000);
    variable_set('statistics_flush_accesslog_timer', 1);

    $this->drupalGet('node/' . $this->test_node->nid);
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $this->test_node->nid;
    $post = http_build_query(array('nid' => $nid));
    $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
    global $base_url;
    $stats_path = $base_url . '/' . drupal_get_path('module', 'statistics'). '/statistics.php';
    drupal_http_request($stats_path, array('method' => 'POST', 'data' => $post, 'headers' => $headers, 'timeout' => 10000));
    $this->drupalGet('node/' . $this->test_node->nid);
    drupal_http_request($stats_path, array('method' => 'POST', 'data' => $post, 'headers' => $headers, 'timeout' => 10000));
    $this->assertText('1 view', t('Node is viewed once.'));

    $this->drupalGet('admin/reports/pages');
    $this->assertText('node/' . $this->test_node->nid, t('Hit URL found.'));

    // statistics_cron will subtract the statistics_flush_accesslog_timer
    // variable from REQUEST_TIME in the delete query, so wait two secs here to
    // make sure the access log will be flushed for the node just hit.
    sleep(2);
    $this->cronRun();

    $this->drupalGet('admin/reports/pages');
    $this->assertNoText('node/' . $this->test_node->nid, t('No hit URL found.'));

    $result = db_select('node_counter', 'nc')
      ->fields('nc', array('daycount'))
      ->condition('nid', $this->test_node->nid, '=')
      ->execute()
      ->fetchField();
    $this->assertFalse($result, t('Daycounter is zero.'));
  }
}
