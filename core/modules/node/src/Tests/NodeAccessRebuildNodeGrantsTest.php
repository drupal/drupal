<?php

namespace Drupal\node\Tests;

/**
 * Ensures that node access rebuild functions work correctly even
 * when other modules implements hook_node_grants().
 *
 * @group node
 */
class NodeAccessRebuildNodeGrantsTest extends NodeTestBase {

  /**
   * A user to test the rebuild nodes feature.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(array('administer site configuration', 'access administration pages', 'access site reports', 'bypass node access'));
    $this->drupalLogin($admin_user);

    $this->webUser = $this->drupalCreateUser();
  }

  /**
   * Tests rebuilding the node access permissions table with content.
   */
  public function testNodeAccessRebuildNodeGrants() {
    \Drupal::service('module_installer')->install(['node_access_test']);
    $this->resetAll();

    $node = $this->drupalCreateNode(array(
      'uid' => $this->webUser->id(),
    ));

    // Default realm access and node records are present.
    $this->assertTrue(\Drupal::service('node.grant_storage')->access($node, 'view', $this->webUser), 'The expected node access records are present');
    $this->assertEqual(1, \Drupal::service('node.grant_storage')->checkAll($this->webUser), 'There is an all realm access record');
    $this->assertTrue(\Drupal::state()->get('node.node_access_needs_rebuild'), 'Node access permissions need to be rebuilt');

    // Rebuild permissions.
    $this->drupalGet('admin/reports/status/rebuild');
    $this->drupalPostForm(NULL, array(), t('Rebuild permissions'));
    $this->assertText(t('The content access permissions have been rebuilt.'));

    // Test if the rebuild has been successful.
    $this->assertNull(\Drupal::state()->get('node.node_access_needs_rebuild'), 'Node access permissions have been rebuilt');
    $this->assertTrue(\Drupal::service('node.grant_storage')->access($node, 'view', $this->webUser), 'The expected node access records are present');
    $this->assertFalse(\Drupal::service('node.grant_storage')->checkAll($this->webUser), 'There is no all realm access record');
  }

  /**
   * Tests rebuilding the node access permissions table with no content.
   */
  public function testNodeAccessRebuildNoAccessModules() {
    // Default realm access is present.
    $this->assertEqual(1, \Drupal::service('node.grant_storage')->count(), 'There is an all realm access record');

    // No need to rebuild permissions.
    $this->assertFalse(\Drupal::state()->get('node.node_access_needs_rebuild'), 'Node access permissions need to be rebuilt');

    // Rebuild permissions.
    $this->drupalGet('admin/reports/status');
    $this->clickLink(t('Rebuild permissions'));
    $this->drupalPostForm(NULL, array(), t('Rebuild permissions'));
    $this->assertText(t('Content permissions have been rebuilt.'));
    $this->assertNull(\Drupal::state()->get('node.node_access_needs_rebuild'), 'Node access permissions have been rebuilt');

    // Default realm access is still present.
    $this->assertEqual(1, \Drupal::service('node.grant_storage')->count(), 'There is an all realm access record');
  }

}
