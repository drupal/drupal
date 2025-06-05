<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for \Drupal\navigation\WorkspacesLazyBuilder.
 *
 * @group navigation
 */
class NavigationWorkspacesUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['navigation', 'workspaces_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'access navigation',
      'administer workspaces',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the Workspaces button in the navigation bar.
   */
  public function testWorkspacesNavigationButton(): void {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementAttributeContains('css', 'a.toolbar-button--icon--workspaces svg', 'width', '20');
    $this->assertSession()->elementAttributeContains('css', 'a.toolbar-button--icon--workspaces svg', 'class', 'toolbar-button__icon');
  }

}
