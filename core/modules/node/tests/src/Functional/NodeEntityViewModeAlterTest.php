<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\EntityViewTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests changing view modes for nodes.
 */
#[Group('node')]
#[RunTestsInSeparateProcesses]
class NodeEntityViewModeAlterTest extends NodeTestBase {

  use EntityViewTrait;

  /**
   * Enable dummy module that implements hook_ENTITY_TYPE_view() for nodes.
   *
   * @var string[]
   */
  protected static $modules = ['node_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Create a "Basic page" node and verify its consistency in the database.
   */
  public function testNodeViewModeChange(): void {
    $web_user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
    ]);
    $this->drupalLogin($web_user);

    $display = \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'page', 'teaser');
    $display_options = $display->getComponent('body');
    $display_options['settings']['trim_length'] = 25;
    $display->setComponent('body', $display_options)
      ->save();

    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = 'Data that should appear only in the body for the node.';
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, 'Save');

    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    // Set the flag to alter the view mode and view the node.
    \Drupal::state()->set('node_test_change_view_mode', 'teaser');
    Cache::invalidateTags(['rendered']);
    $this->drupalGet('node/' . $node->id());

    // Check that teaser mode is viewed. Should be trimmed to 25.
    $this->assertSession()->pageTextContains('Data that should appear');
    $this->assertSession()->pageTextNotContains(' only in the body for the node.');

    // Test that the correct build mode has been set.
    $build = $this->buildEntityView($node);
    $this->assertEquals('teaser', $build['#view_mode'], 'The view mode has correctly been set to teaser.');
  }

}
