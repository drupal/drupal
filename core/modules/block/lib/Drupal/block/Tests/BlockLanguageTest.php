<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockLanguageTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for the language list configuration forms.
 */
class BlockLanguageTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Language block visibility',
      'description' => 'Tests if a block can be configure to be only visibile on a particular language.',
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp('language', 'block');
  }

  /**
   * Tests the visibility settings for the blocks based on language.
   */
  public function testLanguageBlockVisibility() {
    // Create a new user, allow him to manage the blocks and the languages.
    $admin_user = $this->drupalCreateUser(array(
      'administer languages', 'administer blocks',
    ));
    $this->drupalLogin($admin_user);

    // Add predefined language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));
    $this->assertText('French', t('Language added successfully.'));

    // Check if the visibility setting is available.
    $this->drupalGet('admin/structure/block/add');
    $this->assertField('langcodes[en]', t('Language visibility field is visible.'));

    // Create a new block.
    $info_name = $this->randomString(10);
    $body = '';
    for ($i = 0; $i <= 100; $i++) {
      $body .= chr(rand(97, 122));
    }
    $edit = array(
      'regions[stark]' => 'sidebar_first',
      'info' => $info_name,
      'title' => 'test',
      'body[value]' => $body,
    );
    $this->drupalPost('admin/structure/block/add', $edit, t('Save block'));

    // Set visibility setting for one language.
    $edit = array(
      'langcodes[en]' => TRUE,
    );
    $this->drupalPost('admin/structure/block/manage/block/1/configure', $edit, t('Save block'));

    // Change the default language.
    $edit = array(
      'site_default' => 'fr',
    );
    $this->drupalPost('admin/config/regional/language', $edit, t('Save configuration'));

    // Reset the static cache of the language list.
    drupal_static_reset('language_list');

    // Check that a page has a block
    $this->drupalGet('', array('language' => language_load('en')));
    $this->assertText($body, t('The body of the custom block appears on the page.'));

    // Check that a page doesn't has a block for the current language anymore
    $this->drupalGet('', array('language' => language_load('fr')));
    $this->assertNoText($body, t('The body of the custom block does not appear on the page.'));
  }

  /**
   * Tests if the visibility settings are removed if the language is deleted.
   */
  public function testLanguageBlockVisibilityLanguageDelete() {
    // Create a new user, allow him to manage the blocks and the languages.
    $admin_user = $this->drupalCreateUser(array(
      'administer languages', 'administer blocks',
    ));
    $this->drupalLogin($admin_user);

    // Add predefined language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));
    $this->assertText('French', t('Language added successfully.'));

    // Create a new block.
    $info_name = $this->randomString(10);
    $body = '';
    for ($i = 0; $i <= 100; $i++) {
      $body .= chr(rand(97, 122));
    }
    $edit = array(
      'regions[stark]' => 'sidebar_first',
      'info' => $info_name,
      'title' => 'test',
      'body[value]' => $body,
    );
    $this->drupalPost('admin/structure/block/add', $edit, t('Save block'));

    // Set visibility setting for one language.
    $edit = array(
      'langcodes[fr]' => TRUE,
    );
    $this->drupalPost('admin/structure/block/manage/block/1/configure', $edit, t('Save block'));

    // Check that we have an entry in the database after saving the setting.
    $count = db_query('SELECT COUNT(langcode) FROM {block_language} WHERE module = :module AND delta = :delta', array(
      ':module' => 'block',
      ':delta' => '1'
    ))->fetchField();
    $this->assertTrue($count == 1, t('The block language visibility has an entry in the database.'));

    // Delete the language.
    $this->drupalPost('admin/config/regional/language/delete/fr', array(), t('Delete'));

    // Check that the setting related to this language has been deleted.
    $count = db_query('SELECT COUNT(langcode) FROM {block_language} WHERE module = :module AND delta = :delta', array(
      ':module' => 'block',
      ':delta' => '1'
    ))->fetchField();
    $this->assertTrue($count == 0, t('The block language visibility do not have an entry in the database.'));
  }

  /**
   * Tests if the visibility settings are removed if the block is deleted.
   */
  public function testLanguageBlockVisibilityBlockDelete() {
    // Create a new user, allow him to manage the blocks and the languages.
    $admin_user = $this->drupalCreateUser(array(
      'administer languages', 'administer blocks',
    ));
    $this->drupalLogin($admin_user);

    // Add predefined language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));
    $this->assertText('French', t('Language added successfully.'));

    // Create a new block.
    $info_name = $this->randomString(10);
    $body = '';
    for ($i = 0; $i <= 100; $i++) {
      $body .= chr(rand(97, 122));
    }
    $edit = array(
      'regions[stark]' => 'sidebar_first',
      'info' => $info_name,
      'title' => 'test',
      'body[value]' => $body,
    );
    $this->drupalPost('admin/structure/block/add', $edit, t('Save block'));

    // Set visibility setting for one language.
    $edit = array(
      'langcodes[fr]' => TRUE,
    );
    $this->drupalPost('admin/structure/block/manage/block/1/configure', $edit, t('Save block'));

    // Check that we have an entry in the database after saving the setting.
    $count = db_query('SELECT COUNT(langcode) FROM {block_language} WHERE module = :module AND delta = :delta', array(
      ':module' => 'block',
      ':delta' => '1'
    ))->fetchField();
    $this->assertTrue($count == 1, t('The block language visibility has an entry in the database.'));

    // Delete the custom block.
    $this->drupalPost('admin/structure/block/manage/block/1/delete', array(), t('Delete'));

    // Check that the setting related to this block has been deleted.
    $count = db_query('SELECT COUNT(langcode) FROM {block_language} WHERE module = :module AND delta = :delta', array(
      ':module' => 'block',
      ':delta' => '1'
    ))->fetchField();
    $this->assertTrue($count == 0, t('The block language visibility do not have an entry in the database.'));
  }
}
