<?php

declare(strict_types=1);

namespace Drupal\Tests\search_help\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verifies that search_help extends the main help page.
 */
#[Group('help')]
#[RunTestsInSeparateProcesses]
class SearchHelpHelpPageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['help', 'search', 'search_help'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the help search section is added to and removed from the page.
   */
  public function testHelpPages(): void {
    $this->drupalLogin($this->createUser(['access help pages']));

    // Confirm that search_help does not expose its own help page.
    $this->drupalGet('admin/help/search_help');
    $this->assertSession()->statusCodeEquals(404);

    $session = $this->assertSession();

    $this->drupalGet('admin/help/help');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Configuring help search');
    $session->pageTextContains('Translating help topics');

    // Uninstalling search_help should remove the section but keep the
    // original help module output.
    \Drupal::service('module_installer')->uninstall(['search_help']);
    $this->resetAll();

    $this->drupalGet('admin/help/help');
    $session->statusCodeEquals(200);
    $session->pageTextNotContains('Configuring help search');
    $session->pageTextContains('Translating help topics');
  }

}
