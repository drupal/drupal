<?php

namespace Drupal\block\Tests;

use Drupal\Component\Utility\Html;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that the block configuration UI exists and stores data correctly.
 *
 * @group block
 */
class BlockUiTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('block', 'block_test', 'help');

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

  protected function setUp() {
    parent::setUp();
    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer blocks',
      'access administration pages',
    ));
    $this->drupalLogin($this->adminUser);

    // Enable some test blocks.
    $this->blockValues = array(
      array(
        'label' => 'Tools',
        'tr' => '5',
        'plugin_id' => 'system_menu_block:tools',
        'settings' => array('region' => 'sidebar_second', 'id' => 'tools'),
        'test_weight' => '-1',
      ),
      array(
        'label' => 'Powered by Drupal',
        'tr' => '16',
        'plugin_id' => 'system_powered_by_block',
        'settings' => array('region' => 'footer', 'id' => 'powered'),
        'test_weight' => '0',
      ),
    );
    $this->blocks = array();
    foreach ($this->blockValues as $values) {
      $this->blocks[] = $this->drupalPlaceBlock($values['plugin_id'], $values['settings']);
    }
  }

  /**
   * Test block demo page exists and functions correctly.
   */
  public function testBlockDemoUiPage() {
    $this->drupalPlaceBlock('help_block', array('region' => 'help'));
    $this->drupalGet('admin/structure/block');
    $this->clickLink(t('Demonstrate block regions (@theme)', array('@theme' => 'Classy')));
    $elements = $this->xpath('//div[contains(@class, "region-highlighted")]/div[contains(@class, "block-region") and contains(text(), :title)]', array(':title' => 'Highlighted'));
    $this->assertTrue(!empty($elements), 'Block demo regions are shown.');

    \Drupal::service('theme_handler')->install(array('test_theme'));
    $this->drupalGet('admin/structure/block/demo/test_theme');
    $this->assertEscaped('<strong>Test theme</strong>');

    \Drupal::service('theme_handler')->install(['stable']);
    $this->drupalGet('admin/structure/block/demo/stable');
    $this->assertResponse(404, 'Hidden themes that are not the default theme are not supported by the block demo screen');
  }

  /**
   * Test block admin page exists and functions correctly.
   */
  function testBlockAdminUiPage() {
    // Visit the blocks admin ui.
    $this->drupalGet('admin/structure/block');
    // Look for the blocks table.
    $blocks_table = $this->xpath("//table[@id='blocks']");
    $this->assertTrue(!empty($blocks_table), 'The blocks table is being rendered.');
    // Look for test blocks in the table.
    foreach ($this->blockValues as $delta => $values) {
      $block = $this->blocks[$delta];
      $label = $block->label();
      $element = $this->xpath('//*[@id="blocks"]/tbody/tr[' . $values['tr'] . ']/td[1]/text()');
      $this->assertTrue((string) $element[0] == $label, 'The "' . $label . '" block title is set inside the ' . $values['settings']['region'] . ' region.');
      // Look for a test block region select form element.
      $this->assertField('blocks[' . $values['settings']['id'] . '][region]', 'The block "' . $values['label'] . '" has a region assignment field.');
      // Move the test block to the header region.
      $edit['blocks[' . $values['settings']['id'] . '][region]'] = 'header';
      // Look for a test block weight select form element.
      $this->assertField('blocks[' . $values['settings']['id'] . '][weight]', 'The block "' . $values['label'] . '" has a weight assignment field.');
      // Change the test block's weight.
      $edit['blocks[' . $values['settings']['id'] . '][weight]'] = $values['test_weight'];
    }
    $this->drupalPostForm('admin/structure/block', $edit, t('Save blocks'));
    foreach ($this->blockValues as $values) {
      // Check if the region and weight settings changes have persisted.
      $this->assertOptionSelected(
        'edit-blocks-' . $values['settings']['id'] . '-region',
        'header',
        'The block "' . $label . '" has the correct region assignment (header).'
      );
      $this->assertOptionSelected(
        'edit-blocks-' . $values['settings']['id'] . '-weight',
        $values['test_weight'],
        'The block "' . $label . '" has the correct weight assignment (' . $values['test_weight'] . ').'
      );
    }

    // Add a block with a machine name the same as a region name.
    $this->drupalPlaceBlock('system_powered_by_block', ['region' => 'header', 'id' => 'header']);
    $this->drupalGet('admin/structure/block');
    $element = $this->xpath('//tr[contains(@class, :class)]', [':class' => 'region-title-header']);
    $this->assertTrue(!empty($element));

    // Ensure hidden themes do not appear in the UI. Enable another non base
    // theme and place the local tasks block.
    $this->assertTrue(\Drupal::service('theme_handler')->themeExists('classy'), 'The classy base theme is enabled');
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'header']);
    \Drupal::service('theme_installer')->install(['stable', 'stark']);
    $this->drupalGet('admin/structure/block');
    $theme_handler = \Drupal::service('theme_handler');
    $this->assertLink($theme_handler->getName('classy'));
    $this->assertLink($theme_handler->getName('stark'));
    $this->assertNoLink($theme_handler->getName('stable'));

    $this->drupalGet('admin/structure/block/list/stable');
    $this->assertResponse(404, 'Placing blocks through UI is not possible for a hidden base theme.');

    \Drupal::configFactory()->getEditable('system.theme')->set('admin', 'stable')->save();
    \Drupal::service('router.builder')->rebuildIfNeeded();
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'header', 'theme' => 'stable']);
    $this->drupalGet('admin/structure/block');
    $this->assertLink($theme_handler->getName('stable'));
    $this->drupalGet('admin/structure/block/list/stable');
    $this->assertResponse(200, 'Placing blocks through UI is possible for a hidden base theme that is the admin theme.');
  }

  /**
   * Tests the block categories on the listing page.
   */
  public function testCandidateBlockList() {
    $arguments = array(
      ':title' => 'Display message',
      ':category' => 'Block test',
      ':href' => 'admin/structure/block/add/test_block_instantiation/classy',
    );
    $pattern = '//tr[.//td/div[text()=:title] and .//td[text()=:category] and .//td//a[contains(@href, :href)]]';

    $this->drupalGet('admin/structure/block');
    $this->clickLinkPartialName('Place block');
    $elements = $this->xpath($pattern, $arguments);
    $this->assertTrue(!empty($elements), 'The test block appears in the category for its module.');

    // Trigger the custom category addition in block_test_block_alter().
    $this->container->get('state')->set('block_test_info_alter', TRUE);
    $this->container->get('plugin.manager.block')->clearCachedDefinitions();

    $this->drupalGet('admin/structure/block');
    $this->clickLinkPartialName('Place block');
    $arguments[':category'] = 'Custom category';
    $elements = $this->xpath($pattern, $arguments);
    $this->assertTrue(!empty($elements), 'The test block appears in a custom category controlled by block_test_block_alter().');
  }

  /**
   * Tests the behavior of unsatisfied context-aware blocks.
   */
  public function testContextAwareUnsatisfiedBlocks() {
    $arguments = array(
      ':category' => 'Block test',
      ':href' => 'admin/structure/block/add/test_context_aware_unsatisfied/classy',
      ':text' => 'Test context-aware unsatisfied block',
    );

    $this->drupalGet('admin/structure/block');
    $this->clickLinkPartialName('Place block');
    $elements = $this->xpath('//tr[.//td/div[text()=:text] and .//td[text()=:category] and .//td//a[contains(@href, :href)]]', $arguments);
    $this->assertTrue(empty($elements), 'The context-aware test block does not appear.');

    $definition = \Drupal::service('plugin.manager.block')->getDefinition('test_context_aware_unsatisfied');
    $this->assertTrue(!empty($definition), 'The context-aware test block does not exist.');
  }

  /**
   * Tests the behavior of context-aware blocks.
   */
  public function testContextAwareBlocks() {
    $expected_text = '<div id="test_context_aware--username">' . \Drupal::currentUser()->getUsername() . '</div>';
    $this->drupalGet('');
    $this->assertNoText('Test context-aware block');
    $this->assertNoRaw($expected_text);

    $block_url = 'admin/structure/block/add/test_context_aware/classy';
    $arguments = array(
      ':title' => 'Test context-aware block',
      ':category' => 'Block test',
      ':href' => $block_url,
    );
    $pattern = '//tr[.//td/div[text()=:title] and .//td[text()=:category] and .//td//a[contains(@href, :href)]]';

    $this->drupalGet('admin/structure/block');
    $this->clickLinkPartialName('Place block');
    $elements = $this->xpath($pattern, $arguments);
    $this->assertTrue(!empty($elements), 'The context-aware test block appears.');
    $definition = \Drupal::service('plugin.manager.block')->getDefinition('test_context_aware');
    $this->assertTrue(!empty($definition), 'The context-aware test block exists.');
    $edit = [
      'region' => 'content',
      'settings[context_mapping][user]' => '@block_test.multiple_static_context:user2',
    ];
    $this->drupalPostForm($block_url, $edit, 'Save block');

    $this->drupalGet('');
    $this->assertText('Test context-aware block');
    $this->assertText('User context found.');
    $this->assertRaw($expected_text);

    // Test context mapping allows empty selection for optional contexts.
    $this->drupalGet('admin/structure/block/manage/testcontextawareblock');
    $edit = [
      'settings[context_mapping][user]' => '',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save block');
    $this->drupalGet('');
    $this->assertText('No context mapping selected.');
    $this->assertNoText('User context found.');
  }

  /**
   * Tests that the BlockForm populates machine name correctly.
   */
  public function testMachineNameSuggestion() {
    $url = 'admin/structure/block/add/test_block_instantiation/classy';
    $this->drupalGet($url);
    $this->assertFieldByName('id', 'displaymessage', 'Block form uses raw machine name suggestion when no instance already exists.');
    $this->drupalPostForm($url, array(), 'Save block');

    // Now, check to make sure the form starts by autoincrementing correctly.
    $this->drupalGet($url);
    $this->assertFieldByName('id', 'displaymessage_2', 'Block form appends _2 to plugin-suggested machine name when an instance already exists.');
    $this->drupalPostForm($url, array(), 'Save block');

    // And verify that it continues working beyond just the first two.
    $this->drupalGet($url);
    $this->assertFieldByName('id', 'displaymessage_3', 'Block form appends _3 to plugin-suggested machine name when two instances already exist.');
  }

  /**
   * Tests the block placement indicator.
   */
  public function testBlockPlacementIndicator() {
    // Select the 'Powered by Drupal' block to be placed.
    $block = array();
    $block['id'] = strtolower($this->randomMachineName());
    $block['theme'] = 'classy';
    $block['region'] = 'content';

    // After adding a block, it will indicate which block was just added.
    $this->drupalPostForm('admin/structure/block/add/system_powered_by_block', $block, t('Save block'));
    $this->assertUrl('admin/structure/block/list/classy?block-placement=' . Html::getClass($block['id']));

    // Resaving the block page will remove the block indicator.
    $this->drupalPostForm(NULL, array(), t('Save blocks'));
    $this->assertUrl('admin/structure/block/list/classy');
  }

  /**
   * Tests if validation errors are passed plugin form to the parent form.
   */
  public function testBlockValidateErrors() {
    $this->drupalPostForm('admin/structure/block/add/test_settings_validation/classy', ['settings[digits]' => 'abc'], t('Save block'));

    $arguments = [':message' => 'Only digits are allowed'];
    $pattern = '//div[contains(@class,"messages messages--error")]/div[contains(text()[2],:message)]';
    $elements = $this->xpath($pattern, $arguments);
    $this->assertTrue($elements, 'Plugin error message found in parent form.');

    $error_class_pattern = '//div[contains(@class,"form-item-settings-digits")]/input[contains(@class,"error")]';
    $error_class = $this->xpath($error_class_pattern);
    $this->assertTrue($error_class, 'Plugin error class found in parent form.');
  }

}
