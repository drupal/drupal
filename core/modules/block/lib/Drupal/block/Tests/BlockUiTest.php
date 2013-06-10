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
  public static $modules = array('block');

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
        'plugin_id' => 'system_menu_block:menu-tools',
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
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
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
   * Test block search.
   */
  function testBlockSearch() {
    $block = t('Administration');
    $blocks = drupal_json_decode($this->drupalGet('system/autocomplete/block_plugin_ui:stark', array('query' => array('q' => $block))));
    $this->assertEqual($blocks['system_menu_block:menu-admin'], $block, t('Can search for block with name !block.', array('!block' => $block)));
  }
}
