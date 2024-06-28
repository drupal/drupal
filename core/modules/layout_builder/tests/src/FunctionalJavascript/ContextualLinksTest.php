<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

// cspell:ignore blocktest

/**
 * Test contextual links compatibility with the Layout Builder.
 *
 * @group layout_builder
 */
class ContextualLinksTest extends WebDriverTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'views_ui',
    'layout_builder',
    'layout_builder_views_test',
    'layout_test',
    'block',
    'node',
    'contextual',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $user = $this->drupalCreateUser([
      'configure any layout',
      'access contextual links',
      'administer nodes',
      'bypass node access',
      'administer views',
      'administer blocks',
    ]);
    $user->save();
    $this->drupalLogin($user);
    $this->createContentType(['type' => 'bundle_with_section_field']);
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $this->createNode([
      'type' => 'bundle_with_section_field',
      'body' => [
        [
          'value' => 'The node body',
        ],
      ],
    ]);
  }

  /**
   * Tests that the contextual links inside Layout Builder are removed.
   */
  public function testContextualLinks(): void {
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/1/layout');

    // Add a block that includes an entity contextual link.
    $this->addBlock('Test Block View: Teaser block');

    // Add a block that includes a views contextual link.
    $this->addBlock('Recent content');

    // Ensure the contextual links are correct before the layout is saved.
    $this->assertCorrectContextualLinksInUi();

    // Ensure the contextual links are correct when the Layout Builder is loaded
    // after being saved.
    $page->hasButton('Save layout');
    $page->pressButton('Save layout');
    $this->drupalGet('node/1/layout');
    $this->assertCorrectContextualLinksInUi();

    $this->drupalGet('node/1');
    $this->assertCorrectContextualLinksInNode();
  }

  /**
   * Tests that contextual links outside the layout are removed.
   */
  public function testContextualLinksOutsideLayout(): void {
    $assert_session = $this->assertSession();
    $this->drupalPlaceBlock('system_powered_by_block', ['id' => 'global_block']);

    $this->drupalGet('node/1');
    // Ensure global blocks contextual link is present when not on
    // Layout Builder.
    $assert_session->elementsCount('css', '[data-contextual-id*=\'block:block=global_block:\']', 1);

    $this->drupalGet('node/1/layout');
    $this->addBlock('Test Block View: Teaser block');
    // Ensure that only the layout specific contextual links are present.
    $this->assertCorrectContextualLinks();

    $page = $this->getSession()->getPage();
    $page->hasButton('Save layout');
    $page->pressButton('Save layout');
    $this->drupalGet('node/1/layout');

    // Ensure the contextual links are correct when the Layout Builder is loaded
    // after being saved.
    $this->assertCorrectContextualLinks();
  }

  /**
   * Adds block to the layout via Layout Builder's UI.
   *
   * @param string $block_name
   *   The block name as it appears in the Add block form.
   */
  protected function addBlock($block_name) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $assert_session->linkExists('Add block');
    $page->clickLink('Add block');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', "#drupal-off-canvas a:contains('$block_name')"));
    $page->clickLink($block_name);
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '[data-drupal-selector=\'edit-actions-submit\']'));

    $page->pressButton('Add block');
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $assert_session->assertWaitOnAjaxRequest();
  }

  /**
   * Asserts the contextual links are correct in Layout Builder UI.
   *
   * @internal
   */
  protected function assertCorrectContextualLinksInUi(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.block-views-blocktest-block-view-block-2'));

    $layout_builder_specific_contextual_links = $page->findAll('css', '[data-contextual-id*=\'layout_builder_block:\']');
    $this->assertNotEmpty($layout_builder_specific_contextual_links);

    // Confirms Layout Builder contextual links are the only contextual links
    // inside the Layout Builder UI.
    $this->assertSameSize($layout_builder_specific_contextual_links, $page->findAll('css', '#layout-builder [data-contextual-id]'));
  }

  /**
   * Asserts the contextual links are correct on the canonical entity route.
   *
   * @internal
   */
  protected function assertCorrectContextualLinksInNode(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '[data-contextual-id]'));

    // Ensure that no Layout Builder contextual links are visible on node view.
    $this->assertEmpty($page->findAll('css', '[data-contextual-id*=\'layout_builder_block:\']'));

    // Ensure that the contextual links that are hidden in Layout Builder UI
    // are visible on node view.
    $this->assertNotEmpty($page->findAll('css', '.layout-content [data-contextual-id]'));
  }

  /**
   * Assert the contextual links are correct.
   *
   * @internal
   */
  protected function assertCorrectContextualLinks() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.block-views-blocktest-block-view-block-2'));
    $assert_session->assertNoElementAfterWait('css', '[data-contextual-id*=\'node:\']');
    // Ensure that the Layout Builder's own contextual links are not removed.
    $this->assertCount(3, $page->findAll('css', '[data-contextual-id*=\'layout_builder_block:\']'));
    // Ensure that the global block's contextual links are removed.
    $assert_session->elementNotExists('css', '[data-contextual-id*=\'block:block=global_block:\']');
  }

}
