<?php

namespace Drupal\Tests\contextual\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the UI for correct contextual links.
 *
 * @group contextual
 */
class ContextualLinksTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'contextual'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->createUser(['access contextual links']));
    $this->placeBlock('system_branding_block', [
      'id' => 'branding',
    ]);
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
   * Tests clicking contextual links.
   */
  public function testContextualLinksClick() {
    $this->container->get('module_installer')->install(['contextual_test']);
    // Test clicking contextual link without toolbar.
    $this->drupalGet('user');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->clickContextualLink('#block-branding', 'Test Link');
    $this->assertSession()->pageTextContains('Everything is contextual!');

    // Test click a contextual link that uses ajax.
    $this->drupalGet('user');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $current_page_string = 'NOT_RELOADED_IF_ON_PAGE';
    $this->getSession()->executeScript('document.body.appendChild(document.createTextNode("' . $current_page_string . '"));');
    $this->clickContextualLink('#block-branding', 'Test Link with Ajax');
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '#drupal-modal'));
    $this->assertSession()->elementContains('css', '#drupal-modal', 'Everything is contextual!');
    // Check to make sure that page was not reloaded.
    $this->assertSession()->pageTextContains($current_page_string);

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

  /**
   * Tests the contextual links destination.
   */
  public function testContextualLinksDestination() {
    $this->grantPermissions(Role::load(Role::AUTHENTICATED_ID), [
      'access contextual links',
      'administer blocks',
    ]);
    $this->drupalGet('user');
    $this->assertSession()->waitForElement('css', '.contextual button');
    $expected_destination_value = (string) $this->loggedInUser->toUrl()->toString();
    $contextual_link_url_parsed = parse_url($this->getSession()->getPage()->findLink('Configure block')->getAttribute('href'));
    $this->assertEquals("destination=$expected_destination_value", $contextual_link_url_parsed['query']);
  }

  /**
   * Tests the contextual links destination with query.
   */
  public function testContextualLinksDestinationWithQuery() {
    $this->grantPermissions(Role::load(Role::AUTHENTICATED_ID), [
      'access contextual links',
      'administer blocks',
    ]);

    $this->drupalGet('admin/structure/block', ['query' => ['foo' => 'bar']]);
    $this->assertSession()->waitForElement('css', '.contextual button');
    $expected_destination_value = Url::fromRoute('block.admin_display')->toString();
    $contextual_link_url_parsed = parse_url($this->getSession()->getPage()->findLink('Configure block')->getAttribute('href'));
    $this->assertEquals("destination=$expected_destination_value%3Ffoo%3Dbar", $contextual_link_url_parsed['query']);
  }

}
