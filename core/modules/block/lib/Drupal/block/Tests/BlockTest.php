<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides testing for basic block module functionality.
 */
class BlockTest extends BlockTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Block functionality',
      'description' => 'Tests basic block functionality.',
      'group' => 'Block',
    );
  }

  /**
   * Tests block visibility.
   */
  function testBlockVisibility() {
    $block_name = 'system_powered_by_block';
    // Create a random title for the block.
    $title = $this->randomName(8);
    // Enable a standard block.
    $default_theme = \Drupal::config('system.theme')->get('default');
    $edit = array(
      'id' => strtolower($this->randomName(8)),
      'region' => 'sidebar_first',
      'settings[label]' => $title,
    );
    // Set the block to be hidden on any user path, and to be shown only to
    // authenticated users.
    $edit['visibility[path][pages]'] = 'user*';
    $edit['visibility[role][roles][' . DRUPAL_AUTHENTICATED_RID . ']'] = TRUE;
    $this->drupalPostForm('admin/structure/block/add/' . $block_name . '/' . $default_theme, $edit, t('Save block'));
    $this->assertText('The block configuration has been saved.', 'Block was saved');

    $this->drupalGet('');
    $this->assertText($title, 'Block was displayed on the front page.');

    $this->drupalGet('user');
    $this->assertNoText($title, 'Block was not displayed according to block visibility rules.');

    $this->drupalGet('USER/' . $this->adminUser->id());
    $this->assertNoText($title, 'Block was not displayed according to block visibility rules regardless of path case.');

    // Confirm that the block is not displayed to anonymous users.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoText($title, 'Block was not displayed to anonymous users.');

    // Confirm that an empty block is not displayed.
    $this->assertNoText('Powered by Drupal', 'Empty block not displayed.');
    $this->assertNoRaw('sidebar-first', 'Empty sidebar-first region is not displayed.');
  }

  /**
   * Test block visibility when using "pages" restriction but leaving
   * "pages" textarea empty
   */
  function testBlockVisibilityListedEmpty() {
    $block_name = 'system_powered_by_block';
    // Create a random title for the block.
    $title = $this->randomName(8);
    // Enable a standard block.
    $default_theme = \Drupal::config('system.theme')->get('default');
    $edit = array(
      'id' => strtolower($this->randomName(8)),
      'region' => 'sidebar_first',
      'settings[label]' => $title,
      'visibility[path][visibility]' => BLOCK_VISIBILITY_LISTED,
    );
    // Set the block to be hidden on any user path, and to be shown only to
    // authenticated users.
    $this->drupalPostForm('admin/structure/block/add/' . $block_name . '/' . $default_theme, $edit, t('Save block'));
    $this->assertText('The block configuration has been saved.', 'Block was saved');

    $this->drupalGet('user');
    $this->assertNoText($title, 'Block was not displayed according to block visibility rules.');

    $this->drupalGet('USER');
    $this->assertNoText($title, 'Block was not displayed according to block visibility rules regardless of path case.');

    // Confirm that the block is not displayed to anonymous users.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoText($title, 'Block was not displayed to anonymous users on the front page.');
  }

  /**
   * Test configuring and moving a module-define block to specific regions.
   */
  function testBlock() {
    // Select the 'Powered by Drupal' block to be configured and moved.
    $block = array();
    $block['id'] = 'system_powered_by_block';
    $block['settings[label]'] = $this->randomName(8);
    $block['theme'] = \Drupal::config('system.theme')->get('default');
    $block['region'] = 'header';

    // Set block title to confirm that interface works and override any custom titles.
    $this->drupalPostForm('admin/structure/block/add/' . $block['id'] . '/' . $block['theme'], array('settings[label]' => $block['settings[label]'], 'id' => $block['id'], 'region' => $block['region']), t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), 'Block title set.');
    // Check to see if the block was created by checking its configuration.
    $instance = entity_load('block', $block['id']);

    $this->assertEqual($instance->label(), $block['settings[label]'], 'Stored block title found.');

    // Check whether the block can be moved to all available regions.
    foreach ($this->regions as $region) {
      $this->moveBlockToRegion($block, $region);
    }

    // Set the block to the disabled region.
    $edit = array();
    $edit['blocks[' . $block['id'] . '][region]'] = -1;
    $this->drupalPostForm('admin/structure/block', $edit, t('Save blocks'));

    // Confirm that the block is now listed as disabled.
    $this->assertText(t('The block settings have been updated.'), 'Block successfully move to disabled region.');

    // Confirm that the block instance title and markup are not displayed.
    $this->drupalGet('node');
    $this->assertNoText(t($block['settings[label]']));
    // Check for <div id="block-my-block-instance-name"> if the machine name
    // is my_block_instance_name.
    $xpath = $this->buildXPathQuery('//div[@id=:id]/*', array(':id' => 'block-' . str_replace('_', '-', strtolower($block['id']))));
    $this->assertNoFieldByXPath($xpath, FALSE, 'Block found in no regions.');

    // Test deleting the block from the edit form.
    $this->drupalGet('admin/structure/block/manage/' . $block['id']);
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the block %name?', array('%name' => $block['settings[label]'])));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertRaw(t('The block %name has been removed.', array('%name' => $block['settings[label]'])));

    // Test deleting a block via "Configure block" link.
    $block = $this->drupalPlaceBlock('system_powered_by_block');
    $this->drupalGet('admin/structure/block/manage/' . $block->id(), array('query' => array('destination' => 'admin')));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the block %name?', array('%name' => $block->label())));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertRaw(t('The block %name has been removed.', array('%name' => $block->label())));
    $this->assertUrl('admin');
    $this->assertNoRaw($block->id());
  }

  /**
   * Tests that the block form has a theme selector when not passed via the URL.
   */
  public function testBlockThemeSelector() {
    // Enable all themes.
    theme_enable(array('bartik', 'seven'));
    $theme_settings = $this->container->get('config.factory')->get('system.theme');
    foreach (array('bartik', 'stark', 'seven') as $theme) {
      $this->drupalGet('admin/structure/block/list/' . $theme);
      $this->assertTitle(t('Block layout') . ' | Drupal');
      // Select the 'Powered by Drupal' block to be placed.
      $block = array();
      $block['id'] = strtolower($this->randomName());
      $block['theme'] = $theme;
      $block['region'] = 'content';
      $this->drupalPostForm('admin/structure/block/add/system_powered_by_block', $block, t('Save block'));
      $this->assertText(t('The block configuration has been saved.'));
      $this->assertUrl('admin/structure/block/list/' . $theme . '?block-placement=' . drupal_html_class($block['id']));

      // Set the default theme and ensure the block is placed.
      $theme_settings->set('default', $theme)->save();
      $this->drupalGet('');
      $elements = $this->xpath('//div[@id = :id]', array(':id' => drupal_html_id('block-' . $block['id'])));
      $this->assertTrue(!empty($elements), 'The block was found.');
    }
  }

  /**
   * Test block title display settings.
   */
  function testHideBlockTitle() {
    $block_name = 'system_powered_by_block';
    // Create a random title for the block.
    $title = $this->randomName(8);
    $id = strtolower($this->randomName(8));
    // Enable a standard block.
    $default_theme = \Drupal::config('system.theme')->get('default');
    $edit = array(
      'id' => $id,
      'region' => 'sidebar_first',
      'settings[label]' => $title,
    );
    $this->drupalPostForm('admin/structure/block/add/' . $block_name . '/' . $default_theme, $edit, t('Save block'));
    $this->assertText('The block configuration has been saved.', 'Block was saved');

    $this->drupalGet('user');
    $this->assertText($title, 'Block title was displayed by default.');

    $edit = array(
      'settings[label_display]' => FALSE,
    );
    $this->drupalPostForm('admin/structure/block/manage/' . $id, $edit, t('Save block'));
    $this->assertText('The block configuration has been saved.', 'Block was saved');

    $this->drupalGet('admin/structure/block/manage/' . $id);
    $this->assertNoFieldChecked('edit-settings-label-display', 'The display_block option has the correct default value on the configuration form.');

    $this->drupalGet('user');
    $this->assertNoText($title, 'Block title was not displayed when hidden.');
  }

  /**
   * Moves a block to a given region via the UI and confirms the result.
   *
   * @param array $block
   *   An array of information about the block, including the following keys:
   *   - module: The module providing the block.
   *   - title: The title of the block.
   *   - delta: The block's delta key.
   * @param string $region
   *   The machine name of the theme region to move the block to, for example
   *   'header' or 'sidebar_first'.
   */
  function moveBlockToRegion(array $block, $region) {
    // Set the created block to a specific region.
    $block += array('theme' => \Drupal::config('system.theme')->get('default'));
    $edit = array();
    $edit['blocks[' . $block['id'] . '][region]'] = $region;
    $this->drupalPostForm('admin/structure/block', $edit, t('Save blocks'));

    // Confirm that the block was moved to the proper region.
    $this->assertText(t('The block settings have been updated.'), format_string('Block successfully moved to %region_name region.', array( '%region_name' => $region)));

    // Confirm that the block is being displayed.
    $this->drupalGet('');
    $this->assertText(t($block['settings[label]']), 'Block successfully being displayed on the page.');

    // Confirm that the custom block was found at the proper region.
    $xpath = $this->buildXPathQuery('//div[@class=:region-class]//div[@id=:block-id]/*', array(
      ':region-class' => 'region region-' . drupal_html_class($region),
      ':block-id' => 'block-' . str_replace('_', '-', strtolower($block['id'])),
    ));
    $this->assertFieldByXPath($xpath, NULL, t('Block found in %region_name region.', array('%region_name' => drupal_html_class($region))));
  }

  /**
   * Test _block_rehash().
   */
  function testBlockRehash() {
    \Drupal::moduleHandler()->install(array('block_test'));
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('block_test'), 'Test block module enabled.');

    // Clear the block cache to load the block_test module's block definitions.
    $this->container->get('plugin.manager.block')->clearCachedDefinitions();

    // Add a test block.
    $block = array();
    $block['id'] = 'test_cache';
    $block['theme'] = \Drupal::config('system.theme')->get('default');
    $block['region'] = 'header';
    $block = $this->drupalPlaceBlock('test_cache', array('region' => 'header'));

    // Our test block's caching should default to DRUPAL_CACHE_PER_ROLE.
    $settings = $block->get('settings');
    $this->assertEqual($settings['cache'], DRUPAL_CACHE_PER_ROLE, 'Test block cache mode defaults to DRUPAL_CACHE_PER_ROLE.');

    // Disable caching for this block.
    $block->getPlugin()->setConfigurationValue('cache', DRUPAL_NO_CACHE);
    $block->save();
    // Flushing all caches should call _block_rehash().
    $this->resetAll();
    // Verify that block is updated with the new caching mode.
    $block = entity_load('block', $block->id());
    $settings = $block->get('settings');
    $this->assertEqual($settings['cache'], DRUPAL_NO_CACHE, "Test block's database entry updated to DRUPAL_NO_CACHE.");
  }

}
