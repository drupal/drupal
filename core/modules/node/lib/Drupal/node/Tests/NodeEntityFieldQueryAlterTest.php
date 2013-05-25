<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeEntityFieldQueryAlterTest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests node_query_entity_field_access_alter().
 */
class NodeEntityFieldQueryAlterTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node_access_test');

  public static function getInfo() {
    return array(
      'name' => 'Node entity query alter',
      'description' => 'Test that node access entity queries are properly altered by the node module.',
      'group' => 'Node',
    );
  }

  /**
   * User with permission to view content.
   */
  protected $accessUser;

  /**
   * User without permission to view content.
   */
  protected $noAccessUser;

  function setUp() {
    parent::setUp();
    node_access_rebuild();

    // Creating 4 nodes with an entity field so we can test that sort of query
    // alter. All field values starts with 'A' so we can identify and fetch them
    // in the node_access_test module.
    $settings = array('langcode' => Language::LANGCODE_NOT_SPECIFIED);
    for ($i = 0; $i < 4; $i++) {
      $body = array(
        'value' => 'A' . $this->randomName(32),
        'format' => filter_default_format(),
      );
      $settings['body'][0] = $body;
      $this->drupalCreateNode($settings);
    }

    // Create user with simple node access permission. The 'node test view'
    // permission is implemented and granted by the node_access_test module.
    $this->accessUser = $this->drupalCreateUser(array('access content', 'node test view'));
    $this->noAccessUser = $this->drupalCreateUser(array('access content'));
  }

  /**
   * Tests that node access permissions are followed.
   */
  function testNodeQueryAlterWithUI() {
    // Verify that a user with access permission can see at least one node.
    $this->drupalLogin($this->accessUser);
    $this->drupalGet('node_access_entity_test_page');
    $this->assertText('Yes, 4 nodes', "4 nodes were found for access user");
    $this->assertNoText('Exception', "No database exception");

    // Verify that a user with no access permission cannot see nodes.
    $this->drupalLogin($this->noAccessUser);
    $this->drupalGet('node_access_entity_test_page');
    $this->assertText('No nodes', "No nodes were found for no access user");
    $this->assertNoText('Exception', "No database exception");
  }
}
