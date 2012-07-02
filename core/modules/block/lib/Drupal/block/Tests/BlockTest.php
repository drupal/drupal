<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

class BlockTest extends WebTestBase {
  protected $regions;
  protected $admin_user;

  public static function getInfo() {
    return array(
      'name' => 'Block functionality',
      'description' => 'Add, edit and delete custom block. Configure and move a module-defined block.',
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp(array('block'));

    // Create Full HTML text format.
    $full_html_format = array(
      'format' => 'full_html',
      'name' => 'Full HTML',
    );
    $full_html_format = (object) $full_html_format;
    filter_format_save($full_html_format);
    $this->checkPermissions(array(), TRUE);

    // Create and log in an administrative user having access to the Full HTML
    // text format.
    $this->admin_user = $this->drupalCreateUser(array(
      'administer blocks',
      filter_permission_name($full_html_format),
      'access administration pages',
    ));
    $this->drupalLogin($this->admin_user);

    // Define the existing regions
    $this->regions = array();
    $this->regions[] = 'header';
    $this->regions[] = 'sidebar_first';
    $this->regions[] = 'content';
    $this->regions[] = 'sidebar_second';
    $this->regions[] = 'footer';
  }

  /**
   * Test creating custom block, moving it to a specific region and then deleting it.
   */
  function testCustomBlock() {
    // Enable a second theme.
    theme_enable(array('seven'));

    // Confirm that the add block link appears on block overview pages.
    $this->drupalGet('admin/structure/block');
    $this->assertRaw(l('Add block', 'admin/structure/block/add'), t('Add block link is present on block overview page for default theme.'));
    $this->drupalGet('admin/structure/block/list/seven');
    $this->assertRaw(l('Add block', 'admin/structure/block/list/seven/add'), t('Add block link is present on block overview page for non-default theme.'));

    // Confirm that hidden regions are not shown as options for block placement
    // when adding a new block.
    theme_enable(array('bartik'));
    $themes = list_themes();
    $this->drupalGet('admin/structure/block/add');
    foreach ($themes as $key => $theme) {
      if ($theme->status) {
        foreach ($theme->info['regions_hidden'] as $hidden_region) {
          $elements = $this->xpath('//select[@id=:id]//option[@value=:value]', array(':id' => 'edit-regions-' . $key, ':value' => $hidden_region));
          $this->assertFalse(isset($elements[0]), t('The hidden region @region is not available for @theme.', array('@region' => $hidden_region, '@theme' => $key)));
        }
      }
    }

    // Add a new custom block by filling out the input form on the admin/structure/block/add page.
    $custom_block = array();
    $custom_block['info'] = $this->randomName(8);
    $custom_block['title'] = $this->randomName(8);
    $custom_block['body[value]'] = $this->randomName(32);
    $this->drupalPost('admin/structure/block/add', $custom_block, t('Save block'));

    // Confirm that the custom block has been created, and then query the created bid.
    $this->assertText(t('The block has been created.'), t('Custom block successfully created.'));
    $bid = db_query("SELECT bid FROM {block_custom} WHERE info = :info", array(':info' => $custom_block['info']))->fetchField();

    // Check to see if the custom block was created by checking that it's in the database.
    $this->assertNotNull($bid, t('Custom block found in database'));

    // Check that block_block_view() returns the correct title and content.
    $data = block_block_view($bid);
    $format = db_query("SELECT format FROM {block_custom} WHERE bid = :bid", array(':bid' => $bid))->fetchField();
    $this->assertTrue(array_key_exists('subject', $data) && empty($data['subject']), t('block_block_view() provides an empty block subject, since custom blocks do not have default titles.'));
    $this->assertEqual(check_markup($custom_block['body[value]'], $format), $data['content'], t('block_block_view() provides correct block content.'));

    // Check whether the block can be moved to all available regions.
    $custom_block['module'] = 'block';
    $custom_block['delta'] = $bid;
    foreach ($this->regions as $region) {
      $this->moveBlockToRegion($custom_block, $region);
    }

    // Verify presence of configure and delete links for custom block.
    $this->drupalGet('admin/structure/block');
    $this->assertLinkByHref('admin/structure/block/manage/block/' . $bid . '/configure', 0, t('Custom block configure link found.'));
    $this->assertLinkByHref('admin/structure/block/manage/block/' . $bid . '/delete', 0, t('Custom block delete link found.'));

    // Set visibility only for authenticated users, to verify delete functionality.
    $edit = array();
    $edit['roles[' . DRUPAL_AUTHENTICATED_RID . ']'] = TRUE;
    $this->drupalPost('admin/structure/block/manage/block/' . $bid . '/configure', $edit, t('Save block'));

    // Delete the created custom block & verify that it's been deleted and no longer appearing on the page.
    $this->clickLink(t('delete'));
    $this->drupalPost('admin/structure/block/manage/block/' . $bid . '/delete', array(), t('Delete'));
    $this->assertRaw(t('The block %title has been removed.', array('%title' => $custom_block['info'])), t('Custom block successfully deleted.'));
    $this->assertNoText(t($custom_block['title']), t('Custom block no longer appears on page.'));
    $count = db_query("SELECT 1 FROM {block_role} WHERE module = :module AND delta = :delta", array(':module' => $custom_block['module'], ':delta' => $custom_block['delta']))->fetchField();
    $this->assertFalse($count, t('Table block_role being cleaned.'));
  }

  /**
   * Test creating custom block using Full HTML.
   */
  function testCustomBlockFormat() {
    // Add a new custom block by filling out the input form on the admin/structure/block/add page.
    $custom_block = array();
    $custom_block['info'] = $this->randomName(8);
    $custom_block['title'] = $this->randomName(8);
    $custom_block['body[value]'] = '<h1>Full HTML</h1>';
    $full_html_format = filter_format_load('full_html');
    $custom_block['body[format]'] = $full_html_format->format;
    $this->drupalPost('admin/structure/block/add', $custom_block, t('Save block'));

    // Set the created custom block to a specific region.
    $bid = db_query("SELECT bid FROM {block_custom} WHERE info = :info", array(':info' => $custom_block['info']))->fetchField();
    $edit = array();
    $edit['blocks[block_' . $bid . '][region]'] = $this->regions[1];
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Confirm that the custom block is being displayed using configured text format.
    $this->drupalGet('node');
    $this->assertRaw('<h1>Full HTML</h1>', t('Custom block successfully being displayed using Full HTML.'));

    // Confirm that a user without access to Full HTML can not see the body field,
    // but can still submit the form without errors.
    $block_admin = $this->drupalCreateUser(array('administer blocks'));
    $this->drupalLogin($block_admin);
    $this->drupalGet('admin/structure/block/manage/block/' . $bid . '/configure');
    $this->assertFieldByXPath("//textarea[@name='body[value]' and @disabled='disabled']", t('This field has been disabled because you do not have sufficient permissions to edit it.'), t('Body field contains denied message'));
    $this->drupalPost('admin/structure/block/manage/block/' . $bid . '/configure', array(), t('Save block'));
    $this->assertNoText(t('Ensure that each block description is unique.'));

    // Confirm that the custom block is still being displayed using configured text format.
    $this->drupalGet('node');
    $this->assertRaw('<h1>Full HTML</h1>', t('Custom block successfully being displayed using Full HTML.'));
  }

  /**
   * Test block visibility.
   */
  function testBlockVisibility() {
    // Enable Node module and change the front page path to 'node'.
    module_enable(array('node'));
    config('system.site')->set('page.front', 'node')->save();

    $block = array();

    // Create a random title for the block
    $title = $this->randomName(8);

    // Create the custom block
    $custom_block = array();
    $custom_block['info'] = $this->randomName(8);
    $custom_block['title'] = $title;
    $custom_block['body[value]'] = $this->randomName(32);
    $this->drupalPost('admin/structure/block/add', $custom_block, t('Save block'));

    $bid = db_query("SELECT bid FROM {block_custom} WHERE info = :info", array(':info' => $custom_block['info']))->fetchField();
    $block['module'] = 'block';
    $block['delta'] = $bid;
    $block['title'] = $title;

    // Set the block to be hidden on any user path, and to be shown only to
    // authenticated users.
    $edit = array();
    $edit['pages'] = 'user*';
    $edit['roles[' . DRUPAL_AUTHENTICATED_RID . ']'] = TRUE;
    $this->drupalPost('admin/structure/block/manage/' . $block['module'] . '/' . $block['delta'] . '/configure', $edit, t('Save block'));

    // Move block to the first sidebar.
    $this->moveBlockToRegion($block, $this->regions[1]);

    $this->drupalGet('');
    $this->assertText($title, t('Block was displayed on the front page.'));

    $this->drupalGet('user');
    $this->assertNoText($title, t('Block was not displayed according to block visibility rules.'));

    $this->drupalGet('USER/' . $this->admin_user->uid);
    $this->assertNoText($title, t('Block was not displayed according to block visibility rules regardless of path case.'));

    // Confirm that the block is not displayed to anonymous users.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoText($title, t('Block was not displayed to anonymous users.'));

    // Confirm that an empty block is not displayed.
    $this->assertNoRaw('block-system-help', t('Empty block not displayed.'));
  }

  /**
   * Test block visibility when using "pages" restriction but leaving
   * "pages" textarea empty
   */
  function testBlockVisibilityListedEmpty() {
    $block = array();

    // Create a random title for the block
    $title = $this->randomName(8);

    // Create the custom block
    $custom_block = array();
    $custom_block['info'] = $this->randomName(8);
    $custom_block['title'] = $title;
    $custom_block['body[value]'] = $this->randomName(32);
    $this->drupalPost('admin/structure/block/add', $custom_block, t('Save block'));

    $bid = db_query("SELECT bid FROM {block_custom} WHERE info = :info", array(':info' => $custom_block['info']))->fetchField();
    $block['module'] = 'block';
    $block['delta'] = $bid;
    $block['title'] = $title;

    // Move block to the first sidebar.
    $this->moveBlockToRegion($block, $this->regions[1]);

    // Set the block to be hidden on any user path, and to be shown only to
    // authenticated users.
    $edit = array();
    $edit['visibility'] = BLOCK_VISIBILITY_LISTED;
    $this->drupalPost('admin/structure/block/manage/' . $block['module'] . '/' . $block['delta'] . '/configure', $edit, t('Save block'));

    $this->drupalGet('');
    $this->assertNoText($title, t('Block was not displayed according to block visibility rules.'));

    $this->drupalGet('user');
    $this->assertNoText($title, t('Block was not displayed according to block visibility rules regardless of path case.'));

    // Confirm that the block is not displayed to anonymous users.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoText($title, t('Block was not displayed to anonymous users.'));
  }

  /**
   * Test user customization of block visibility.
   */
  function testBlockVisibilityPerUser() {
    $block = array();

    // Create a random title for the block.
    $title = $this->randomName(8);

    // Create our custom test block.
    $custom_block = array();
    $custom_block['info'] = $this->randomName(8);
    $custom_block['title'] = $title;
    $custom_block['body[value]'] = $this->randomName(32);
    $this->drupalPost('admin/structure/block/add', $custom_block, t('Save block'));

    $bid = db_query("SELECT bid FROM {block_custom} WHERE info = :info", array(':info' => $custom_block['info']))->fetchField();
    $block['module'] = 'block';
    $block['delta'] = $bid;
    $block['title'] = $title;

    // Move block to the first sidebar.
    $this->moveBlockToRegion($block, $this->regions[1]);

    // Set the block to be customizable per user, visible by default.
    $edit = array();
    $edit['custom'] = BLOCK_CUSTOM_ENABLED;
    $this->drupalPost('admin/structure/block/manage/' . $block['module'] . '/' . $block['delta'] . '/configure', $edit, t('Save block'));

    // Disable block visibility for the admin user.
    $edit = array();
    $edit['block[' . $block['module'] . '][' . $block['delta'] . ']'] = FALSE;
    $this->drupalPost('user/' . $this->admin_user->uid . '/edit', $edit, t('Save'));

    $this->drupalGet('');
    $this->assertNoText($block['title'], t('Block was not displayed according to per user block visibility setting.'));

    // Set the block to be customizable per user, hidden by default.
    $edit = array();
    $edit['custom'] = BLOCK_CUSTOM_DISABLED;
    $this->drupalPost('admin/structure/block/manage/' . $block['module'] . '/' . $block['delta'] . '/configure', $edit, t('Save block'));

    // Enable block visibility for the admin user.
    $edit = array();
    $edit['block[' . $block['module'] . '][' . $block['delta'] . ']'] = TRUE;
    $this->drupalPost('user/' . $this->admin_user->uid . '/edit', $edit, t('Save'));

    $this->drupalGet('');
    $this->assertText($block['title'], t('Block was displayed according to per user block visibility setting.'));
  }

  /**
   * Test configuring and moving a module-define block to specific regions.
   */
  function testBlock() {
    // Select the Navigation block to be configured and moved.
    $block = array();
    $block['module'] = 'system';
    $block['delta'] = 'management';
    $block['title'] = $this->randomName(8);

    // Set block title to confirm that interface works and override any custom titles.
    $this->drupalPost('admin/structure/block/manage/' . $block['module'] . '/' . $block['delta'] . '/configure', array('title' => $block['title']), t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), t('Block title set.'));
    $bid = db_query("SELECT bid FROM {block} WHERE module = :module AND delta = :delta", array(
      ':module' => $block['module'],
      ':delta' => $block['delta'],
    ))->fetchField();

    // Check to see if the block was created by checking that it's in the database.
    $this->assertNotNull($bid, t('Block found in database'));

    // Check whether the block can be moved to all available regions.
    foreach ($this->regions as $region) {
      $this->moveBlockToRegion($block, $region);
    }

    // Set the block to the disabled region.
    $edit = array();
    $edit['blocks[' . $block['module'] . '_' . $block['delta'] . '][region]'] = '-1';
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Confirm that the block was moved to the proper region.
    $this->assertText(t('The block settings have been updated.'), t('Block successfully move to disabled region.'));
    $this->assertNoText(t($block['title']), t('Block no longer appears on page.'));

    // Confirm that the region's xpath is not available.
    $xpath = $this->buildXPathQuery('//div[@id=:id]/*', array(':id' => 'block-block-' . $bid));
    $this->assertNoFieldByXPath($xpath, FALSE, t('Custom block found in no regions.'));

    // For convenience of developers, put the navigation block back.
    $edit = array();
    $edit['blocks[' . $block['module'] . '_' . $block['delta'] . '][region]'] = $this->regions[1];
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
    $this->assertText(t('The block settings have been updated.'), t('Block successfully move to first sidebar region.'));

    $this->drupalPost('admin/structure/block/manage/' . $block['module'] . '/' . $block['delta'] . '/configure', array('title' => 'Navigation'), t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), t('Block title set.'));
  }

  function moveBlockToRegion($block, $region) {
    // Set the created block to a specific region.
    $edit = array();
    $edit['blocks[' . $block['module'] . '_' . $block['delta'] . '][region]'] = $region;
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Confirm that the block was moved to the proper region.
    $this->assertText(t('The block settings have been updated.'), t('Block successfully moved to %region_name region.', array( '%region_name' => $region)));

    // Confirm that the block is being displayed.
    $this->drupalGet('node');
    $this->assertText(t($block['title']), t('Block successfully being displayed on the page.'));

    // Confirm that the custom block was found at the proper region.
    $xpath = $this->buildXPathQuery('//div[@class=:region-class]//div[@id=:block-id]/*', array(
      ':region-class' => 'region region-' . str_replace('_', '-', $region),
      ':block-id' => 'block-' . $block['module'] . '-' . $block['delta'],
    ));
    $this->assertFieldByXPath($xpath, NULL, t('Custom block found in %region_name region.', array('%region_name' => $region)));
  }

  /**
   * Test _block_rehash().
   */
  function testBlockRehash() {
    module_enable(array('block_test'));
    $this->assertTrue(module_exists('block_test'), t('Test block module enabled.'));

    // Our new block should be inserted in the database when we visit the
    // block management page.
    $this->drupalGet('admin/structure/block');
    // Our test block's caching should default to DRUPAL_CACHE_PER_ROLE.
    $current_caching = db_query("SELECT cache FROM {block} WHERE module = 'block_test' AND delta = 'test_cache'")->fetchField();
    $this->assertEqual($current_caching, DRUPAL_CACHE_PER_ROLE, t('Test block cache mode defaults to DRUPAL_CACHE_PER_ROLE.'));

    // Disable caching for this block.
    variable_set('block_test_caching', DRUPAL_NO_CACHE);
    // Flushing all caches should call _block_rehash().
    $this->resetAll();
    // Verify that the database is updated with the new caching mode.
    $current_caching = db_query("SELECT cache FROM {block} WHERE module = 'block_test' AND delta = 'test_cache'")->fetchField();
    $this->assertEqual($current_caching, DRUPAL_NO_CACHE, t("Test block's database entry updated to DRUPAL_NO_CACHE."));
  }
}
