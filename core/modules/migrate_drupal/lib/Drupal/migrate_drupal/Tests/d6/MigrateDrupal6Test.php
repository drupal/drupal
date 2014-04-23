<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateDrupal6Test.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\simpletest\TestBase;

/**
 * Test the complete Drupal 6 migration.
 */
class MigrateDrupal6Test extends MigrateDrupalTestBase {

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
    'custom_block',
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
  public function tearDown() {
    // Move the results of every class under ours. This is solely for
    // reporting, the filename will guide developers.
    self::getDatabaseConnection()
      ->update('simpletest')
      ->fields(array('test_class' => get_class($this)))
      ->condition('test_id', $this->testId)
      ->execute();
    parent::tearDown();
  }


  /**
   * Test the complete Drupal 6 migration.
   */
  public function testDrupal6() {
    $path = drupal_get_path('module', 'migrate_drupal');
    $dumps = array(
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6ActionSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6AggregatorFeed.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6AggregatorItem.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6AggregatorSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6Block.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6BookSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6Box.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6Comment.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6CommentVariable.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6ContactCategory.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6ContactSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6DateFormat.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6DblogSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6FieldInstance.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6FieldSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6File.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6FileSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6FilterFormat.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6ForumSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6LocaleSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6Menu.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6MenuSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6NodeBodyInstance.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6Node.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6NodeRevision.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6NodeSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6NodeType.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SearchPage.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SearchSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SimpletestSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6StatisticsSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SyslogSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemCron.php',
      // This dump contains the file directory path to the simpletest directory
      // where the files are.
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemFile.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemFilter.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemImageGd.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemImage.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemMaintenance.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemPerformance.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemRss.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemSite.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemTheme.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6TaxonomySettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6TaxonomyTerm.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6TaxonomyVocabulary.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6TermNode.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6TextSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6UpdateSettings.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6UploadInstance.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6Upload.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6UrlAlias.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6UserMail.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6User.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6UserProfileFields.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6UserRole.php',
      $path . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6VocabularyField.php',
    );
    $this->loadDumps($dumps);
    $migrations = array(
      'd6_action_settings',
      'd6_aggregator_settings',
      'd6_aggregator_feed',
      'd6_aggregator_item',
      'd6_block',
      'd6_book_settings',
      'd6_cck_field_values:*',
      'd6_cck_field_revision:*',
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
    $classes = array(
      __NAMESPACE__ . '\MigrateActionConfigsTest',
      __NAMESPACE__ . '\MigrateAggregatorConfigsTest',
      __NAMESPACE__ . '\MigrateAggregatorFeedTest',
      __NAMESPACE__ . '\MigrateAggregatorItemTest',
      __NAMESPACE__ . '\MigrateBlockTest',
      __NAMESPACE__ . '\MigrateBookConfigsTest',
      __NAMESPACE__ . '\MigrateCckFieldValuesTest',
      __NAMESPACE__ . '\MigrateCckFieldRevisionTest',
      __NAMESPACE__ . '\MigrateCommentTest',
      __NAMESPACE__ . '\MigrateCommentVariableEntityDisplay',
      __NAMESPACE__ . '\MigrateCommentVariableEntityFormDisplay',
      __NAMESPACE__ . '\MigrateCommentVariableField',
      __NAMESPACE__ . '\MigrateCommentVariableInstance',
      __NAMESPACE__ . '\MigrateContactCategoryTest',
      __NAMESPACE__ . '\MigrateContactConfigsTest',
      __NAMESPACE__ . '\MigrateCustomBlockTest',
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
    // Run every migration in the order specified by the storage controller.
    foreach (entity_load_multiple('migration', $migrations) as $migration) {
      (new MigrateExecutable($migration, $this))->import();
    }
    foreach ($classes as $class) {
      $test_object = new $class($this->testId);
      $test_object->databasePrefix = $this->databasePrefix;
      $test_object->container = $this->container;
      // run() does a lot of setup and tear down work which we don't need:
      // it would setup a new database connection and wouldn't find the
      // Drupal 6 dump. Also by skipping the setUp() methods there are no id
      // mappings or entities prepared. The tests run against solely migrated
      // data.
      foreach (get_class_methods($test_object) as $method) {
        if (strtolower(substr($method, 0, 4)) == 'test') {
          // Insert a fail record. This will be deleted on completion to ensure
          // that testing completed.
          $method_info = new \ReflectionMethod($class, $method);
          $caller = array(
            'file' => $method_info->getFileName(),
            'line' => $method_info->getStartLine(),
            'function' => $class . '->' . $method . '()',
          );
          $completion_check_id = TestBase::insertAssert($this->testId, $class, FALSE, 'The test did not complete due to a fatal error.', 'Completion check', $caller);
          // Run the test method.
          try {
            $test_object->$method();
          }
          catch (\Exception $e) {
            $this->exceptionHandler($e);
          }
          // Remove the completion check record.
          TestBase::deleteAssert($completion_check_id);
        }
      }
      // Add the pass/fail/exception/debug results.
      foreach ($this->results as $key => &$value) {
        $value += $test_object->results[$key];
      }
    }
  }

}
