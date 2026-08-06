<?php

declare(strict_types=1);

namespace Drupal\Tests\toolbar\FunctionalJavascript\contextual;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the toolbar with contextual links.
 */
#[Group('toolbar')]
#[RunTestsInSeparateProcesses]
class ContextualLinksTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'contextual',
    'contextual_test',
    'toolbar',
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

    $this->drupalLogin($this->createUser([
      'access contextual links',
      'access toolbar',
    ]));
    $this->placeBlock('system_branding_block', [
      'id' => 'branding',
    ]);
  }

  /**
   * Tests clicking contextual link with toolbar.
   */
  public function testContextualLinksClick(): void {
    $this->drupalGet('user');
    $this->assertSession()->assertExpectedAjaxRequest(1);

    // Click "Edit" in toolbar to show contextual links.
    $this->getSession()->getPage()->find('css', '.contextual-toolbar-tab button')->press();
    $this->clickContextualLink('#block-branding', 'Test Link', FALSE);
    $this->assertSession()->pageTextContains('Everything is contextual!');
  }

}
