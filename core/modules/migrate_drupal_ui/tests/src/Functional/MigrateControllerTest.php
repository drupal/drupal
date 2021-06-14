<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\views\Entity\View;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the MigrateController class.
 *
 * @group migrate_drupal_ui
 */
class MigrateControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'dblog',
    'migrate_drupal_ui',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Log in as user 1. Migrations in the UI can only be performed as user 1.
    $this->drupalLogin($this->rootUser);

    // Create a migrate message for testing purposes.
    \Drupal::logger('migrate_drupal_ui')->notice('A test message');

  }

  /**
   * Tests the upgrade report with the view enabled, disabled and uninstalled.
   */
  public function testUpgradeReport() {
    $session = $this->assertSession();

    $this->assertTrue(View::load('watchdog')->status(), 'Watchdog view is enabled');
    // Tests redirection to report page when the watchdog view is enabled.
    $this->drupalGet('admin/reports/upgrade');
    $session->optionExists('type[]', 'migrate_drupal_ui')->isSelected();
    $session->pageTextContainsOnce('A test message');

    // Disable the watchdog view.
    $this->drupalGet('admin/structure/views');
    $this->assertTrue($this->clickViewsOperationsLink('Disable', '/watchdog/'));
    $session->statusCodeEquals(200);

    // Tests redirection to report page when the watchdog view is disabled.
    $this->drupalGet('admin/reports/upgrade');
    $session->optionExists('type[]', 'migrate_drupal_ui')->isSelected();
    $session->pageTextContainsOnce('A test message');

    \Drupal::service('module_installer')->uninstall(['views_ui', 'views']);
    // Tests redirection to report page when views is uninstalled.
    $this->drupalGet('admin/reports/upgrade');
    $session->optionExists('type[]', 'migrate_drupal_ui')->isSelected();
    $session->pageTextContainsOnce('A test message');
  }

  /**
   * Clicks a view link to perform an operation.
   *
   * @param string $label
   *   Text between the anchor tags of the link.
   * @param string $href_part
   *   A unique string that is expected to occur within the href of the link.
   *
   * @return bool
   *   TRUE when link found and clicked, otherwise FALSE.
   */
  public function clickViewsOperationsLink($label, $href_part) {
    $links = $this->xpath('//a[normalize-space(text())=:label]', [':label' => (string) $label]);
    foreach ($links as $link_index => $link) {
      $position = strpos($link->getAttribute('href'), $href_part);
      if ($position !== FALSE) {
        $index = $link_index;
        $this->clickLink((string) $label, $index);
        return TRUE;
      }
    }
    return FALSE;
  }

}
