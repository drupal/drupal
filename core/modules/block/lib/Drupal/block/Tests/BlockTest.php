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
      'machine_name' => strtolower($this->randomName(8)),
      'region' => 'sidebar_first',
      'settings[label]' => $title,
    );
    // Set the block to be hidden on any user path, and to be shown only to
    // authenticated users.
    $edit['visibility[path][pages]'] = 'user*';
    $edit['visibility[role][roles][' . DRUPAL_AUTHENTICATED_RID . ']'] = TRUE;
    $this->drupalPost('admin/structure/block/add/' . $block_name . '/' . $default_theme, $edit, t('Save block'));
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
      'machine_name' => strtolower($this->randomName(8)),
      'region' => 'sidebar_first',
      'settings[label]' => $title,
      'visibility[path][visibility]' => BLOCK_VISIBILITY_LISTED,
    );
    // Set the block to be hidden on any user path, and to be shown only to
    // authenticated users.
    $this->drupalPost('admin/structure/block/add/' . $block_name . '/' . $default_theme, $edit, t('Save block'));
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
    $block['machine_name'] = strtolower($this->randomName(8));
    $block['theme'] = \Drupal::config('system.theme')->get('default');
    $block['region'] = 'header';

    // Set block title to confirm that interface works and override any custom titles.
    $this->drupalPost('admin/structure/block/add/' . $block['id'] . '/' . $block['theme'], array('settings[label]' => $block['settings[label]'], 'machine_name' => $block['machine_name'], 'region' => $block['region']), t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), 'Block title set.');
    // Check to see if the block was created by checking its configuration.
    $instance = entity_load('block', $block['theme'] . '.' . $block['machine_name']);

    $this->assertEqual($instance->label(), $block['settings[label]'], 'Stored block title found.');

    // Check whether the block can be moved to all available regions.
    foreach ($this->regions as $region) {
      $this->moveBlockToRegion($block, $region);
    }

    // Set the block to the disabled region.
    $edit = array();
    $edit['blocks[' . $block['theme'] . '.' . $block['machine_name'] . '][region]'] = -1;
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Confirm that the block is now listed as disabled.
    $this->assertText(t('The block settings have been updated.'), 'Block successfully move to disabled region.');

    // Confirm that the block instance title and markup are not displayed.
    $this->drupalGet('node');
    $this->assertNoText(t($block['settings[label]']));
    // Check for <div id="block-my-block-instance-name"> if the machine name
    // is my_block_instance_name.
    $xpath = $this->buildXPathQuery('//div[@id=:id]/*', array(':id' => 'block-' . strtr(strtolower($block['machine_name']), '-', '_')));
    $this->assertNoFieldByXPath($xpath, FALSE, 'Block found in no regions.');

    // Test deleting the block from the edit form.
    $this->drupalGet('admin/structure/block/manage/' . $block['theme'] . '.' . $block['machine_name']);
    $this->drupalPost(NULL, array(), t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the block %name?', array('%name' => $block['settings[label]'])));
    $this->drupalPost(NULL, array(), t('Delete'));
    $this->assertRaw(t('The block %name has been removed.', array('%name' => $block['settings[label]'])));
  }

  /**
   * Test block title display settings.
   */
  function testHideBlockTitle() {
    $block_name = 'system_powered_by_block';
    // Create a random title for the block.
    $title = $this->randomName(8);
    $machine_name = strtolower($this->randomName(8));
    // Enable a standard block.
    $default_theme = variable_get('theme_default', 'stark');
    $edit = array(
      'machine_name' => $machine_name,
      'region' => 'sidebar_first',
      'settings[label]' => $title,
    );
    $this->drupalPost('admin/structure/block/add/' . $block_name . '/' . $default_theme, $edit, t('Save block'));
    $this->assertText('The block configuration has been saved.', 'Block was saved');

    $this->drupalGet('user');
    $this->assertText($title, 'Block title was displayed by default.');

    $edit = array(
      'settings[label_display]' => FALSE,
    );
    $this->drupalPost('admin/structure/block/manage/' . $default_theme . '.' . $machine_name, $edit, t('Save block'));
    $this->assertText('The block configuration has been saved.', 'Block was saved');

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
    $edit['blocks[' . $block['theme'] . '.' . $block['machine_name'] . '][region]'] = $region;
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Confirm that the block was moved to the proper region.
    $this->assertText(t('The block settings have been updated.'), format_string('Block successfully moved to %region_name region.', array( '%region_name' => $region)));

    // Confirm that the block is being displayed.
    $this->drupalGet('');
    $this->assertText(t($block['settings[label]']), 'Block successfully being displayed on the page.');

    // Confirm that the custom block was found at the proper region.
    $xpath = $this->buildXPathQuery('//div[@class=:region-class]//div[@id=:block-id]/*', array(
      ':region-class' => 'region region-' . drupal_html_class($region),
      ':block-id' => 'block-' . strtr(strtolower($block['machine_name']), '-', '_'),
    ));
    $this->assertFieldByXPath($xpath, NULL, t('Block found in %region_name region.', array('%region_name' => drupal_html_class($region))));
  }

  /**
   * Test _block_rehash().
   */
  function testBlockRehash() {
    module_enable(array('block_test'));
    $this->assertTrue(module_exists('block_test'), 'Test block module enabled.');

    // Clear the block cache to load the block_test module's block definitions.
    $this->container->get('plugin.manager.block')->clearCachedDefinitions();

    // Add a test block.
    $block = array();
    $block['id'] = 'test_cache';
    $block['machine_name'] = strtolower($this->randomName(8));
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

  /**
   * Tests blocks belonging to disabled modules.
   */
  function testBlockModuleDisable() {
    module_enable(array('block_test'));
    $this->assertTrue(module_exists('block_test'), 'Test block module enabled.');

    // Clear the block cache to load the block_test module's block definitions.
    $manager = $this->container->get('plugin.manager.block');
    $manager->clearCachedDefinitions();

    // Add test blocks in different regions and confirm they are displayed.
    $blocks = array();
    $regions = array('sidebar_first', 'content', 'footer');
    foreach ($regions as $region) {
      $blocks[$region] = $this->drupalPlaceBlock('test_cache', array('region' => $region));
    }
    $this->drupalGet('');
    foreach ($regions as $region) {
      $this->assertText($blocks[$region]->label());
    }

    // Disable the block test module and refresh the definitions cache.
    module_disable(array('block_test'), FALSE);
    $this->assertFalse(module_exists('block_test'), 'Test block module disabled.');
    $manager->clearCachedDefinitions();

    // Ensure that the block administration page still functions as expected.
    $this->drupalGet('admin/structure/block');
    $this->assertResponse(200);
    // A 200 response is possible with a fatal error, so check the title too.
    $this->assertTitle(t('Block layout') . ' | Drupal');

    // Ensure that the disabled module's block instance is not listed.
    foreach ($regions as $region) {
      $this->assertNoText($blocks[$region]->label());
    }

    // Ensure that the disabled module's block plugin is no longer available.
    $this->drupalGet('admin/structure/block/list/' . \Drupal::config('system.theme')->get('default'));
    $this->assertNoText(t('Test block caching'));

    // Confirm that the block is no longer displayed on the front page.
    $this->drupalGet('');
    $this->assertResponse(200);
    foreach ($regions as $region) {
      $this->assertNoText($blocks[$region]->label());
    }

    // Confirm that a different block instance can still be enabled by
    // submitting the block library form.
    // Emulate a POST submission rather than using drupalPlaceBlock() to ensure
    // that the form still functions as expected.
    $edit = array(
      'settings[label]' => $this->randomName(8),
      'machine_name' => strtolower($this->randomName(8)),
      'region' => 'sidebar_first',
    );
    $this->drupalPost('admin/structure/block/add/system_powered_by_block/stark', $edit, t('Save block'));
    $this->assertText(t('The block configuration has been saved.'));
    $this->assertText($edit['settings[label]']);

    // Update the weight of a block.
    $edit = array('blocks[stark.' . $edit['machine_name'] . '][weight]' => -1);
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
    $this->assertText(t('The block settings have been updated.'));

    // Re-enable the module and refresh the definitions cache.
    module_enable(array('block_test'), FALSE);
    $this->assertTrue(module_exists('block_test'), 'Test block module re-enabled.');
    $manager->clearCachedDefinitions();

    // Reload the admin page and confirm the block can again be configured.
    $this->drupalGet('admin/structure/block');
    foreach ($regions as $region) {
      $this->assertLinkByHref(url('admin/structure/block/manage/' . $blocks[$region]->id()));
    }

    // Confirm that the blocks are again displayed on the front page in the
    // correct regions.
    $this->drupalGet('');
    foreach ($regions as $region) {
      // @todo Use a proper method for this.
      $name_pieces = explode('.', $blocks[$region]->id());
      $machine_name = array_pop($name_pieces);
      $xpath = $this->buildXPathQuery('//div[@class=:region-class]//div[@id=:block-id]/*', array(
        ':region-class' => 'region region-' . drupal_html_class($region),
        ':block-id' => 'block-' . strtr(strtolower($machine_name), '-', '_'),
    ));
      $this->assertFieldByXPath($xpath, NULL, format_string('Block %name found in the %region region.', array(
        '%name' => $blocks[$region]->label(),
        '%region' => $region,
      )));
    }
  }

}
