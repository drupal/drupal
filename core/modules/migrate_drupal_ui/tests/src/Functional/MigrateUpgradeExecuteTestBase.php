<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Drupal\Tests\migrate_drupal\Traits\CreateTestContentEntitiesTrait;

/**
 * Provides a base class for testing a complete upgrade via the UI.
 */
abstract class MigrateUpgradeExecuteTestBase extends MigrateUpgradeTestBase {

  use MigrationConfigurationTrait;
  use CreateTestContentEntitiesTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create content.
    $this->createContent();
  }

  /**
   * Executes all steps of migrations upgrade.
   */
  public function testMigrateUpgradeExecute() {
    $connection_options = $this->sourceDatabase->getConnectionOptions();
    $this->drupalGet('/upgrade');
    $session = $this->assertSession();
    $session->responseContains('Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal 8.');

    $this->drupalPostForm(NULL, [], t('Continue'));
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');

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
    $edits = $this->translatePostValues($edit);

    // Ensure submitting the form with invalid database credentials gives us a
    // nice warning.
    $this->drupalPostForm(NULL, [$driver . '[database]' => 'wrong'] + $edits, t('Review upgrade'));
    $session->pageTextContains('Resolve the issue below to continue the upgrade.');

    $this->drupalPostForm(NULL, $edits, t('Review upgrade'));
    // Ensure we get errors about missing modules.
    $session->pageTextContains(t('Resolve the issue below to continue the upgrade'));
    $session->pageTextContains(t('The no_source_module plugin must define the source_module property.'));

    // Uninstall the module causing the missing module error messages.
    $this->container->get('module_installer')->uninstall(['migration_provider_test'], TRUE);

    // Restart the upgrade process.
    $this->drupalGet('/upgrade');
    $session->responseContains('Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal 8.');

    $this->drupalPostForm(NULL, [], t('Continue'));
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');

    $this->drupalPostForm(NULL, $edits, t('Review upgrade'));
    $session->pageTextContains('WARNING: Content may be overwritten on your new site.');
    $session->pageTextContains('There is conflicting content of these types:');
    $session->pageTextContains('aggregator feed entities');
    $session->pageTextContains('aggregator feed item entities');
    $session->pageTextContains('custom block entities');
    $session->pageTextContains('custom menu link entities');
    $session->pageTextContains('file entities');
    $session->pageTextContains('taxonomy term entities');
    $session->pageTextContains('user entities');
    $session->pageTextContains('comments');
    $session->pageTextContains('content item revisions');
    $session->pageTextContains('content items');
    $session->pageTextContains('There is translated content of these types:');
    $this->drupalPostForm(NULL, [], t('I acknowledge I may lose data. Continue anyway.'));
    $session->statusCodeEquals(200);
    $session->pageTextContains('Upgrade analysis report');
    // Ensure there are no errors about missing modules from the test module.
    $session->pageTextNotContains(t('Source module not found for migration_provider_no_annotation.'));
    $session->pageTextNotContains(t('Source module not found for migration_provider_test.'));
    // Ensure there are no errors about any other missing migration providers.
    $session->pageTextNotContains(t('module not found'));

    // Test the upgrade paths.
    $available_paths = $this->getAvailablePaths();
    $missing_paths = $this->getMissingPaths();
    $this->assertUpgradePaths($session, $available_paths, $missing_paths);

    $this->drupalPostForm(NULL, [], t('Perform upgrade'));
    $this->assertText(t('Congratulations, you upgraded Drupal!'));

    // Have to reset all the statics after migration to ensure entities are
    // loadable.
    $this->resetAll();

    $expected_counts = $this->getEntityCounts();
    foreach (array_keys(\Drupal::entityTypeManager()
      ->getDefinitions()) as $entity_type) {
      $real_count = \Drupal::entityQuery($entity_type)->count()->execute();
      $expected_count = isset($expected_counts[$entity_type]) ? $expected_counts[$entity_type] : 0;
      $this->assertEqual($expected_count, $real_count, "Found $real_count $entity_type entities, expected $expected_count.");
    }

    $plugin_manager = \Drupal::service('plugin.manager.migration');
    /** @var \Drupal\migrate\Plugin\Migration[] $all_migrations */
    $all_migrations = $plugin_manager->createInstancesByTag('Drupal ' . $version);
    foreach ($all_migrations as $migration) {
      $id_map = $migration->getIdMap();
      foreach ($id_map as $source_id => $map) {
        // Convert $source_id into a keyless array so that
        // \Drupal\migrate\Plugin\migrate\id_map\Sql::getSourceHash() works as
        // expected.
        $source_id_values = array_values(unserialize($source_id));
        $row = $id_map->getRowBySource($source_id_values);
        $destination = serialize($id_map->currentDestination());
        $message = "Migration of $source_id to $destination as part of the {$migration->id()} migration. The source row status is " . $row['source_row_status'];
        // A completed migration should have maps with
        // MigrateIdMapInterface::STATUS_IGNORED or
        // MigrateIdMapInterface::STATUS_IMPORTED.
        if ($row['source_row_status'] == MigrateIdMapInterface::STATUS_FAILED || $row['source_row_status'] == MigrateIdMapInterface::STATUS_NEEDS_UPDATE) {
          $this->fail($message);
        }
        else {
          $this->pass($message);
        }
      }
    }
    \Drupal::service('module_installer')->install(['forum']);
    \Drupal::service('module_installer')->install(['book']);
  }

}
