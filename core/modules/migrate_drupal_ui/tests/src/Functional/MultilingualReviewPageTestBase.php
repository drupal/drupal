<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\Tests\migrate_drupal\Traits\CreateTestContentEntitiesTrait;

/**
 * Provides a base class for testing the review step of the Upgrade form.
 *
 * When using this test class, enable translation modules.
 */
abstract class MultilingualReviewPageTestBase extends MigrateUpgradeTestBase {

  use CreateTestContentEntitiesTrait;

  /**
   * An array suitable for drupalPostForm().
   *
   * @var array
   */
  protected $edits = [];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal_ui'];

  /**
   * Tests the migrate upgrade review form.
   *
   * The upgrade review form displays a list of modules that will be upgraded
   * and a list of modules that will not be upgraded. This test is to ensure
   * that the review page works correctly for all contributed Drupal 6 and
   * Drupal 7 modules that have moved to core, e.g. Views, and for modules that
   * were in Drupal 6 or Drupal 7 core but are not in Drupal 8 core, e.g.
   * Overlay.
   *
   * To do this all modules in the source fixtures are enabled, except test and
   * example modules. This means that we can test that the modules listed in the
   * the $noUpgradePath property of the update form class are correct, since
   * there will be no available migrations which declare those modules as their
   * source_module. It is assumed that the test fixtures include all modules
   * that have moved to or dropped from core.
   *
   * The upgrade review form will also display errors for each migration that
   * does not have a source_module definition. That function is not tested here.
   *
   * @see \Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase
   */
  public function testMigrateUpgradeReviewPage() {
    $this->prepare();
    // Start the upgrade process.
    $this->drupalGet('/upgrade');
    $this->drupalPostForm(NULL, [], t('Continue'));
    $this->drupalPostForm(NULL, $this->edits, t('Review upgrade'));
    $this->drupalPostForm(NULL, [], t('I acknowledge I may lose data. Continue anyway.'));

    // Ensure there are no errors about missing modules from the test module.
    $session = $this->assertSession();
    $session->pageTextNotContains(t('Source module not found for migration_provider_no_annotation.'));
    $session->pageTextNotContains(t('Source module not found for migration_provider_test.'));
    $session->pageTextNotContains(t('Destination module not found for migration_provider_test'));
    // Ensure there are no errors about any other missing migration providers.
    $session->pageTextNotContains(t('module not found'));

    // Test the upgrade paths.
    $available_paths = $this->getAvailablePaths();
    $missing_paths = $this->getMissingPaths();
    $this->assertUpgradePaths($session, $available_paths, $missing_paths);

    // Check there are no errors when a module in noUpgradePaths is not in the
    // source system tables. Test with a module that is listed in noUpgradePaths
    // for both Drupal 6 and Drupal 7.
    // @see \Drupal\migrate_drupal_ui\Form\ReviewForm::$noUpgradePaths
    $module = 'help';
    $query = $this->sourceDatabase->delete('system');
    $query->condition('type', 'module');
    $query->condition('name', $module);
    $query->execute();

    // Start the upgrade process.
    $this->drupalGet('/upgrade');
    $this->drupalPostForm(NULL, [], t('Continue'));
    $this->drupalPostForm(NULL, $this->edits, t('Review upgrade'));
    $this->drupalPostForm(NULL, [], t('I acknowledge I may lose data. Continue anyway.'));

    // Test the upgrade paths.
    $available_paths = $this->getAvailablePaths();
    $available_paths = array_diff($available_paths, [$module]);
    $missing_paths = $this->getMissingPaths();
    $this->assertUpgradePaths($session, $available_paths, $missing_paths);
  }

  /**
   * Performs preparation for the form tests.
   *
   * This is not done in setup because setup executes before the source database
   * is loaded.
   */
  public function prepare() {
    $connection_options = $this->sourceDatabase->getConnectionOptions();
    $driver = $connection_options['driver'];
    $connection_options['prefix'] = $connection_options['prefix']['default'];

    // Use the driver connection form to get the correct options out of the
    // database settings. This supports all of the databases we test against.
    $drivers = drupal_get_database_types();
    $form = $drivers[$driver]->getFormOptions($connection_options);
    $connection_options = array_intersect_key($connection_options, $form + $form['advanced_options']);
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);
    $edit = [
      $driver => $connection_options,
      'source_private_file_path' => $this->getSourceBasePath(),
      'version' => $version,
    ];
    if ($version == 6) {
      $edit['d6_source_base_path'] = $this->getSourceBasePath();
    }
    else {
      $edit['source_base_path'] = $this->getSourceBasePath();
    }
    if (count($drivers) !== 1) {
      $edit['driver'] = $driver;
    }
    $this->edits = $this->translatePostValues($edit);

    // Enable all modules in the source except test and example modules, but
    // include simpletest.
    /** @var \Drupal\Core\Database\Query\SelectInterface $update */
    $update = $this->sourceDatabase->update('system')
      ->fields(['status' => 1])
      ->condition('type', 'module');
    $and = $update->andConditionGroup()
      ->condition('name', '%test%', 'NOT LIKE')
      ->condition('name', '%example%', 'NOT LIKE');
    $conditions = $update->orConditionGroup();
    $conditions->condition($and);
    $conditions->condition('name', 'simpletest');
    $update->condition($conditions);
    $update->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    return [];
  }

}
