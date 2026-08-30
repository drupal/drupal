<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the JavaScript functionality of the admin compact mode.
 */
#[Group('system')]
#[RunTestsInSeparateProcesses]
class AdminCompactModeTest extends WebDriverTestBase {

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
      'administer permissions',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that filter results announcement has correct pluralization.
   */
  public function testAdminCompactMode(): void {
    $descriptionSelector = '[data-drupal-selector="edit-permissions-administer-site-configuration"] .description';
    $descriptionContent = 'Warning: Give to trusted roles only; this permission has security implications.';

    $this->drupalGet('admin/people/permissions');
    $assertSession = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    // Document attribute is absent.
    $assertSession->elementAttributeNotExists('css', ':root', 'data-admin-compact-mode');

    // Description is present and visible.
    $assertSession->elementTextContains('css', $descriptionSelector, $descriptionContent);
    $displayValue = $this->getSession()->evaluateScript(
      "window.getComputedStyle(document.querySelector('{$descriptionSelector}')).display;"
    );
    $this->assertEquals('block', $displayValue);

    $page->findLink('Hide descriptions')->click();

    // Document attribute is present.
    $assertSession->elementAttributeExists('css', ':root', 'data-admin-compact-mode');

    // Description is present and hidden.
    $descriptionElement = $assertSession->elementExists('css', $descriptionSelector);
    $this->assertEquals("", $descriptionElement->getText());
    $displayValue = $this->getSession()->evaluateScript(
      "window.getComputedStyle(document.querySelector('{$descriptionSelector}')).display;"
    );
    $this->assertEquals('none', $displayValue);

    // Navigate away and back.
    $this->drupalGet('<front>');
    $this->drupalGet('admin/people/permissions');

    // Document attribute is still present.
    $assertSession->elementAttributeExists('css', ':root', 'data-admin-compact-mode');

    // Description is still hidden.
    $descriptionElement = $assertSession->elementExists('css', $descriptionSelector);
    $this->assertEquals("", $descriptionElement->getText());
    $displayValue = $this->getSession()->evaluateScript(
      "window.getComputedStyle(document.querySelector('{$descriptionSelector}')).display;"
    );
    $this->assertEquals('none', $displayValue);

    $page->findLink('Show descriptions')->click();

    // Document attribute is absent.
    $assertSession->elementAttributeNotExists('css', ':root', 'data-admin-compact-mode');

    // Description is visible.
    $assertSession->elementTextContains('css', $descriptionSelector, $descriptionContent);
    $displayValue = $this->getSession()->evaluateScript(
      "window.getComputedStyle(document.querySelector('{$descriptionSelector}')).display;"
    );
    $this->assertEquals('block', $displayValue);
  }

}
