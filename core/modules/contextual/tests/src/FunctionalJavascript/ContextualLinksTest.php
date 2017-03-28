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

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'contextual'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->placeBlock('system_branding_block', ['id' => 'branding']);
  }

  /**
   * Tests the visibility of contextual links.
   */
  public function testContextualLinksVisibility() {
    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links'
    ]));

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

}
