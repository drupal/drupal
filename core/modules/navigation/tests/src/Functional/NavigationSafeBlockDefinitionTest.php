<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the definition of navigation safe blocks.
 *
 * @group navigation
 */
class NavigationSafeBlockDefinitionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['navigation', 'navigation_test', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with permission to administer navigation blocks and access navigation.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an admin user, log in and enable test navigation blocks.
    $this->adminUser = $this->drupalCreateUser([
      'configure navigation layout',
      'access navigation',
      'administer blocks',
    ]);

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests logic to include blocks in Navigation Layout UI.
   */
  public function testNavigationSafeBlockDefinition(): void {
    // Confirm that default blocks are available.
    $layout_url = '/admin/config/user-interface/navigation-block';
    $this->drupalGet($layout_url);
    $this->clickLink('Add block');

    $this->assertSession()->linkExists('Administration');
    $this->assertSession()->linkExists('Content');
    $this->assertSession()->linkExists('Footer');
    $this->assertSession()->linkExists('Navigation Shortcuts');
    $this->assertSession()->linkExists('User');
    $this->assertSession()->linkNotExists('Link');

    // Apply changes, clear cache and confirm that changes are applied.
    \Drupal::state()->set('navigation_safe_alter', TRUE);
    \Drupal::cache('discovery')->delete('block_plugins');

    $this->drupalGet($this->getUrl());
    $this->assertSession()->linkExists('Administration');
    $this->assertSession()->linkExists('Content');
    $this->assertSession()->linkExists('Footer');
    $this->assertSession()->linkExists('Link');
    $this->assertSession()->linkNotExists('Navigation Shortcuts');
  }

  /**
   * Tests logic to exclude blocks in Block Layout UI.
   */
  public function testNavigationBlocksHiddenInBlockLayout(): void {
    $block_url = '/admin/structure/block';
    $this->drupalGet($block_url);
    $this->clickLink('Place block');
    $this->assertSession()->linkByHrefNotExists('/admin/structure/block/add/navigation_menu%3Aadmin/stark');
    $this->assertSession()->linkByHrefNotExists('/admin/structure/block/add/navigation_menu%3Acontent/stark');
    $this->assertSession()->linkByHrefNotExists('/admin/structure/block/add/navigation_shortcuts/stark');
    $this->assertSession()->linkByHrefNotExists('/admin/structure/block/add/navigation_user/stark');
    $this->assertSession()->linkByHrefNotExists('/admin/structure/block/add/navigation_link/stark');
  }

}
