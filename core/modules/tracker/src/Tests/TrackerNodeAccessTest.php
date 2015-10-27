<?php

/**
 * @file
 * Contains \Drupal\tracker\Tests\TrackerNodeAccessTest.
 */

namespace Drupal\tracker\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for private node access on /tracker.
 *
 * @group tracker
 */
class TrackerNodeAccessTest extends WebTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'comment', 'tracker', 'node_access_test');

  protected function setUp() {
    parent::setUp();
    node_access_rebuild();
    $this->drupalCreateContentType(array('type' => 'page'));
    node_access_test_add_field(NodeType::load('page'));
    $this->addDefaultCommentField('node', 'page', 'comment', CommentItemInterface::OPEN);
    \Drupal::state()->set('node_access_test.private', TRUE);
  }

  /**
   * Ensure private node on /tracker is only visible to users with permission.
   */
  function testTrackerNodeAccess() {
    // Create user with node test view permission.
    $access_user = $this->drupalCreateUser(array('node test view', 'access user profiles'));

    // Create user without node test view permission.
    $no_access_user = $this->drupalCreateUser(array('access user profiles'));

    $this->drupalLogin($access_user);

    // Create some nodes.
    $private_node = $this->drupalCreateNode(array(
      'title' => t('Private node test'),
      'private' => TRUE,
    ));
    $public_node = $this->drupalCreateNode(array(
      'title' => t('Public node test'),
      'private' => FALSE,
    ));

    // User with access should see both nodes created.
    $this->drupalGet('activity');
    $this->assertText($private_node->getTitle(), 'Private node is visible to user with private access.');
    $this->assertText($public_node->getTitle(), 'Public node is visible to user with private access.');
    $this->drupalGet('user/' . $access_user->id() . '/activity');
    $this->assertText($private_node->getTitle(), 'Private node is visible to user with private access.');
    $this->assertText($public_node->getTitle(), 'Public node is visible to user with private access.');

    // User without access should not see private node.
    $this->drupalLogin($no_access_user);
    $this->drupalGet('activity');
    $this->assertNoText($private_node->getTitle(), 'Private node is not visible to user without private access.');
    $this->assertText($public_node->getTitle(), 'Public node is visible to user without private access.');
    $this->drupalGet('user/' . $access_user->id() . '/activity');
    $this->assertNoText($private_node->getTitle(), 'Private node is not visible to user without private access.');
    $this->assertText($public_node->getTitle(), 'Public node is visible to user without private access.');
  }
}
