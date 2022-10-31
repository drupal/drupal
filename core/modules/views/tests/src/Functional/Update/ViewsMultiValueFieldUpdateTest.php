<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the update path base class.
 *
 * @group Update
 * @group legacy
 */
class ViewsMultiValueFieldUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update_test_schema'];

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // This config is broken intentionally.
    'views.view.test_broken_config_multi_value',
    'views.view.test_another_broken_config_multi_value',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.3.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/multi_value_fields.php',
    ];
  }

  /**
   * Tests views_post_update_field_names_for_multivalue_fields().
   */
  public function testViewsPostUpdateFieldNamesForMultiValueFields() {
    $key_value_store = \Drupal::keyValue('post_update');
    $existing_update_functions = $key_value_store->get('existing_updates', []);
    $existing_update_functions = array_diff($existing_update_functions, ['views_post_update_field_names_for_multivalue_fields']);
    $key_value_store->set('existing_updates', $existing_update_functions);

    $this->runUpdates();

    $this->assertSession()->pageTextContainsOnce('Updates failed for the entity type View, for test_another_broken_config_multi_value, test_broken_config_multi_value. Check the logs.');
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/reports/dblog', ['query' => ['type[]' => 'update']]);
    $this->assertSession()->pageTextMatchesCount(2, '/Unable to update view test_broken_config_multi_value/');
  }

}
