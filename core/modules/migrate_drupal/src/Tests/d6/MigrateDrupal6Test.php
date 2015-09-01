<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateDrupal6Test.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate_drupal\Tests\MigrateFullDrupalTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the complete Drupal 6 migration.
 *
 * @group migrate_drupal
 */
class MigrateDrupal6Test extends MigrateFullDrupalTestBase {

  const TEST_GROUP = 'migrate_drupal_6';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'action',
    'aggregator',
    'block',
    'book',
    'comment',
    'contact',
    'block_content',
    'datetime',
    'dblog',
    'entity_reference',
    'field',
    'file',
    'filter',
    'forum',
    'image',
    'language',
    'link',
    'locale',
    'menu_link_content',
    'menu_ui',
    'migrate_drupal',
    'node',
    'options',
    'path',
    'search',
    'system',
    'simpletest',
    'statistics',
    'syslog',
    'taxonomy',
    'telephone',
    'text',
    'update',
    'user',
    'views',
  );

  /**
   * Migrations to run in the test.
   *
   * @var array
   */
  static $migrations = array(
    'd6_action_settings',
    'd6_aggregator_settings',
    'd6_aggregator_feed',
    'd6_aggregator_item',
    'd6_block',
    'd6_block_content_body_field',
    'd6_block_content_type',
    'd6_book',
    'd6_book_settings',
    'd6_comment_type',
    'd6_comment',
    'd6_comment_entity_display',
    'd6_comment_entity_form_display',
    'd6_comment_entity_form_display_subject',
    'd6_comment_field',
    'd6_comment_field_instance',
    'd6_contact_category',
    'd6_contact_settings',
    'd6_custom_block',
    'd6_date_formats',
    'd6_dblog_settings',
    'd6_field',
    'd6_field_instance',
    'd6_field_instance_widget_settings',
    'd6_field_formatter_settings',
    'd6_file_settings',
    'd6_file',
    'd6_filter_format',
    'd6_forum_settings',
    'locale_settings',
    'd6_menu_settings',
    'menu',
    'd6_menu_links',
    'd6_node_revision:*',
    'd6_node_setting_promote',
    'd6_node_setting_status',
    'd6_node_setting_sticky',
    'd6_node:*',
    'd6_node_settings',
    'd6_node_type',
    'd6_profile_values',
    'd6_search_page',
    'd6_search_settings',
    'd6_simpletest_settings',
    'd6_statistics_settings',
    'd6_syslog_settings',
    'd6_system_cron',
    'd6_system_date',
    'd6_system_file',
    'd6_system_image',
    'd6_system_image_gd',
    'd6_system_logging',
    'd6_system_maintenance',
    'd6_system_performance',
    'd6_system_rss',
    'd6_system_site',
    'd6_taxonomy_settings',
    'd6_taxonomy_term',
    'd6_taxonomy_vocabulary',
    'd6_term_node_revision:*',
    'd6_term_node:*',
    'text_settings',
    'd6_update_settings',
    'd6_upload_entity_display',
    'd6_upload_entity_form_display',
    'd6_upload_field',
    'd6_upload_field_instance',
    'd6_upload',
    'd6_url_alias',
    'd6_user_mail',
    'd6_user_contact_settings',
    'user_profile_field_instance',
    'user_profile_entity_display',
    'user_profile_entity_form_display',
    'user_profile_field',
    'user_picture_entity_display',
    'user_picture_entity_form_display',
    'user_picture_field_instance',
    'user_picture_field',
    'd6_user_picture_file',
    'd6_user_role',
    'd6_user_settings',
    'd6_user',
    'd6_view_modes',
    'd6_vocabulary_entity_display',
    'd6_vocabulary_entity_form_display',
    'd6_vocabulary_field_instance',
    'd6_vocabulary_field',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $config = $this->config('system.theme');
    $config->set('default', 'bartik');
    $config->set('admin', 'seven');
    $config->save();

    foreach (static::$modules as $module) {
      $function = $module . '_schema';
      module_load_install($module);
      if (function_exists($function)) {
        $schema = $function();
        $this->installSchema($module, array_keys($schema));
      }
    }

    $this->installEntitySchema('aggregator_feed');
    $this->installEntitySchema('aggregator_item');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('file');
    $this->installEntitySchema('node');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');

    $this->installConfig(['block_content', 'comment', 'file', 'node', 'simpletest']);

    // Install one of D8's test themes.
    \Drupal::service('theme_handler')->install(array('test_theme'));

    // Create a new user which needs to have UID 1, because that is expected by
    // the assertions from
    // \Drupal\migrate_drupal\Tests\d6\MigrateNodeRevisionTest.
    User::create([
      'uid' => 1,
      'name' => $this->randomMachineName(),
      'status' => 1,
    ])->enforceIsNew(TRUE)->save();

    $this->installMigrations('Drupal 6');
  }

  /**
   * Returns the path to the dump directory.
   *
   * @return string
   *   A string that represents the dump directory path.
   */
  protected function getDumpDirectory() {
    return dirname(__DIR__) . '/Table/d6';
  }

}
