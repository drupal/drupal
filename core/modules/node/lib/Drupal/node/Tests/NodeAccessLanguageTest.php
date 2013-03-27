<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeAccessLanguageTest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Language\Language;

/**
 * Verifies node_access() functionality for multiple languages.
 */
class NodeAccessLanguageTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'node_access_test');

  public static function getInfo() {
    return array(
      'name' => 'Node access language',
      'description' => 'Test node_access functionality with multiple languages.',
      'group' => 'Node',
    );
  }

  /**
   * Asserts node_access correctly grants or denies access.
   */
  function assertNodeAccess($ops, $node, $account, $langcode = NULL) {
    foreach ($ops as $op => $result) {
      $msg = t("node_access returns @result with operation '@op', language code @langcode.", array('@result' => $result ? 'true' : 'false', '@op' => $op, '@langcode' => !empty($langcode) ? "'$langcode'" : 'empty'));
      $this->assertEqual($result, node_access($op, $node, $account, $langcode), $msg);
    }
  }

  function setUp() {
    parent::setUp();

    // Clear permissions for authenticated users.
    db_delete('role_permission')
      ->condition('rid', DRUPAL_AUTHENTICATED_RID)
      ->execute();
  }

  /**
   * Runs tests for node_access function with multiple languages.
   */
  function testNodeAccess() {
    // Add Hungarian and Catalan.
    $language = new Language(array(
      'langcode' => 'hu',
    ));
    language_save($language);
    $language = new Language(array(
      'langcode' => 'ca',
    ));
    language_save($language);

    // Tests the default access provided for a published Hungarian node.
    $web_user = $this->drupalCreateUser(array('access content'));
    $node = $this->drupalCreateNode(array('body' => array(array()), 'langcode' => 'hu'));
    $this->assertTrue($node->langcode == 'hu', 'Node created as Hungarian.');
    $expected_node_access = array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE);
    $this->assertNodeAccess($expected_node_access, $node, $web_user);

    // Tests that Hungarian provided specifically results in the same.
    $this->assertNodeAccess($expected_node_access, $node, $web_user, 'hu');

    // There is no specific Catalan version of this node and Croatian is not
    // even set up on the system in this scenario, so these languages will not
    // play a role in the node's permissions.
    $this->assertNodeAccess($expected_node_access, $node, $web_user, 'ca');
    $this->assertNodeAccess($expected_node_access, $node, $web_user, 'hr');

    // Reset the node access cache and turn on our test node_access() code.
    entity_access_controller('node')->resetCache();
    state()->set('node_access_test_secret_catalan', 1);

    // Tests that Hungarian is still accessible.
    $this->assertNodeAccess($expected_node_access, $node, $web_user, 'hu');

    // Tests that Catalan is not accessible anymore.
    $this->assertNodeAccess(array('view' => FALSE, 'update' => FALSE, 'delete' => FALSE), $node, $web_user, 'ca');
  }
}
