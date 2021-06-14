<?php

namespace Drupal\Tests\settings_tray\FunctionalJavascript;

use Drupal\block\Entity\Block;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\user\Entity\Role;

/**
 * Tests handling of configuration overrides.
 *
 * @group settings_tray
 */
class OverriddenConfigurationTest extends SettingsTrayTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'settings_tray_override_test',
    'menu_ui',
    'menu_link_content',
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

    $user = $this->createUser([
      'administer blocks',
      'access contextual links',
      'access toolbar',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests blocks with overridden related configuration removed when overridden.
   */
  public function testOverriddenConfigurationRemoved() {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->grantPermissions(Role::load(Role::AUTHENTICATED_ID), ['administer site configuration', 'administer menu']);

    // Confirm the branding block does include 'site_information' section when
    // the site name is not overridden.
    $branding_block = $this->placeBlock('system_branding_block');
    $this->drupalGet('user');
    $this->enableEditMode();
    $this->openBlockForm($this->getBlockSelector($branding_block));
    $web_assert->fieldExists('settings[site_information][site_name]');
    // Confirm the branding block does not include 'site_information' section
    // when the site name is overridden.
    $this->container->get('state')->set('settings_tray_override_test.site_name', TRUE);
    $this->drupalGet('user');
    $this->openBlockForm($this->getBlockSelector($branding_block));
    $web_assert->fieldNotExists('settings[site_information][site_name]');
    $page->pressButton('Save Site branding');
    $this->assertElementVisibleAfterWait('css', 'div:contains(The block configuration has been saved)');
    $web_assert->assertWaitOnAjaxRequest();
    // Confirm we did not save changes to the configuration.
    $this->assertEquals('Llama Fan Club', \Drupal::configFactory()->get('system.site')->get('name'));
    $this->assertEquals('Drupal', \Drupal::configFactory()->getEditable('system.site')->get('name'));

    // Add a link or the menu will not render.
    $menu_link_content = MenuLinkContent::create([
      'title' => 'This is on the menu',
      'menu_name' => 'main',
      'link' => ['uri' => 'route:<front>'],
    ]);
    $menu_link_content->save();
    // Confirm the menu block does include menu section when the menu is not
    // overridden.
    $menu_block = $this->placeBlock('system_menu_block:main');
    $web_assert->assertWaitOnAjaxRequest();
    $this->drupalGet('user');
    $web_assert->pageTextContains('This is on the menu');
    $this->openBlockForm($this->getBlockSelector($menu_block));
    $web_assert->elementExists('css', '#menu-overview');

    // Confirm the menu block does not include menu section when the menu is
    // overridden.
    $this->container->get('state')->set('settings_tray_override_test.menu', TRUE);
    $this->drupalGet('user');
    $web_assert->pageTextContains('This is on the menu');
    $menu_with_overrides = \Drupal::configFactory()->get('system.menu.main')->get();
    $menu_without_overrides = \Drupal::configFactory()->getEditable('system.menu.main')->get();
    $this->openBlockForm($this->getBlockSelector($menu_block));
    $web_assert->elementNotExists('css', '#menu-overview');
    $page->pressButton('Save Main navigation');
    $this->assertElementVisibleAfterWait('css', 'div:contains(The block configuration has been saved)');
    $web_assert->assertWaitOnAjaxRequest();
    // Confirm we did not save changes to the configuration.
    $this->assertEquals('Labely label', \Drupal::configFactory()->get('system.menu.main')->get('label'));
    $this->assertEquals('Main navigation', \Drupal::configFactory()->getEditable('system.menu.main')->get('label'));
    $this->assertEquals($menu_with_overrides, \Drupal::configFactory()->get('system.menu.main')->get());
    $this->assertEquals($menu_without_overrides, \Drupal::configFactory()->getEditable('system.menu.main')->get());
    $web_assert->pageTextContains('This is on the menu');
  }

  /**
   * Tests that blocks with configuration overrides are disabled.
   */
  public function testOverriddenBlock() {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $overridden_block = $this->placeBlock('system_powered_by_block', [
      'id' => 'overridden_block',
      'label_display' => 1,
      'label' => 'This will be overridden.',
    ]);
    $this->drupalGet('user');
    $block_selector = $this->getBlockSelector($overridden_block);
    // Confirm the block is marked as Settings Tray editable.
    $this->assertEquals('editable', $page->find('css', $block_selector)->getAttribute('data-drupal-settingstray'));
    // Confirm the label is not overridden.
    $web_assert->elementContains('css', $block_selector, 'This will be overridden.');
    $this->enableEditMode();
    $this->openBlockForm($block_selector);

    // Confirm the block Settings Tray functionality is disabled when block is
    // overridden.
    $this->container->get('state')->set('settings_tray_override_test.block', TRUE);
    $overridden_block->save();
    $block_config = \Drupal::configFactory()->getEditable('block.block.overridden_block');
    $block_config->set('settings', $block_config->get('settings'))->save();

    $this->drupalGet('user');
    $this->assertOverriddenBlockDisabled($overridden_block, 'Now this will be the label.');

    // Test a non-overridden block does show the form in the off-canvas dialog.
    $block = $this->placeBlock('system_powered_by_block', [
      'label_display' => 1,
      'label' => 'Labely label',
    ]);
    $this->drupalGet('user');
    $block_selector = $this->getBlockSelector($block);
    // Confirm the block is marked as Settings Tray editable.
    $this->assertEquals('editable', $page->find('css', $block_selector)->getAttribute('data-drupal-settingstray'));
    // Confirm the label is not overridden.
    $web_assert->elementContains('css', $block_selector, 'Labely label');
    $this->openBlockForm($block_selector);
  }

  /**
   * Asserts that an overridden block has Settings Tray disabled.
   *
   * @param \Drupal\block\Entity\Block $overridden_block
   *   The overridden block.
   * @param string $override_text
   *   The override text that should appear in the block.
   */
  protected function assertOverriddenBlockDisabled(Block $overridden_block, $override_text) {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $block_selector = $this->getBlockSelector($overridden_block);
    $block_id = $overridden_block->id();
    // Confirm the block does not have a quick edit link.
    $contextual_links = $page->findAll('css', "$block_selector .contextual-links li a");
    $this->assertNotEmpty($contextual_links);
    foreach ($contextual_links as $link) {
      $this->assertStringNotContainsString("/admin/structure/block/manage/$block_id/off-canvas", $link->getAttribute('href'));
    }
    // Confirm the block is not marked as Settings Tray editable.
    $this->assertFalse($page->find('css', $block_selector)
      ->hasAttribute('data-drupal-settingstray'));

    // Confirm the text is actually overridden.
    $web_assert->elementContains('css', $this->getBlockSelector($overridden_block), $override_text);
  }

}
