<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockUiTest.
 */

namespace Drupal\block\Tests;

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
  public static $modules = array('block', 'block_test');

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
    $this->drupalPlaceBlock('system_help_block', array('region' => 'help'));
    $this->drupalGet('admin/structure/block');
    $this->clickLink(t('Demonstrate block regions (@theme)', array('@theme' => 'Classy')));
    $elements = $this->xpath('//div[contains(@class, "region-highlighted")]/div[contains(@class, "block-region") and contains(text(), :title)]', array(':title' => 'Highlighted'));
    $this->assertTrue(!empty($elements), 'Block demo regions are shown.');
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
        'edit-blocks-' . $values['settings']['id']  . '-region',
        'header',
        'The block "' . $label . '" has the correct region assignment (header).'
      );
      $this->assertOptionSelected(
        'edit-blocks-' . $values['settings']['id']  . '-weight',
        $values['test_weight'],
        'The block "' . $label . '" has the correct weight assignment (' . $values['test_weight'] . ').'
      );
    }
  }

  /**
   * Tests the block categories on the listing page.
   */
  public function testCandidateBlockList() {
    $arguments = array(
      ':ul_class' => 'block-list',
      ':li_class' => 'test-block-instantiation',
      ':href' => 'admin/structure/block/add/test_block_instantiation/classy',
      ':text' => 'Display message',
    );

    $this->drupalGet('admin/structure/block');
    $elements = $this->xpath('//details[@id="edit-category-block-test"]//ul[contains(@class, :ul_class)]/li[contains(@class, :li_class)]/a[contains(@href, :href) and text()=:text]', $arguments);
    $this->assertTrue(!empty($elements), 'The test block appears in the category for its module.');

    // Trigger the custom category addition in block_test_block_alter().
    $this->container->get('state')->set('block_test_info_alter', TRUE);
    $this->container->get('plugin.manager.block')->clearCachedDefinitions();

    $this->drupalGet('admin/structure/block');
    $elements = $this->xpath('//details[@id="edit-category-custom-category"]//ul[contains(@class, :ul_class)]/li[contains(@class, :li_class)]/a[contains(@href, :href) and text()=:text]', $arguments);
    $this->assertTrue(!empty($elements), 'The test block appears in a custom category controlled by block_test_block_alter().');
  }

  /**
   * Tests the behavior of context-aware blocks.
   */
  public function testContextAwareBlocks() {
    $arguments = array(
      ':ul_class' => 'block-list',
      ':li_class' => 'test-context-aware',
      ':href' => 'admin/structure/block/add/test_context_aware/classy',
      ':text' => 'Test context-aware block',
    );

    $this->drupalGet('admin/structure/block');
    $elements = $this->xpath('//details[@id="edit-category-block-test"]//ul[contains(@class, :ul_class)]/li[contains(@class, :li_class)]/a[contains(@href, :href) and text()=:text]', $arguments);
    $this->assertTrue(empty($elements), 'The context-aware test block does not appear.');
    $definition = \Drupal::service('plugin.manager.block')->getDefinition('test_context_aware');
    $this->assertTrue(!empty($definition), 'The context-aware test block exists.');
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
    $this->assertUrl('admin/structure/block/list/classy?block-placement=' . drupal_html_class($block['id']));

    // Resaving the block page will remove the block indicator.
    $this->drupalPostForm(NULL, array(), t('Save blocks'));
    $this->assertUrl('admin/structure/block/list/classy');
  }

}
