<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

/**
 * Tests that a missing source provider error message is displayed.
 *
 * @group migrate_drupal_ui
 */
class SourceProviderTest extends MigrateUpgradeTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate_drupal_ui',
    // Will generate an error for a missing source module.
    'migration_provider_test',
  ];

  /**
   * Test missing source provider.
   *
   * @dataProvider providerSourceProvider
   */
  public function testSourceProvider($path_to_database) {
    $this->loadFixture(drupal_get_path('module', 'migrate_drupal') . $path_to_database);

    $session = $this->assertSession();
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
    $edits = $this->translatePostValues($edit);

    // Start the upgrade.
    $this->drupalGet('/upgrade');
    [$new_site_version] = explode('.', \Drupal::VERSION, 2);
    $session->responseContains("Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal $new_site_version.");
    $this->submitForm([], 'Continue');
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');
    $this->submitForm($edits, 'Review upgrade');

    // Ensure we get errors about missing modules.
    $session->pageTextContains(t('Resolve all issues below to continue the upgrade.'));
    $session->pageTextContains(t('The no_source_module plugin must define the source_module property.'));

    // Uninstall the module causing the missing module error messages.
    $this->container->get('module_installer')
      ->uninstall(['migration_provider_test'], TRUE);

    // Restart the upgrade process and test there is no source_module error.
    $this->drupalGet('/upgrade');
    $session->responseContains("Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal $new_site_version.");
    $this->submitForm([], 'Continue');
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');
    $this->submitForm($edits, 'Review upgrade');

    // Ensure there are no errors about missing modules from the test module.
    $session->pageTextNotContains(t('Source module not found for migration_provider_no_annotation.'));
    $session->pageTextNotContains(t('Source module not found for migration_provider_test.'));
    // Ensure there are no errors about any other missing migration providers.
    $session->pageTextNotContains(t('module not found'));
  }

  /**
   * Data provider for testSourceProvider.
   */
  public function providerSourceProvider() {
    return [
      [
        'path_to_database' => '/tests/fixtures/drupal6.php',
      ],
      [
        'path_to_database' => '/tests/fixtures/drupal7.php',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
    return [];
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

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [];
  }

}
