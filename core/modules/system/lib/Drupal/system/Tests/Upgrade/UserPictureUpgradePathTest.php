<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\UserPictureUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

use Drupal\Core\Language\Language;

/**
 * Tests upgrading a filled database with user picture data.
 *
 * Loads a filled installation of Drupal 7 with user picture data and runs the
 * upgrade process on it.
 */
class UserPictureUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name'  => 'User picture upgrade test',
      'description'  => 'Upgrade tests with user picture data.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $path = drupal_get_path('module', 'system') . '/tests/upgrade';
    $this->databaseDumpFiles = array(
      $path . '/drupal-7.bare.standard_all.database.php.gz',
      $path . '/drupal-7.user_picture.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests expected user picture conversions after a successful upgrade.
   */
  public function testUserPictureUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Retrieve the field instance and check for migrated settings.
    $instance = field_info_instance('user', 'user_picture', 'user');
    $file = entity_load('file', $instance['settings']['default_image'][0]);
    $this->assertIdentical($instance['settings']['default_image'][0], $file->id(), 'Default user picture has been migrated.');
    $this->assertEqual($file->getFileUri(), 'public://user_pictures_dir/druplicon.png', 'File id matches the uri expected.');
    $this->assertEqual($file->getFilename(), 'druplicon.png');
    $this->assertEqual($file->langcode->value, Language::LANGCODE_NOT_SPECIFIED);
    $this->assertEqual($file->getMimeType(), 'image/png');
    $this->assertFalse(empty($file->uuid->value));

    // Check file usage for the default image.
    $usage = file_usage()->listUsage($file);
    $field = field_info_field('user', 'user_picture');
    $this->assertTrue(isset($usage['image']['default_image'][$field['uuid']]));

    $this->assertEqual($instance['settings']['max_resolution'], '800x800', 'User picture maximum resolution has been migrated.');
    $this->assertEqual($instance['settings']['max_filesize'], '700 KB', 'User picture maximum filesize has been migrated.');
    $this->assertEqual($instance['description'], 'These are user picture guidelines.', 'User picture guidelines are now the user picture field description.');
    $this->assertEqual($instance['settings']['file_directory'], 'user_pictures_dir', 'User picture directory path has been migrated.');

    $display_options = entity_get_display('user', 'user', 'default')->getComponent('user_picture');
    $this->assertEqual($display_options['settings']['image_style'], 'thumbnail', 'User picture image style setting has been migrated.');

    // Verify compact view mode default settings.
    $this->drupalGet('admin/config/people/accounts/display/compact');
    $this->assertFieldByName('fields[member_for][type]', 'hidden');

    // Check the user picture and file usage record.
    $user = user_load(1);
    $file = $user->user_picture->entity;
    $this->assertEqual('public://user_pictures_dir/faked_image.png', $file->getFileUri());
    $usage = file_usage()->listUsage($file);
    $this->assertEqual(1, $usage['file']['user'][1]);
  }

}
