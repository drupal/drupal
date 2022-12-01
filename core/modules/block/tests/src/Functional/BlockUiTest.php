<?php

namespace Drupal\Tests\block\Functional;

use Drupal\Component\Utility\Html;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\Tests\BrowserTestBase;

// cspell:ignore scriptalertxsssubjectscript

/**
 * Tests that the block configuration UI exists and stores data correctly.
 *
 * @group block
 */
class BlockUiTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'block_test',
    'help',
    'condition_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected $regions;

  /**
   * The submitted block values used by this test.
   *
   * @var array
   */
  protected $blockValues;

  /**
   * The block entities used by this test.
   *
   * @var \Drupal\block\BlockInterface[]
   */
  protected $blocks;

  /**
   * An administrative user to configure the test environment.
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);

    // Enable some test blocks.
    $this->blockValues = [
      [
        'label' => 'Tools',
        'tr' => '6',
        'plugin_id' => 'system_menu_block:tools',
        'settings' => ['region' => 'sidebar_second', 'id' => 'tools'],
        'test_weight' => '-1',
      ],
      [
        'label' => 'Powered by Drupal',
        'tr' => '17',
        'plugin_id' => 'system_powered_by_block',
        'settings' => ['region' => 'footer', 'id' => 'powered'],
        'test_weight' => '0',
      ],
    ];
    $this->blocks = [];
    foreach ($this->blockValues as $values) {
      $this->blocks[] = $this->drupalPlaceBlock($values['plugin_id'], $values['settings']);
    }
  }

  /**
   * Tests block demo page exists and functions correctly.
   */
  public function testBlockDemoUiPage() {
    $this->drupalPlaceBlock('help_block', ['region' => 'help']);
    $this->drupalGet('admin/structure/block');
    $this->clickLink('Demonstrate block regions (Stark)');
    $this->assertSession()->elementExists('xpath', '//header[@role = "banner"]/div/div[contains(@class, "block-region") and contains(text(), "Header")]');

    // Ensure that other themes can use the block demo page.
    \Drupal::service('theme_installer')->install(['test_theme']);
    $this->drupalGet('admin/structure/block/demo/test_theme');
    $this->assertSession()->assertEscaped('<strong>Test theme</strong>');

    // Ensure that a hidden theme cannot use the block demo page.
    \Drupal::service('theme_installer')->install(['stable9']);
    $this->drupalGet('admin/structure/block/demo/stable9');
    $this->assertSession()->statusCodeEquals(404);

    // Delete all blocks and verify saving the block layout results in a
    // validation error.
    $block_storage = \Drupal::service('entity_type.manager')->getStorage('block');
    $blocks = $block_storage->loadMultiple();
    foreach ($blocks as $block) {
      $block->delete();
    }
    $this->drupalGet('admin/structure/block');
    $blocks_table = $this->xpath("//tr[@class='block-enabled']");
    $this->assertEmpty($blocks_table, 'The blocks table is now empty.');
    $this->submitForm([], 'Save blocks');
    $this->assertSession()->pageTextContains('No blocks settings to update');

  }

  /**
   * Tests block admin page exists and functions correctly.
   */
  public function testBlockAdminUiPage() {
    // Visit the blocks admin ui.
    $this->drupalGet('admin/structure/block');
    // Look for the blocks table.
    $this->assertSession()->elementExists('xpath', "//table[@id='blocks']");
    // Look for test blocks in the table.
    foreach ($this->blockValues as $delta => $values) {
      $block = $this->blocks[$delta];
      $label = $block->label();
      $this->assertSession()->elementTextEquals('xpath', '//*[@id="blocks"]/tbody/tr[' . $values['tr'] . ']/td[1]/text()', $label);
      // Look for a test block region select form element.
      $this->assertSession()->fieldExists('blocks[' . $values['settings']['id'] . '][region]');
      // Move the test block to the header region.
      $edit['blocks[' . $values['settings']['id'] . '][region]'] = 'header';
      // Look for a test block weight select form element.
      $this->assertSession()->fieldExists('blocks[' . $values['settings']['id'] . '][weight]');
      // Change the test block's weight.
      $edit['blocks[' . $values['settings']['id'] . '][weight]'] = $values['test_weight'];
    }
    $this->drupalGet('admin/structure/block');
    $this->submitForm($edit, 'Save blocks');
    foreach ($this->blockValues as $values) {
      // Check if the region and weight settings changes have persisted.
      $this->assertTrue($this->assertSession()->optionExists('edit-blocks-' . $values['settings']['id'] . '-region', 'header')->isSelected());
      $this->assertTrue($this->assertSession()->optionExists('edit-blocks-' . $values['settings']['id'] . '-weight', $values['test_weight'])->isSelected());
    }

    // Add a block with a machine name the same as a region name.
    $this->drupalPlaceBlock('system_powered_by_block', ['region' => 'header', 'id' => 'header']);
    $this->drupalGet('admin/structure/block');
    $this->assertSession()->elementExists('xpath', '//tr[contains(@class, "region-title-header")]');

    // Ensure hidden themes do not appear in the UI. Enable another non base
    // theme and place the local tasks block.
    $this->assertTrue(\Drupal::service('theme_handler')->themeExists('stark'), 'The stark base theme is enabled');
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'header', 'theme' => 'stark']);
    // We have to enable at least one extra theme that is not hidden so that
    // local tasks will show up. That's why we enable test_theme_theme.
    \Drupal::service('theme_installer')->install(['stable9', 'test_theme_theme']);
    $this->drupalGet('admin/structure/block');
    $theme_handler = \Drupal::service('theme_handler');
    $this->assertSession()->linkExists($theme_handler->getName('stark'));
    $this->assertSession()->linkExists($theme_handler->getName('test_theme_theme'));
    $this->assertSession()->linkNotExists($theme_handler->getName('stable9'));

    // Ensure that a hidden theme cannot use the block demo page.
    $this->drupalGet('admin/structure/block/list/stable9');
    $this->assertSession()->statusCodeEquals(404);

    // Ensure that a hidden theme set as the admin theme can use the block demo
    // page.
    \Drupal::configFactory()->getEditable('system.theme')->set('admin', 'stable9')->save();
    \Drupal::service('router.builder')->rebuildIfNeeded();
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'header', 'theme' => 'stable9']);
    $this->drupalGet('admin/structure/block');
    $this->assertSession()->linkExists($theme_handler->getName('stable9'));
    $this->drupalGet('admin/structure/block/list/stable9');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the block categories on the listing page.
   */
  public function testCandidateBlockList() {
    $this->drupalGet('admin/structure/block');
    $this->clickLink('Place block');
    $this->assertSession()->elementExists('xpath', '//tr[.//td/div[text()="Display message"] and .//td[text()="Block test"] and .//td//a[contains(@href, "admin/structure/block/add/test_block_instantiation/stark")]]');

    // Trigger the custom category addition in block_test_block_alter().
    $this->container->get('state')->set('block_test_info_alter', TRUE);
    $this->container->get('plugin.manager.block')->clearCachedDefinitions();

    $this->drupalGet('admin/structure/block');
    $this->clickLink('Place block');
    $this->assertSession()->elementExists('xpath', '//tr[.//td/div[text()="Display message"] and .//td[text()="Custom category"] and .//td//a[contains(@href, "admin/structure/block/add/test_block_instantiation/stark")]]');
  }

  /**
   * Tests the behavior of unsatisfied context-aware blocks.
   */
  public function testContextAwareUnsatisfiedBlocks() {
    $this->drupalGet('admin/structure/block');
    $this->clickLink('Place block');
    // Verify that the context-aware test block does not appear.
    $this->assertSession()->elementNotExists('xpath', '//tr[.//td/div[text()="Test context-aware unsatisfied block"] and .//td[text()="Block test"] and .//td//a[contains(@href, "admin/structure/block/add/test_context_aware_unsatisfied/stark")]]');

    $definition = \Drupal::service('plugin.manager.block')->getDefinition('test_context_aware_unsatisfied');
    $this->assertNotEmpty($definition, 'The context-aware test block does not exist.');
  }

  /**
   * Tests the behavior of context-aware blocks.
   */
  public function testContextAwareBlocks() {
    $expected_text = '<div id="test_context_aware--username">' . \Drupal::currentUser()->getAccountName() . '</div>';
    $this->drupalGet('');
    $this->assertSession()->pageTextNotContains('Test context-aware block');
    $this->assertSession()->responseNotContains($expected_text);

    $block_url = 'admin/structure/block/add/test_context_aware/stark';

    $this->drupalGet('admin/structure/block');
    $this->clickLink('Place block');
    $this->assertSession()->elementExists('xpath', '//tr[.//td/div[text()="Test context-aware block"] and .//td[text()="Block test"] and .//td//a[contains(@href, "' . $block_url . '")]]');
    $definition = \Drupal::service('plugin.manager.block')->getDefinition('test_context_aware');
    $this->assertNotEmpty($definition, 'The context-aware test block exists.');
    $edit = [
      'region' => 'content',
      'settings[context_mapping][user]' => '@block_test.multiple_static_context:userB',
    ];
    $this->drupalGet($block_url);
    $this->submitForm($edit, 'Save block');

    $this->drupalGet('');
    $this->assertSession()->pageTextContains('Test context-aware block');
    $this->assertSession()->pageTextContains('User context found.');
    $this->assertSession()->responseContains($expected_text);

    // Test context mapping form element is not visible if there are no valid
    // context options for the block (the test_context_aware_no_valid_context_options
    // block has one context defined which is not available for it on the
    // Block Layout interface).
    $this->drupalGet('admin/structure/block/add/test_context_aware_no_valid_context_options/stark');
    $this->assertSession()->fieldNotExists('edit-settings-context-mapping-email');

    // Test context mapping allows empty selection for optional contexts.
    $this->drupalGet('admin/structure/block/manage/testcontextawareblock');
    $edit = [
      'settings[context_mapping][user]' => '',
    ];
    $this->submitForm($edit, 'Save block');
    $this->drupalGet('');
    $this->assertSession()->pageTextContains('No context mapping selected.');
    $this->assertSession()->pageTextNotContains('User context found.');

    // Tests that conditions with missing context are not displayed.
    $this->drupalGet('admin/structure/block/manage/testcontextawareblock');
    $this->assertSession()->responseNotContains('No existing type');
    $this->assertSession()->elementNotExists('xpath', '//*[@name="visibility[condition_test_no_existing_type][negate]"]');
  }

  /**
   * Tests that the BlockForm populates machine name correctly.
   */
  public function testMachineNameSuggestion() {
    // Check the form uses the raw machine name suggestion when no instance
    // already exists.
    $url = 'admin/structure/block/add/test_block_instantiation/stark';
    $this->drupalGet($url);
    $this->assertSession()->fieldValueEquals('id', 'displaymessage');
    $edit = ['region' => 'content'];
    $this->drupalGet($url);
    $this->submitForm($edit, 'Save block');
    $this->assertSession()->pageTextContains('The block configuration has been saved.');

    // Now, check to make sure the form starts by auto-incrementing correctly.
    $this->drupalGet($url);
    $this->assertSession()->fieldValueEquals('id', 'displaymessage_2');
    $this->drupalGet($url);
    $this->submitForm($edit, 'Save block');
    $this->assertSession()->pageTextContains('The block configuration has been saved.');

    // And verify that it continues working beyond just the first two.
    $this->drupalGet($url);
    $this->assertSession()->fieldValueEquals('id', 'displaymessage_3');
  }

  /**
   * Tests the block placement indicator.
   */
  public function testBlockPlacementIndicator() {
    // Test the block placement indicator with using the domain as URL language
    // indicator. This causes destination query parameters to be absolute URLs.
    \Drupal::service('module_installer')->install(['language', 'locale']);
    $this->container = \Drupal::getContainer();
    ConfigurableLanguage::createFromLangcode('it')->save();
    $config = $this->config('language.types');
    $config->set('negotiation.language_interface.enabled', [
      LanguageNegotiationUrl::METHOD_ID => -10,
    ]);
    $config->save();
    $config = $this->config('language.negotiation');
    $config->set('url.source', LanguageNegotiationUrl::CONFIG_DOMAIN);
    $config->set('url.domains', [
      'en' => \Drupal::request()->getHost(),
      'it' => 'it.example.com',
    ]);
    $config->save();

    // Select the 'Powered by Drupal' block to be placed.
    $block = [];
    $block['id'] = strtolower($this->randomMachineName());
    $block['theme'] = 'stark';
    $block['region'] = 'content';

    // After adding a block, it will indicate which block was just added.
    $this->drupalGet('admin/structure/block/add/system_powered_by_block');
    $this->submitForm($block, 'Save block');
    $this->assertSession()->addressEquals('admin/structure/block/list/stark?block-placement=' . Html::getClass($block['id']));

    // Resaving the block page will remove the block placement indicator.
    $this->submitForm([], 'Save blocks');
    $this->assertSession()->addressEquals('admin/structure/block/list/stark');

    // Place another block and test the remove functionality works with the
    // block placement indicator. Click the first 'Place block' link to bring up
    // the list of blocks to place in the first available region.
    $this->clickLink('Place block');
    // Select the first available block, which is the 'test_xss_title' plugin,
    // with a default machine name 'scriptalertxsssubjectscript' that is used
    // for the 'block-placement' querystring parameter.
    $this->clickLink('Place block');
    $this->submitForm([], 'Save block');
    $this->assertSession()->addressEquals('admin/structure/block/list/stark?block-placement=scriptalertxsssubjectscript');

    // Removing a block will remove the block placement indicator.
    $this->clickLink('Remove');
    $this->submitForm([], 'Remove');
    // @todo https://www.drupal.org/project/drupal/issues/2980527 this should be
    //   'admin/structure/block/list/stark' but there is a bug.
    $this->assertSession()->addressEquals('admin/structure/block');
  }

  /**
   * Tests if validation errors are passed plugin form to the parent form.
   */
  public function testBlockValidateErrors() {
    $this->drupalGet('admin/structure/block/add/test_settings_validation/stark');
    $this->submitForm([
      'region' => 'content',
      'settings[digits]' => 'abc',
    ], 'Save block');

    $this->assertSession()->statusMessageContains('Only digits are allowed', 'error');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class,"form-item-settings-digits")]/input[contains(@class,"error")]');
  }

  /**
   * Tests that the enable/disable routes are protected from CSRF.
   */
  public function testRouteProtection() {
    // Get the first block generated in our setUp method.
    /** @var \Drupal\block\BlockInterface $block */
    $block = reset($this->blocks);
    // Ensure that the enable and disable routes are protected.
    $this->drupalGet('admin/structure/block/manage/' . $block->id() . '/disable');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/structure/block/manage/' . $block->id() . '/enable');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that users without permission are not able to view broken blocks.
   */
  public function testBrokenBlockVisibility() {
    $assert_session = $this->assertSession();

    $block = $this->drupalPlaceBlock('broken');

    // Ensure that broken block configuration can be accessed.
    $this->drupalGet('admin/structure/block/manage/' . $block->id());
    $assert_session->statusCodeEquals(200);

    // Login as an admin user to the site.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('');
    $assert_session->statusCodeEquals(200);
    // Check that this user can view the Broken Block message.
    $assert_session->pageTextContains('This block is broken or missing. You may be missing content or you might need to enable the original module.');
    $this->drupalLogout();

    // Visit the same page as anonymous.
    $this->drupalGet('');
    $assert_session->statusCodeEquals(200);
    // Check that this user cannot view the Broken Block message.
    $assert_session->pageTextNotContains('This block is broken or missing. You may be missing content or you might need to enable the original module.');

    // Visit same page as an authorized user that does not have access to
    // administer blocks.
    $this->drupalLogin($this->drupalCreateUser(['access administration pages']));
    $this->drupalGet('');
    $assert_session->statusCodeEquals(200);
    // Check that this user cannot view the Broken Block message.
    $assert_session->pageTextNotContains('This block is broken or missing. You may be missing content or you might need to enable the original module.');
  }

}
