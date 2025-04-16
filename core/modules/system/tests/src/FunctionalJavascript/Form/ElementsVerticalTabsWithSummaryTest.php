<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript\Form;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests that titles and summaries in vertical-tabs form elements are set correctly.
 *
 * @group Form
 */
class ElementsVerticalTabsWithSummaryTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Check that vertical tabs title and summaries are set correctly.
   */
  public function testDynamicSummary(): void {
    $this->drupalGet('form_test/vertical-tabs-with-summary');
    $this->assertSession()->elementTextEquals('css', '.vertical-tabs__menu-item.first .vertical-tabs__menu-item-title', 'Tab 1');
    $this->assertSession()->elementTextEquals('css', '.vertical-tabs__menu-item.first .vertical-tabs__menu-item-summary', 'Summary 1');
    $this->assertSession()->elementTextEquals('css', '.vertical-tabs__menu-item.last .vertical-tabs__menu-item-title', 'Tab 2');
    $this->assertSession()->elementTextEquals('css', '.vertical-tabs__menu-item.last .vertical-tabs__menu-item-summary', 'Summary 2');
  }

}
