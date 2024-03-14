<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests page title block.
 *
 * @group Block
 */
class PageTitleBlockTest extends BrowserTestBase {
  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block', 'update', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Add the page title block to the page.
    $this->drupalPlaceBlock('page_title_block', ['id' => 'stark_page_title']);

    // Create node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    // Create administrative user.
    $admin_user = $this->drupalCreateUser([
      'administer blocks',
      'administer themes',
      'administer software updates',
      'administer nodes',
      'administer modules',
      'create article content',
      'edit any article content',
      'delete any article content',
      'delete any article content',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Check if the contextualized title is displayed.
   */
  public function testContextualizeTitle(): void {
    $edit['admin_theme'] = $this->defaultTheme;
    $this->drupalGet('admin/appearance');
    $this->submitForm($edit, 'Save configuration');

    // Make sure the title shown is non-contextualized.
    $this->drupalGet('admin/modules/update');
    $this->assertSession()->elementTextEquals('css', 'h1', 'Update');

    // Checking if the title block is configured for showing
    // contextualized title and if it's not then configure it.
    $this->drupalGet('admin/structure/block/manage/' . $this->defaultTheme . '_page_title');
    // In stark theme it's not configured to show contextualized title
    // therefore the value of the configuration will be 1 otherwise 0.
    // @see \Drupal\Core\Block\Plugin\Block\PageTitleBlock::blockForm()
    $this->assertSession()->fieldValueEquals('settings[base_route_title]', 0);
    $this->submitForm(['settings[base_route_title]' => 1], 'Save block');

    // Make sure the title shown is contextualized.
    $this->drupalGet('admin/modules/update');
    $this->assertSession()->elementTextEquals('css', 'h1', 'Extend: Update');
    $title = $this->assertSession()->elementExists('xpath', '//h1');
    $this->assertSession()->elementExists('xpath', '/span[@class="visually-hidden"]', $title);
    $this->assertSession()->elementTextEquals('xpath', '//h1/span', ': Update');

  }

  /**
   * Tests the contextualized title if base route is not accessible.
   */
  public function testContextualizeTitleWhenBaseRouteIsNotAccessible(): void {
    // Create administrative user with access to 'admin/modules/update' but no
    // access to base route 'admin/modules'.
    $admin_user = $this->drupalCreateUser([
      'administer blocks',
      'administer software updates',
    ]);
    $this->drupalLogin($admin_user);

    $non_contextualized_title = 'Update';
    // Make sure the title shown is non-contextualized.
    $this->drupalGet('admin/modules/update');
    $this->assertSession()->elementTextEquals('css', 'h1', $non_contextualized_title);

    // Configure title block to show contextualized title.
    $this->drupalGet('admin/structure/block/manage/' . $this->defaultTheme . '_page_title');
    $this->assertSession()->fieldValueEquals('settings[base_route_title]', 0);
    $this->submitForm(['settings[base_route_title]' => 1], 'Save block');

    // Make sure the title shown is non-contextualized because the base route is
    // not accessible.
    // @see \Drupal\Core\Block\Plugin\Block\PageTitleBlock::getTitleBasedOnBaseRoute()
    $this->drupalGet('admin/modules/update');
    $this->assertSession()->elementTextEquals('css', 'h1', $non_contextualized_title);
  }

  /**
   * Data provider for testContextualizeTitleOnNodeOperationPages().
   *
   * @return array[][]
   *   The test cases.
   */
  public function providerTestContextualizeTitleOnNodeOperationPages() : array {
    return [
      'node with random title' => [$this->randomMachineName(8)],
      'node with title set to 0' => ['0'],
    ];
  }

  /**
   * Tests if contextualized title displayed on all node operation pages.
   *
   * @dataProvider providerTestContextualizeTitleOnNodeOperationPages
   */
  public function testContextualizeTitleOnNodeOperationPages($node_title): void {
    $settings = [
      'type' => 'article',
      'title' => $node_title,
    ];
    $node = $this->drupalCreateNode($settings);

    // Make sure the non-contextualized title is shown on all node operation
    // pages.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->elementTextEquals('xpath', '//h1', $node_title);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->elementTextEquals('xpath', '//h1', "Edit Article $node_title");

    $this->drupalGet('node/' . $node->id() . '/delete');
    $this->assertSession()->elementTextEquals('xpath', '//h1', "Are you sure you want to delete the content item $node_title?");

    $this->drupalGet('node/' . $node->id() . '/revisions');
    $this->assertSession()->elementTextEquals('xpath', '//h1', "Revisions for $node_title");

    // Configure title block to show contextualized title.
    $this->drupalGet('admin/structure/block/manage/' . $this->defaultTheme . '_page_title');
    $this->submitForm(['settings[base_route_title]' => 1], 'Save block');

    // Make sure the contextualized title is shown on all node operation pages. On all pages the title is same as before
    // because we don't change the title if it's already overridden.
    // @see \Drupal\Core\Block\Plugin\Block\PageTitleBlock::getTitleBasedOnBaseRoute()
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->elementTextEquals('xpath', '//h1', $node_title);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->elementTextEquals('xpath', '//h1', "Edit Article $node_title");

    $this->drupalGet('node/' . $node->id() . '/delete');
    $this->assertSession()->elementTextEquals('xpath', '//h1', "Are you sure you want to delete the content item $node_title?");

    $this->drupalGet('node/' . $node->id() . '/revisions');
    $this->assertSession()->elementTextEquals('xpath', '//h1', "Revisions for $node_title");
  }

}
