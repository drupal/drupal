<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the default block provider logic.
 *
 * @group navigation
 */
class NavigationDefaultBlockDefinitionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['test_page_test', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the default block flow enabling Navigation module first.
   */
  public function testNavigationDefaultAfterNavigation(): void {
    $test_page_url = Url::fromRoute('test_page_test.test_page');
    $module_installer = \Drupal::service('module_installer');

    // After installing Navigation, the bar is present, but not the block.
    $module_installer->install(['navigation']);
    $this->drupalLogin($this->drupalCreateUser(['access navigation']));
    $this->drupalGet($test_page_url);
    $this->assertSession()->elementExists('css', '.admin-toolbar');
    $this->assertSession()->elementNotExists('css', '.toolbar-button--icon--test-block');

    // After installing Navigation Test Block, both elements are present.
    $module_installer->install(['navigation_test_block']);
    $this->drupalGet($test_page_url);
    $this->assertSession()->elementExists('css', '.admin-toolbar');
    $this->assertSession()->elementContains('css', '.toolbar-button--icon--test-block', 'Test Navigation Block');
  }

  /**
   * Tests the default block flow enabling the block provider module first.
   */
  public function testNavigationDefaultBeforeNavigation(): void {
    $test_page_url = Url::fromRoute('test_page_test.test_page');
    $module_installer = \Drupal::service('module_installer');

    // After installing Navigation Test Block, none of the elements are present.
    $module_installer->install(['navigation_test_block']);
    $this->drupalGet($test_page_url);
    $this->assertSession()->elementNotExists('css', '.admin-toolbar');
    $this->assertSession()->elementNotExists('css', '.toolbar-button--icon--test-block');

    // After installing Navigation, both elements are present.
    $module_installer->install(['navigation']);
    $this->drupalLogin($this->drupalCreateUser(['access navigation']));
    $this->drupalGet($test_page_url);
    $this->assertSession()->elementExists('css', '.admin-toolbar');
    $this->assertSession()->elementContains('css', '.toolbar-button--icon--test-block', 'Test Navigation Block');
  }

}
