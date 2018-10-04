<?php

namespace Drupal\Tests\system\Functional\Menu;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests breadcrumbs functionality.
 *
 * @group Menu
 */
class BreadcrumbFrontCacheContextsTest extends BrowserTestBase {

  use AssertBreadcrumbTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'node',
    'path',
    'user',
  ];

  /**
   * A test node with path alias.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodeWithAlias;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('system_breadcrumb_block');

    $user = $this->drupalCreateUser();

    $this->drupalCreateContentType([
      'type' => 'page',
    ]);

    // Create a node for front page.
    $node_front = $this->drupalCreateNode([
      'uid' => $user->id(),
    ]);

    // Create a node with a random alias.
    $this->nodeWithAlias = $this->drupalCreateNode([
      'uid' => $user->id(),
      'type' => 'page',
      'path' => '/' . $this->randomMachineName(),
    ]);

    // Configure 'node' as front page.
    $this->config('system.site')
      ->set('page.front', '/node/' . $node_front->id())
      ->save();

    \Drupal::cache('render')->deleteAll();
  }

  /**
   * Validate that breadcrumb markup get the right cache contexts.
   *
   * Checking that the breadcrumb will be printed on node canonical routes even
   * if it was rendered for the <front> page first.
   */
  public function testBreadcrumbsFrontPageCache() {
    // Hit front page first as anonymous user with 'cold' render cache.
    $this->drupalGet('<front>');
    $web_assert = $this->assertSession();
    // Verify that no breadcrumb block presents.
    $web_assert->elementNotExists('css', '.block-system-breadcrumb-block');

    // Verify that breadcrumb appears correctly for the test content
    // (which is not set as front page).
    $this->drupalGet($this->nodeWithAlias->path->alias);
    $breadcrumbs = $this->assertSession()->elementExists('css', '.block-system-breadcrumb-block');
    $crumbs = $breadcrumbs->findAll('css', 'ol li');
    $this->assertTrue(count($crumbs) === 1);
    $this->assertTrue($crumbs[0]->getText() === 'Home');
  }

}
