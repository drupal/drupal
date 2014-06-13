<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateDrupal6Test.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate_drupal\Tests\MigrateFullDrupalTestBase;

/**
 * Test the complete Drupal 6 migration.
 */
class MigrateDrupal6Test extends MigrateFullDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  static $modules = array(
    'action',
    'aggregator',
    'block',
    'book',
    'comment',
    'contact',
    'block_content',
    'datetime',
    'dblog',
    'file',
    'forum',
    'image',
    'link',
    'locale',
    'menu_ui',
    'node',
    'options',
    'search',
    'simpletest',
    'syslog',
    'taxonomy',
    'telephone',
    'text',
    'update',
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
    'd6_book_settings',
    'd6_cck_field_values:*',
    'd6_cck_field_revision:*',
    'd6_comment_type',
    'd6_comment',
    'd6_comment_entity_display',
    'd6_comment_entity_form_display',
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
    'd6_field_settings',
    'd6_field_formatter_settings',
    'd6_file_settings',
    'd6_file',
    'd6_filter_format',
    'd6_forum_settings',
    'd6_locale_settings',
    'd6_menu_settings',
    'd6_menu',
    'd6_node_revision',
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
    'd6_system_maintenance',
    'd6_system_performance',
    'd6_system_rss',
    'd6_system_site',
    'd6_system_theme',
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
  public static function getInfo() {
    return array(
      'name'  => 'Migrate Drupal 6',
      'description'  => 'Test every Drupal 6 migration',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getDumps() {
    $tests_path = $this->getDumpDirectory();
    $dumps = array(
      $tests_path . '/Drupal6ActionSettings.php',
      $tests_path . '/Drupal6AggregatorFeed.php',
      $tests_path . '/Drupal6AggregatorItem.php',
      $tests_path . '/Drupal6AggregatorSettings.php',
      $tests_path . '/Drupal6Block.php',
      $tests_path . '/Drupal6BookSettings.php',
      $tests_path . '/Drupal6Box.php',
      $tests_path . '/Drupal6Comment.php',
      $tests_path . '/Drupal6CommentVariable.php',
      $tests_path . '/Drupal6ContactCategory.php',
      $tests_path . '/Drupal6ContactSettings.php',
      $tests_path . '/Drupal6DateFormat.php',
      $tests_path . '/Drupal6DblogSettings.php',
      $tests_path . '/Drupal6FieldInstance.php',
      $tests_path . '/Drupal6FieldSettings.php',
      $tests_path . '/Drupal6File.php',
      $tests_path . '/Drupal6FileSettings.php',
      $tests_path . '/Drupal6FilterFormat.php',
      $tests_path . '/Drupal6ForumSettings.php',
      $tests_path . '/Drupal6LocaleSettings.php',
      $tests_path . '/Drupal6Menu.php',
      $tests_path . '/Drupal6MenuSettings.php',
      $tests_path . '/Drupal6NodeBodyInstance.php',
      $tests_path . '/Drupal6Node.php',
      $tests_path . '/Drupal6NodeRevision.php',
      $tests_path . '/Drupal6NodeSettings.php',
      $tests_path . '/Drupal6NodeType.php',
      $tests_path . '/Drupal6SearchPage.php',
      $tests_path . '/Drupal6SearchSettings.php',
      $tests_path . '/Drupal6SimpletestSettings.php',
      $tests_path . '/Drupal6StatisticsSettings.php',
      $tests_path . '/Drupal6SyslogSettings.php',
      $tests_path . '/Drupal6SystemCron.php',
      // This dump contains the file directory path to the simpletest directory
      // where the files are.
      $tests_path . '/Drupal6SystemFile.php',
      $tests_path . '/Drupal6SystemFilter.php',
      $tests_path . '/Drupal6SystemImageGd.php',
      $tests_path . '/Drupal6SystemImage.php',
      $tests_path . '/Drupal6SystemMaintenance.php',
      $tests_path . '/Drupal6SystemPerformance.php',
      $tests_path . '/Drupal6SystemRss.php',
      $tests_path . '/Drupal6SystemSite.php',
      $tests_path . '/Drupal6SystemTheme.php',
      $tests_path . '/Drupal6TaxonomySettings.php',
      $tests_path . '/Drupal6TaxonomyTerm.php',
      $tests_path . '/Drupal6TaxonomyVocabulary.php',
      $tests_path . '/Drupal6TermNode.php',
      $tests_path . '/Drupal6TextSettings.php',
      $tests_path . '/Drupal6UpdateSettings.php',
      $tests_path . '/Drupal6UploadInstance.php',
      $tests_path . '/Drupal6Upload.php',
      $tests_path . '/Drupal6UrlAlias.php',
      $tests_path . '/Drupal6UserMail.php',
      $tests_path . '/Drupal6User.php',
      $tests_path . '/Drupal6UserProfileFields.php',
      $tests_path . '/Drupal6UserRole.php',
      $tests_path . '/Drupal6VocabularyField.php',
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
      __NAMESPACE__ . '\MigrateBookConfigsTest',
      __NAMESPACE__ . '\MigrateCckFieldValuesTest',
      __NAMESPACE__ . '\MigrateCckFieldRevisionTest',
      __NAMESPACE__ . '\MigrateCommentTypeTest',
      __NAMESPACE__ . '\MigrateCommentTest',
      __NAMESPACE__ . '\MigrateCommentVariableEntityDisplay',
      __NAMESPACE__ . '\MigrateCommentVariableEntityFormDisplay',
      __NAMESPACE__ . '\MigrateCommentVariableField',
      __NAMESPACE__ . '\MigrateCommentVariableInstance',
      __NAMESPACE__ . '\MigrateContactCategoryTest',
      __NAMESPACE__ . '\MigrateContactConfigsTest',
      __NAMESPACE__ . '\MigrateBlockContentTest',
      __NAMESPACE__ . '\MigrateDateFormatTest',
      __NAMESPACE__ . '\MigrateDblogConfigsTest',
      __NAMESPACE__ . '\MigrateFieldConfigsTest',
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
      __NAMESPACE__ . '\MigrateMenuTest',
      __NAMESPACE__ . '\MigrateNodeConfigsTest',
      __NAMESPACE__ . '\MigrateNodeRevisionTest',
      __NAMESPACE__ . '\MigrateNodeTest',
      __NAMESPACE__ . '\MigrateNodeTypeTest',
      __NAMESPACE__ . '\MigrateProfileValuesTest',
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
      __NAMESPACE__ . '\MigrateSystemMaintenanceTest',
      __NAMESPACE__ . '\MigrateSystemPerformanceTest',
      __NAMESPACE__ . '\MigrateSystemRssTest',
      __NAMESPACE__ . '\MigrateSystemSiteTest',
      __NAMESPACE__ . '\MigrateSystemThemeTest',
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

}
