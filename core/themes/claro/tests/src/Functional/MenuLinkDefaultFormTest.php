<?php

declare(strict_types=1);

namespace Drupal\Tests\claro\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the MenuLinkDefaultForm customizations.
 *
 * @group claro
 */
class MenuLinkDefaultFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser(['administer menu']));
  }

  /**
   * Tests the MenuLinkDefaultForm customizations.
   */
  public function testMenuLinkDefaultFormCustomizations(): void {
    $this->drupalGet('/admin/structure/menu/link/system.admin/edit');
    // Assert the Display Settings details element is placed in the sidebar.
    $this->assertSession()->elementTextEquals('css', '.layout-region--secondary #edit-advanced #edit-menu-link-display-settings summary', 'Display settings');
    // Assert tht form elements are in the expected location.
    $this->assertSession()->elementExists('css', '#edit-menu-link-display-settings .form-item--weight');
    $this->assertSession()->elementExists('css', '#edit-menu-link-display-settings .form-item--expanded');

    // Assert that menu link original values are present.
    $this->assertSession()->fieldValueEquals('weight', 9);
    $this->assertSession()->checkboxNotChecked('Show as expanded');

    $this->submitForm([
      'weight' => 10,
      'expanded' => TRUE,
    ], 'Save');

    // Assert that menu link values are updated.
    $this->drupalGet('/admin/structure/menu/link/system.admin/edit');
    $this->assertSession()->fieldValueEquals('weight', 10);
    $this->assertSession()->checkboxChecked('Show as expanded');
  }

}
