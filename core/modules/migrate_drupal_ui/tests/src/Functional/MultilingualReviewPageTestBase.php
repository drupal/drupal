<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal_ui\Functional;

/**
 * Provides a base class for testing the review step of the Upgrade form.
 *
 * When using this test class, enable translation modules.
 */
abstract class MultilingualReviewPageTestBase extends MigrateUpgradeTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_drupal_ui'];

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
   * example modules. This means that we can test that the modules that do not
   * need any migrations, such as Overlay, since there will be no available
   * migrations which declare those modules as their source_module. It is
   * assumed that the test fixtures include all modules that have moved to or
   * dropped from core.
   *
   * The upgrade review form will also display errors for each migration that
   * does not have a source_module definition. That function is not tested here.
   *
   * @see \Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase
   */
  public function testMigrateUpgradeReviewPage(): void {
    $this->prepare();
    // Start the upgrade process.
    $this->submitCredentialForm();
    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');

    // Test the upgrade paths.
    $this->assertReviewForm();

    // Check there are no errors when a module does not have any migrations and
    // does not need any. Test with a module that is in both Drupal 6 and
    // Drupal 7 core.
    $module = 'help';
    $module_name = 'Help';
    $query = $this->sourceDatabase->delete('system');
    $query->condition('type', 'module');
    $query->condition('name', $module);
    $query->execute();

    // Start the upgrade process.
    $this->drupalGet('/upgrade');
    $this->submitForm([], 'Continue');
    $this->submitForm($this->edits, 'Review upgrade');
    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');

    // Test the upgrade paths. First remove the module from the available paths
    // list.
    $available_paths = $this->getAvailablePaths();
    $available_paths = array_diff($available_paths, [$module_name]);
    $this->assertReviewForm($available_paths);
  }

  /**
   * Performs preparation for the form tests.
   *
   * This is not done in setup because setup executes before the source database
   * is loaded.
   */
  public function prepare() {
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

    // Create entries for D8 test modules.
    $insert = $this->sourceDatabase->insert('system')
      ->fields([
        'filename' => 'migrate_status_active_test',
        'name' => 'migrate_status_active_test',
        'type' => 'module',
        'status' => 1,
      ]);
    $insert->execute();
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
