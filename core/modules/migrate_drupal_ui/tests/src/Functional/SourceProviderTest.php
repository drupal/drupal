<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal_ui\Functional;

/**
 * Tests that a missing source provider error message is displayed.
 *
 * @group migrate_drupal_ui
 * @group #slow
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
  public function testSourceProvider($path_to_database): void {
    $this->loadFixture($this->getModulePath('migrate_drupal') . $path_to_database);

    $session = $this->assertSession();

    // Start the upgrade process.
    $this->submitCredentialForm();

    // Ensure we get errors about missing modules.
    $session->pageTextContains('Resolve all issues below to continue the upgrade.');
    $session->pageTextContains('The no_source_module plugin must define the source_module property.');

    // Uninstall the module causing the missing module error messages.
    $this->container->get('module_installer')
      ->uninstall(['migration_provider_test'], TRUE);

    // Restart the upgrade process and test there is no source_module error.
    $this->drupalGet('/upgrade');
    $this->submitForm([], 'Continue');
    $this->submitForm($this->edits, 'Review upgrade');

    // Ensure there are no errors about missing modules from the test module.
    $session->pageTextNotContains('Source module not found for migration_provider_no_annotation.');
    $session->pageTextNotContains('Source module not found for migration_provider_test.');
    // Ensure there are no errors about any other missing migration providers.
    $session->pageTextNotContains('module not found');
  }

  /**
   * Data provider for testSourceProvider.
   */
  public static function providerSourceProvider() {
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
  protected function getSourceBasePath(): string {
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
