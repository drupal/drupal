<?php

declare(strict_types=1);

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

    // Confirm touchevents detection is loaded with Contextual Links
    $this->assertSession()->elementExists('css', 'html.no-touchevents');

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
    $this->clickContextualLink('#block-branding', 'Test Link');
    $this->assertSession()->pageTextContains('Everything is contextual!');

    // Test click a contextual link that uses ajax.
    $this->drupalGet('user');
    $current_page_string = 'NOT_RELOADED_IF_ON_PAGE';
    $this->getSession()->executeScript('document.body.appendChild(document.createTextNode("' . $current_page_string . '"));');

    // Move the pointer over the branding block so the contextual link appears
    // as it would with a real user interaction. Otherwise clickContextualLink()
    // does not open the dialog in a manner that is opener-aware, and it isn't
    // possible to reliably test focus management.
    $driver_session = $this->getSession()->getDriver()->getWebDriverSession();
    $element = $driver_session->element('css selector', '#block-branding');
    $driver_session->moveto(['element' => $element->getID()]);
    $this->clickContextualLink('#block-branding', 'Test Link with Ajax', FALSE);
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '#drupal-modal'));
    $this->assertSession()->elementContains('css', '#drupal-modal', 'Everything is contextual!');
    $this->getSession()->executeScript('document.querySelector("#block-branding .trigger").addEventListener("focus", (e) => e.target.classList.add("i-am-focused"))');
    $this->getSession()->getPage()->pressButton('Close');
    $this->assertSession()->assertNoElementAfterWait('css', 'ui.dialog');

    // When the dialog is closed, the opening contextual link is now inside a
    // collapsed container, so focus should be routed to the contextual link
    // toggle button.
    $this->assertNotNull($this->assertSession()->waitForElement('css', '.trigger.i-am-focused'), $this->getSession()->getPage()->find('css', '#block-branding')->getOuterHtml());
    $this->assertJsCondition('document.activeElement === document.querySelector("#block-branding button.trigger")', 10000, 'Focus should be on the contextual trigger, but instead is at ' . $this->getSession()->evaluateScript('document.activeElement.outerHTML'));

    // Check to make sure that page was not reloaded.
    $this->assertSession()->pageTextContains($current_page_string);

    // Test clicking contextual link with toolbar.
    $this->container->get('module_installer')->install(['toolbar']);
    $this->grantPermissions(Role::load(Role::AUTHENTICATED_ID), ['access toolbar']);
    $this->drupalGet('user');
    $this->assertSession()->assertExpectedAjaxRequest(1);

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
