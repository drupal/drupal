<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JavaScript functionality of the module filter.
 *
 * @group system
 */
class ModuleFilterTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'system'];

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
      'administer modules',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that filter results announcement has correct pluralization.
   */
  public function testModuleFilter() {

    // Find the module filter field.
    $this->drupalGet('admin/modules');
    $assertSession = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    $filter = $page->findField('edit-text');

    // Get all module rows, for assertions later.
    $module_rows = $page->findAll('css', '.package-listing tbody tr td.module');

    // Test module filter reduces the number of visible rows.
    $filter->setValue('test');
    $session->wait(1000, 'jQuery("#module-node:visible").length == 0');
    $visible_rows = $this->filterVisibleElements($module_rows);
    // Test Drupal.announce() message when multiple matches are expected.
    $expected_message = count($visible_rows) . ' modules are available in the modified list.';
    $assertSession->elementTextContains('css', '#drupal-live-announce', $expected_message);
    self::assertGreaterThan(count($visible_rows), count($module_rows));
    self::assertGreaterThan(1, count($visible_rows));

    // Test Drupal.announce() message when one match is expected.
    // Using a very specific module name, we expect only one row.
    $filter->setValue('System dependency test');
    $session->wait(1000, 'jQuery("#module-node:visible").length == 0');
    $visible_rows = $this->filterVisibleElements($module_rows);
    self::assertEquals(1, count($visible_rows));
    $expected_message = '1 module is available in the modified list.';
    $assertSession->elementTextContains('css', '#drupal-live-announce', $expected_message);

    // Test filtering by a machine name, when the module description doesn't end
    // with a period or other separator. This condition is common for test
    // modules.
    $filter->setValue('comment_base_field_test');
    $session->wait(1000, 'jQuery("#module-node:visible").length == 0');
    $visible_rows = $this->filterVisibleElements($module_rows);
    self::assertEquals(1, count($visible_rows));

    // Test Drupal.announce() message when no matches are expected.
    $filter->setValue('Pan-Galactic Gargle Blaster');
    $session->wait(1000, 'jQuery("#module-node:visible").length == 0');
    $visible_rows = $this->filterVisibleElements($module_rows);
    self::assertEquals(0, count($visible_rows));

    $expected_message = '0 modules are available in the modified list.';
    $assertSession->elementTextContains('css', '#drupal-live-announce', $expected_message);
  }

  /**
   * Removes any non-visible elements from the passed array.
   *
   * @param \Behat\Mink\Element\NodeElement[] $elements
   *   An array of node elements.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   An array of node elements.
   */
  protected function filterVisibleElements(array $elements): array {
    $elements = array_filter($elements, function ($element) {
      return $element->isVisible();
    });
    return $elements;
  }

}
