<?php

namespace Drupal\Tests\contextual\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the UI for correct contextual links.
 *
 * @group contextual
 */
class DuplicateContextualLinksTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'contextual',
    'node',
    'views',
    'views_ui',
    'contextual_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the contextual links with same id.
   */
  public function testSameContextualLinks() {
    $this->drupalPlaceBlock('views_block:contextual_recent-block_1', ['id' => 'first']);
    $this->drupalPlaceBlock('views_block:contextual_recent-block_1', ['id' => 'second']);
    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateNode();
    $this->drupalLogin($this->drupalCreateUser([
      'access content',
      'access contextual links',
      'administer nodes',
      'administer blocks',
      'administer views',
      'edit any page content',
    ]));
    // Ensure same contextual links work correct with fresh and cached page.
    foreach (['fresh', 'cached'] as $state) {
      $this->drupalGet('user');
      $contextual_id = '[data-contextual-id^="node:node=1"]';
      $this->assertJsCondition("(typeof jQuery !== 'undefined' && jQuery('[data-contextual-id]:empty').length === 0)");
      $this->getSession()->executeScript("jQuery('#block-first $contextual_id .trigger').trigger('click');");
      $contextual_links = $this->assertSession()->waitForElementVisible('css', "#block-first $contextual_id .contextual-links");
      $this->assertTrue($contextual_links->isVisible(), "Contextual links are visible with $state page.");
    }
  }

}
