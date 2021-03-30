<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\EntityViewTrait;

/**
 * Tests changing view modes for nodes.
 *
 * @group node
 */
class NodeEntityViewModeAlterTest extends NodeTestBase {

  use EntityViewTrait;

  /**
   * Enable dummy module that implements hook_ENTITY_TYPE_view() for nodes.
   */
  protected static $modules = ['node_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Create a "Basic page" node and verify its consistency in the database.
   */
  public function testNodeViewModeChange() {
    $web_user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
    ]);
    $this->drupalLogin($web_user);

    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = t('Data that should appear only in the body for the node.');
    $edit['body[0][summary]'] = t('Extra data that should appear only in the teaser for the node.');
    $this->drupalPostForm('node/add/page', $edit, 'Save');

    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    // Set the flag to alter the view mode and view the node.
    \Drupal::state()->set('node_test_change_view_mode', 'teaser');
    Cache::invalidateTags(['rendered']);
    $this->drupalGet('node/' . $node->id());

    // Check that teaser mode is viewed.
    $this->assertText('Extra data that should appear only in the teaser for the node.');
    // Make sure body text is not present.
    $this->assertNoText('Data that should appear only in the body for the node.');

    // Test that the correct build mode has been set.
    $build = $this->buildEntityView($node);
    $this->assertEqual('teaser', $build['#view_mode'], 'The view mode has correctly been set to teaser.');
  }

}
