<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the definition of navigation safe blocks.
 */
#[Group('navigation')]
#[RunTestsInSeparateProcesses]
class NavigationSafeBlockDefinitionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'navigation',
    'navigation_test',
    'block',
    'shortcut',
    'shortcut_test',
  ];

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
   *
   * @see \Drupal\shortcut_test\Hook\ShortcutTestHooks::blockAlter()
   */
  public function testNavigationSafeBlockDefinition(): void {
    // Confirm that default blocks are available.
    $layout_url = '/layout_builder/choose/block/navigation/navigation.block_layout/0/content';
    $this->drupalGet($layout_url);

    $this->assertSession()->linkExists('Navigation Shortcuts');

    // Apply changes, clear cache and confirm that changes are applied.
    \Drupal::state()->set('navigation_safe_alter', TRUE);
    \Drupal::cache('discovery')->delete('block_plugins');

    $this->drupalGet($this->getUrl());
    $this->assertSession()->linkNotExists('Navigation Shortcuts');
  }

  /**
   * Tests logic to exclude blocks in Block Layout UI.
   */
  public function testNavigationBlocksHiddenInBlockLayout(): void {
    $block_url = '/admin/structure/block';
    $this->drupalGet($block_url);
    $this->clickLink('Place block');
    $this->assertSession()->linkByHrefNotExists('/admin/structure/block/add/navigation_shortcuts/stark');
  }

}
