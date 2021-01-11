<?php

namespace Drupal\Tests\tracker\Functional;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for private node access on /tracker.
 *
 * @group tracker
 */
class TrackerNodeAccessTest extends BrowserTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'comment',
    'tracker',
    'node_access_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    node_access_rebuild();
    $this->drupalCreateContentType(['type' => 'page']);
    node_access_test_add_field(NodeType::load('page'));
    $this->addDefaultCommentField('node', 'page', 'comment', CommentItemInterface::OPEN);
    \Drupal::state()->set('node_access_test.private', TRUE);
  }

  /**
   * Ensure private node on /tracker is only visible to users with permission.
   */
  public function testTrackerNodeAccess() {
    // Create user with node test view permission.
    $access_user = $this->drupalCreateUser([
      'node test view',
      'access user profiles',
    ]);

    // Create user without node test view permission.
    $no_access_user = $this->drupalCreateUser(['access user profiles']);

    $this->drupalLogin($access_user);

    // Create some nodes.
    $private_node = $this->drupalCreateNode([
      'title' => t('Private node test'),
      'private' => TRUE,
    ]);
    $public_node = $this->drupalCreateNode([
      'title' => t('Public node test'),
      'private' => FALSE,
    ]);

    // User with access should see both nodes created.
    $this->drupalGet('activity');
    $this->assertText($private_node->getTitle());
    $this->assertText($public_node->getTitle());
    $this->drupalGet('user/' . $access_user->id() . '/activity');
    $this->assertText($private_node->getTitle());
    $this->assertText($public_node->getTitle());

    // User without access should not see private node.
    $this->drupalLogin($no_access_user);
    $this->drupalGet('activity');
    $this->assertNoText($private_node->getTitle());
    $this->assertText($public_node->getTitle());
    $this->drupalGet('user/' . $access_user->id() . '/activity');
    $this->assertNoText($private_node->getTitle());
    $this->assertText($public_node->getTitle());
  }

}
