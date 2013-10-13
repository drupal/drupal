<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockUiTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the block configuration UI.
 */
class BlockUiTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'block_test');

  protected $regions;

  /**
   * An administrative user to configure the test environment.
   */
  protected $adminUser;

  public static function getInfo() {
    return array(
      'name' => 'Block UI',
      'description' => 'Checks that the block configuration UI exists and stores data correctly.',
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp();
    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer blocks',
      'access administration pages',
    ));
    $this->drupalLogin($this->adminUser);

    // Enable some test blocks.
    $this->testBlocks = array(
      array(
        'label' => 'Tools',
        'tr' => '5',
        'plugin_id' => 'system_menu_block:tools',
        'settings' => array('region' => 'sidebar_second', 'machine_name' => 'tools'),
        'test_weight' => '-1',
      ),
      array(
        'label' => 'Powered by Drupal',
        'tr' => '12',
        'plugin_id' => 'system_powered_by_block',
        'settings' => array('region' => 'footer', 'machine_name' => 'powered'),
        'test_weight' => '0',
      ),
    );
    foreach ($this->testBlocks as $values) {
      $this->drupalPlaceBlock($values['plugin_id'], $values['settings']);
    }
  }

  /**
   * Test block demo page exists and functions correctly.
   */
  public function testBlockDemoUiPage() {
    $this->drupalPlaceBlock('system_help_block', array('region' => 'help'));
    $this->drupalGet('admin/structure/block');
    $this->clickLink(t('Demonstrate block regions (@theme)', array('@theme' => 'Stark')));
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
    foreach ($this->testBlocks as $values) {
      $element = $this->xpath('//*[@id="blocks"]/tbody/tr[' . $values['tr'] . ']/td[1]/text()');
      $this->assertTrue((string)$element[0] == $values['label'], 'The "' . $values['label'] . '" block title is set inside the ' . $values['settings']['region'] . ' region.');
      // Look for a test block region select form element.
      $this->assertField('blocks[stark.' . $values['settings']['machine_name'] . '][region]', 'The block "' . $values['label'] . '" has a region assignment field.');
      // Move the test block to the header region.
      $edit['blocks[stark.' . $values['settings']['machine_name'] . '][region]'] = 'header';
      // Look for a test block weight select form element.
      $this->assertField('blocks[stark.' . $values['settings']['machine_name'] . '][weight]', 'The block "' . $values['label'] . '" has a weight assignment field.');
      // Change the test block's weight.
      $edit['blocks[stark.' . $values['settings']['machine_name'] . '][weight]'] = $values['test_weight'];
    }
    $this->drupalPostForm('admin/structure/block', $edit, t('Save blocks'));
    foreach ($this->testBlocks as $values) {
      // Check if the region and weight settings changes have persisted.
      $this->assertOptionSelected(
        'edit-blocks-stark' . $values['settings']['machine_name']  . '-region',
        'header',
        'The block "' . $values['label'] . '" has the correct region assignment (header).'
      );
      $this->assertOptionSelected(
        'edit-blocks-stark' . $values['settings']['machine_name']  . '-weight',
        $values['test_weight'],
        'The block "' . $values['label'] . '" has the correct weight assignment (' . $values['test_weight'] . ').'
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
      ':href' => 'admin/structure/block/add/test_block_instantiation/stark',
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
   * Tests that the BlockFormController populates machine name correctly.
   */
  public function testMachineNameSuggestion() {
    $url = 'admin/structure/block/add/test_block_instantiation/stark';
    $this->drupalGet($url);
    $this->assertFieldByName('machine_name', 'displaymessage', 'Block form uses raw machine name suggestion when no instance already exists.');
    $this->drupalPostForm($url, array(), 'Save block');

    // Now, check to make sure the form starts by autoincrementing correctly.
    $this->drupalGet($url);
    $this->assertFieldByName('machine_name', 'displaymessage_2', 'Block form appends _2 to plugin-suggested machine name when an instance already exists.');
    $this->drupalPostForm($url, array(), 'Save block');

    // And verify that it continues working beyond just the first two.
    $this->drupalGet($url);
    $this->assertFieldByName('machine_name', 'displaymessage_3', 'Block form appends _3 to plugin-suggested machine name when two instances already exist.');
  }

}
