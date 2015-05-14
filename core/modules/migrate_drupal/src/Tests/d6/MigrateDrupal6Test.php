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
    'file',
    'filter',
    'forum',
    'image',
    'language',
    'link',
    'locale',
    'menu_link_content',
    'menu_ui',
    'node',
    'options',
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
    'd6_cck_field_values:*',
    'd6_cck_field_revision:*',
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
    'd6_locale_settings',
    'd6_menu_settings',
    'd6_menu',
    'd6_menu_links',
    'd6_node_revision',
    'd6_node_setting_promote',
    'd6_node_setting_status',
    'd6_node_setting_sticky',
    'd6_node',
    'd6_node_settings',
    'd6_node_type',
    'd6_profile_values:user',
    'd6_search_page',
    'd6_search_settings',
    'd6_simpletest_settings',
    'd6_statistics_settings',
    'd6_syslog_settings',
    'd6_system_cron',
    'd6_system_file',
    'd6_system_filter',
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
    'd6_text_settings',
    'd6_update_settings',
    'd6_upload_entity_display',
    'd6_upload_entity_form_display',
    'd6_upload_field',
    'd6_upload_field_instance',
    'd6_upload',
    'd6_url_alias',
    'd6_user_mail',
    'd6_user_contact_settings',
    'd6_user_profile_field_instance',
    'd6_user_profile_entity_display',
    'd6_user_profile_entity_form_display',
    'd6_user_profile_field',
    'd6_user_picture_entity_display',
    'd6_user_picture_entity_form_display',
    'd6_user_picture_field_instance',
    'd6_user_picture_field',
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
  }

  /**
   * {@inheritdoc}
   */
  protected function getDumps() {
    $tests_path = $this->getDumpDirectory();
    $dumps = array(
      $tests_path . '/AggregatorFeed.php',
      $tests_path . '/AggregatorItem.php',
      $tests_path . '/Blocks.php',
      $tests_path . '/BlocksRoles.php',
      $tests_path . '/Book.php',
      $tests_path . '/Boxes.php',
      $tests_path . '/Comments.php',
      $tests_path . '/Contact.php',
      $tests_path . '/ContentFieldMultivalue.php',
      $tests_path . '/ContentFieldTest.php',
      $tests_path . '/ContentFieldTestTwo.php',
      $tests_path . '/ContentNodeField.php',
      $tests_path . '/ContentNodeFieldInstance.php',
      $tests_path . '/ContentTypeStory.php',
      $tests_path . '/ContentTypeTestPlanet.php',
      $tests_path . '/EventTimezones.php',
      $tests_path . '/Files.php',
      $tests_path . '/FilterFormats.php',
      $tests_path . '/Filters.php',
      $tests_path . '/MenuCustom.php',
      $tests_path . '/MenuLinks.php',
      $tests_path . '/Node.php',
      $tests_path . '/NodeRevisions.php',
      $tests_path . '/NodeType.php',
      $tests_path . '/Permission.php',
      $tests_path . '/ProfileFields.php',
      $tests_path . '/ProfileValues.php',
      $tests_path . '/Role.php',
      $tests_path . '/TermData.php',
      $tests_path . '/TermHierarchy.php',
      $tests_path . '/TermNode.php',
      $tests_path . '/Upload.php',
      $tests_path . '/UrlAlias.php',
      $tests_path . '/Users.php',
      $tests_path . '/UsersRoles.php',
      $tests_path . '/Variable.php',
      $tests_path . '/Vocabulary.php',
      $tests_path . '/VocabularyNodeTypes.php',
    );

    return $dumps;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTestClassesList() {
    $classes = array(
      __NAMESPACE__ . '\MigrateActionConfigsTest',
      __NAMESPACE__ . '\MigrateAggregatorConfigsTest',
      __NAMESPACE__ . '\MigrateAggregatorFeedTest',
      __NAMESPACE__ . '\MigrateAggregatorItemTest',
      __NAMESPACE__ . '\MigrateBlockTest',
      __NAMESPACE__ . '\MigrateBookTest',
      __NAMESPACE__ . '\MigrateBookConfigsTest',
      __NAMESPACE__ . '\MigrateCckFieldValuesTest',
      __NAMESPACE__ . '\MigrateCckFieldRevisionTest',
      __NAMESPACE__ . '\MigrateCommentTypeTest',
      __NAMESPACE__ . '\MigrateCommentTest',
      __NAMESPACE__ . '\MigrateCommentVariableEntityDisplayTest',
      __NAMESPACE__ . '\MigrateCommentVariableEntityFormDisplayTest',
      __NAMESPACE__ . '\MigrateCommentVariableEntityFormDisplaySubjectTest',
      __NAMESPACE__ . '\MigrateCommentVariableFieldTest',
      __NAMESPACE__ . '\MigrateCommentVariableInstanceTest',
      __NAMESPACE__ . '\MigrateContactCategoryTest',
      __NAMESPACE__ . '\MigrateContactConfigsTest',
      __NAMESPACE__ . '\MigrateBlockContentTest',
      __NAMESPACE__ . '\MigrateDateFormatTest',
      __NAMESPACE__ . '\MigrateDblogConfigsTest',
      __NAMESPACE__ . '\MigrateFieldTest',
      __NAMESPACE__ . '\MigrateFieldInstanceTest',
      __NAMESPACE__ . '\MigrateFieldFormatterSettingsTest',
      __NAMESPACE__ . '\MigrateFieldWidgetSettingsTest',
      __NAMESPACE__ . '\MigrateFileConfigsTest',
      __NAMESPACE__ . '\MigrateFileTest',
      __NAMESPACE__ . '\MigrateFilterFormatTest',
      __NAMESPACE__ . '\MigrateForumConfigsTest',
      __NAMESPACE__ . '\MigrateLocaleConfigsTest',
      __NAMESPACE__ . '\MigrateMenuConfigsTest',
      __NAMESPACE__ . '\MigrateMenuLinkTest',
      __NAMESPACE__ . '\MigrateMenuTest',
      __NAMESPACE__ . '\MigrateNodeBundleSettingsTest',
      __NAMESPACE__ . '\MigrateNodeConfigsTest',
      __NAMESPACE__ . '\MigrateNodeRevisionTest',
      __NAMESPACE__ . '\MigrateNodeTest',
      __NAMESPACE__ . '\MigrateNodeTypeTest',
      __NAMESPACE__ . '\MigrateUserProfileValuesTest',
      __NAMESPACE__ . '\MigrateSearchConfigsTest',
      __NAMESPACE__ . '\MigrateSearchPageTest',
      __NAMESPACE__ . '\MigrateSimpletestConfigsTest',
      __NAMESPACE__ . '\MigrateStatisticsConfigsTest',
      __NAMESPACE__ . '\MigrateSyslogConfigsTest',
      __NAMESPACE__ . '\MigrateSystemCronTest',
      __NAMESPACE__ . '\MigrateSystemFileTest',
      __NAMESPACE__ . '\MigrateSystemFilterTest',
      __NAMESPACE__ . '\MigrateSystemImageGdTest',
      __NAMESPACE__ . '\MigrateSystemImageTest',
      __NAMESPACE__ . '\MigrateSystemLoggingTest',
      __NAMESPACE__ . '\MigrateSystemMaintenanceTest',
      __NAMESPACE__ . '\MigrateSystemPerformanceTest',
      __NAMESPACE__ . '\MigrateSystemRssTest',
      __NAMESPACE__ . '\MigrateSystemSiteTest',
      __NAMESPACE__ . '\MigrateTaxonomyConfigsTest',
      __NAMESPACE__ . '\MigrateTaxonomyTermTest',
      __NAMESPACE__ . '\MigrateTaxonomyVocabularyTest',
      __NAMESPACE__ . '\MigrateTermNodeRevisionTest',
      __NAMESPACE__ . '\MigrateTermNodeTest',
      __NAMESPACE__ . '\MigrateTextConfigsTest',
      __NAMESPACE__ . '\MigrateUpdateConfigsTest',
      __NAMESPACE__ . '\MigrateUploadEntityDisplayTest',
      __NAMESPACE__ . '\MigrateUploadEntityFormDisplayTest',
      __NAMESPACE__ . '\MigrateUploadFieldTest',
      __NAMESPACE__ . '\MigrateUploadInstanceTest',
      __NAMESPACE__ . '\MigrateUploadTest',
      __NAMESPACE__ . '\MigrateUrlAliasTest',
      __NAMESPACE__ . '\MigrateUserConfigsTest',
      __NAMESPACE__ . '\MigrateUserContactSettingsTest',
      __NAMESPACE__ . '\MigrateUserProfileEntityDisplayTest',
      __NAMESPACE__ . '\MigrateUserProfileEntityFormDisplayTest',
      __NAMESPACE__ . '\MigrateUserProfileFieldTest',
      __NAMESPACE__ . '\MigrateUserProfileFieldInstanceTest',
      __NAMESPACE__ . '\MigrateUserPictureEntityDisplayTest',
      __NAMESPACE__ . '\MigrateUserPictureEntityFormDisplayTest',
      __NAMESPACE__ . '\MigrateUserPictureFileTest',
      __NAMESPACE__ . '\MigrateUserPictureFieldTest',
      __NAMESPACE__ . '\MigrateUserPictureInstanceTest',
      __NAMESPACE__ . '\MigrateUserRoleTest',
      __NAMESPACE__ . '\MigrateUserTest',
      __NAMESPACE__ . '\MigrateViewModesTest',
      __NAMESPACE__ . '\MigrateVocabularyEntityDisplayTest',
      __NAMESPACE__ . '\MigrateVocabularyEntityFormDisplayTest',
      __NAMESPACE__ . '\MigrateVocabularyFieldInstanceTest',
      __NAMESPACE__ . '\MigrateVocabularyFieldTest',
    );

    return $classes;
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
