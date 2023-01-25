<?php

namespace Drupal\Tests\system\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JavaScript functionality of the module uninstall filter.
 *
 * @group system
 */
class ModuleUninstallFilterTest extends WebDriverTestBase {

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
   * Tests that correct module count is returned when list filtered.
   */
  public function testModuleUninstallFilter() {

    // Find the module filter field.
    $this->drupalGet('admin/modules/uninstall');
    $assertSession = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    $filter = $page->findField('edit-text');

    // Get all module rows, for assertions later.
    $module_rows = $page->findAll('css', '.module-name');

    // Test module filter reduces the number of visible rows.
    $filter->setValue('dynamic');
    $session->wait(1000, 'jQuery("#edit-uninstall-page-cache:visible").length == 0');
    $visible_rows = $this->filterVisibleElements($module_rows);
    $this->assertNotEquals(count($module_rows), count($visible_rows));

    // Test Drupal.announce() message when multiple matches are expected.
    $filter->setValue('cache');
    $session->wait(1000, 'jQuery("#drupal-live-announce").html().indexOf("modules are available") > -1');
    $visible_rows = $this->filterVisibleElements($module_rows);
    $expected_message = count($visible_rows) . ' modules are available in the modified list.';
    $assertSession->elementTextContains('css', '#drupal-live-announce', $expected_message);

    // Test Drupal.announce() message when only one match is expected.
    // Using a very specific module name, we expect only one row.
    $filter->setValue('dynamic page cache');
    $session->wait(1000, 'jQuery("#drupal-live-announce").html().indexOf("module is available") > -1');
    $visible_rows = $this->filterVisibleElements($module_rows);
    $this->assertEquals(1, count($visible_rows));
    $expected_message = '1 module is available in the modified list.';
    $assertSession->elementTextContains('css', '#drupal-live-announce', $expected_message);

    // Test Drupal.announce() message when no matches are expected.
    $filter->setValue('Pan-Galactic Gargle Blaster');
    $session->wait(1000, 'jQuery("#drupal-live-announce").html().indexOf("0 modules are available") > -1');
    $visible_rows = $this->filterVisibleElements($module_rows);
    $this->assertEquals(0, count($visible_rows));
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
   */
  protected function filterVisibleElements($elements) {
    $elements = array_filter($elements, function ($element) {
      return $element->isVisible();
    });
    return $elements;
  }

}
