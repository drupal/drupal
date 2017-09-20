<?php

namespace Drupal\Tests\contextual\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the UI for correct contextual links.
 *
 * @group contextual
 */
class ContextualLinksTest extends JavascriptTestBase {

  use ContextualLinkClickTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'contextual'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->createUser(['access contextual links']));
    $this->placeBlock('system_branding_block', ['id' => 'branding']);
  }

  /**
   * Tests the visibility of contextual links.
   */
  public function testContextualLinksVisibility() {
    $this->drupalGet('user');
    $contextualLinks = $this->assertSession()->waitForElement('css', '.contextual button');
    $this->assertEmpty($contextualLinks);

    // Ensure visibility remains correct after cached paged load.
    $this->drupalGet('user');
    $contextualLinks = $this->assertSession()->waitForElement('css', '.contextual button');
    $this->assertEmpty($contextualLinks);

    // Grant permissions to use contextual links on blocks.
    $this->grantPermissions(Role::load(Role::AUTHENTICATED_ID), [
      'access contextual links',
      'administer blocks',
    ]);

    $this->drupalGet('user');
    $contextualLinks = $this->assertSession()->waitForElement('css', '.contextual button');
    $this->assertNotEmpty($contextualLinks);

    // Ensure visibility remains correct after cached paged load.
    $this->drupalGet('user');
    $contextualLinks = $this->assertSession()->waitForElement('css', '.contextual button');
    $this->assertNotEmpty($contextualLinks);
  }

  /**
   * Test clicking contextual links.
   */
  public function testContextualLinksClick() {
    $this->container->get('module_installer')->install(['contextual_test']);
    // Test clicking contextual link without toolbar.
    $this->drupalGet('user');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->clickContextualLink('#block-branding', 'Test Link');
    $this->assertSession()->pageTextContains('Everything is contextual!');

    // Test clicking contextual link with toolbar.
    $this->container->get('module_installer')->install(['toolbar']);
    $this->grantPermissions(Role::load(Role::AUTHENTICATED_ID), ['access toolbar']);
    $this->drupalGet('user');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Click "Edit" in toolbar to show contextual links.
    $this->getSession()->getPage()->find('css', '.contextual-toolbar-tab button')->press();
    $this->clickContextualLink('#block-branding', 'Test Link', FALSE);
    $this->assertSession()->pageTextContains('Everything is contextual!');
  }

}
