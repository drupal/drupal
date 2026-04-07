<?php

declare(strict_types=1);

namespace Drupal\Tests\contextual\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that duplicate contextual links are initialized independently.
 */
#[Group('contextual')]
#[RunTestsInSeparateProcesses]
class DuplicateContextualLinksTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'contextual_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that duplicate contextual links each get their own model view.
   */
  public function testSameContextualLinks(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
    ]));

    // Ensure same contextual links work correctly with fresh and cached page.
    $contextual_id = '[data-contextual-id^="contextual_test"]';
    foreach (['fresh', 'cached'] as $state) {
      $this->drupalGet('contextual-tests/duplicate-links');
      $this->assertJsCondition("(typeof jQuery !== 'undefined' && jQuery('[data-contextual-id]:empty').length === 0)");
      // Click each duplicate contextual trigger and verify only that region's
      // links open. If the cached path doesn't isolate duplicates, toggling
      // one opens both.
      foreach (['first', 'second'] as $id) {
        $other = $id === 'first' ? 'second' : 'first';
        $this->getSession()->executeScript("jQuery('#region-$id $contextual_id .trigger').trigger('click');");
        $this->assertNotNull($this->assertSession()->waitForElementVisible('css', "#region-$id $contextual_id .contextual-links"), "Contextual links in region-$id should open ($state page).");
        $this->assertFalse($this->getSession()->getPage()->find('css', "#region-$other $contextual_id .contextual-links")->isVisible(), "Contextual links in region-$other must NOT open when only region-$id was toggled ($state page).");
        // Close it again for the next iteration.
        $this->getSession()->executeScript("jQuery('#region-$id $contextual_id .trigger').trigger('click');");
        $this->assertSession()->waitForElementRemoved('css', "#region-$id $contextual_id .contextual.open");
      }
    }
  }

}
