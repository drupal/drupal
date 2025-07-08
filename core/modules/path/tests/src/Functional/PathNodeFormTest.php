<?php

declare(strict_types=1);

namespace Drupal\Tests\path\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Tests the Path Node form UI.
 *
 * @group path
 */
class PathNodeFormTest extends PathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'path', 'path_test_misc'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create test user and log in.
    $web_user = $this->drupalCreateUser([
      'create page content',
      'create url aliases',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the node form ui.
   */
  public function testNodeForm(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('node/add/page');

    // Make sure we have a vertical tab fieldset and 'Path' fields.
    $assert_session->elementContains('css', '.js-form-type-vertical-tabs #edit-path-0 summary', 'URL alias');
    $assert_session->fieldExists('path[0][alias]');

    // Disable the 'Path' field for this content type.
    \Drupal::service('entity_display.repository')->getFormDisplay('node', 'page', 'default')
      ->removeComponent('path')
      ->save();

    $this->drupalGet('node/add/page');

    // See if the whole fieldset is gone now.
    $assert_session->elementNotExists('css', '.js-form-type-vertical-tabs #edit-path-0');
    $assert_session->fieldNotExists('path[0][alias]');
  }

  /**
   * Tests that duplicate path aliases don't get created.
   */
  public function testAliasDuplicationPrevention(): void {
    $this->drupalGet('node/add/page');
    $edit['title[0][value]'] = 'path duplication test';
    $edit['path[0][alias]'] = '/my-alias';
    $this->submitForm($edit, 'Save');

    // Test that PathItem::postSave detects if a path alias exists
    // before creating one.
    $aliases = \Drupal::entityTypeManager()
      ->getStorage('path_alias')
      ->loadMultiple();
    static::assertCount(1, $aliases);
    $node = Node::load(1);
    static::assertInstanceOf(NodeInterface::class, $node);

    // This updated title gets set in PathTestMiscHooks::nodePresave. This
    // is a way of ensuring that bit of test code runs.
    static::assertEquals('path duplication test ran', $node->getTitle());
  }

}
