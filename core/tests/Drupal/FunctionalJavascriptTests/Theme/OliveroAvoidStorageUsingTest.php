<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Theme;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests usage of localStorage.
 *
 * @group olivero
 */
final class OliveroAvoidStorageUsingTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'olivero';

  /**
   * Tests use of localStorage.
   */
  public function testStorageUsing(): void {
    $this->drupalGet('<front>');
    // Check if initial no storage item is written.
    $this->assertJsCondition("localStorage.getItem('Drupal.olivero.stickyHeaderState') === null", 10000, 'Written not strictly necessary Drupal.olivero.stickyHeaderState to localStorage without consent.');

    // Resize and scroll to show stickyHeaderToggleButton.
    $session = $this->getSession();
    $session->resizeWindow(1280, 1024);
    $session->executeScript('window.scrollTo(0, 500);');

    // Click stickyHeaderToggleButton.
    $this->getSession()->getPage()->find('css', '.sticky-header-toggle')->click();

    // Test if localStorage is set now.
    $this->assertJsCondition("localStorage.getItem('Drupal.olivero.stickyHeaderState') !== null");

    // Click stickyHeaderToggleButton again.
    $this->getSession()->getPage()->find('css', '.sticky-header-toggle')->click();

    // Storage item should be removed now.
    $this->assertJsCondition("localStorage.getItem('Drupal.olivero.stickyHeaderState') === null", 10000, 'Storage item Drupal.olivero.stickyHeaderState should be removed.');

  }

}
