<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\Tests\migrate_drupal\Traits\CreateTestContentEntitiesTrait;

// cspell:ignore drupalmysqldriverdatabasemysql

/**
 * Test the credential form for both Drupal 6 and Drupal 7 sources.
 *
 * The credential form is tested with incorrect credentials, correct
 * credentials, and incorrect file paths.
 *
 * @group migrate_drupal_ui
 */
class CredentialFormTest extends MigrateUpgradeTestBase {

  use CreateTestContentEntitiesTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_drupal_ui'];

  /**
   * Test the credential form.
   *
   * @dataProvider providerCredentialForm
   */
  public function testCredentialFrom($path_to_database) {
    $this->loadFixture($this->getModulePath('migrate_drupal') . $path_to_database);
    $session = $this->assertSession();

    // Get valid credentials.
    $edit = $this->getCredentials();
    $version = $edit['version'];
    $edits = $this->translatePostValues($edit);

    $this->drupalGet('/upgrade');
    $session->responseContains("Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal $this->destinationSiteVersion.");

    $this->submitForm([], 'Continue');
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('edit-drupalmysqldriverdatabasemysql-host');

    // Ensure submitting the form with invalid database credentials gives us a
    // nice warning.
    $this->submitForm([$edit['driver'] . '[database]' => 'wrong'] + $edits, 'Review upgrade');
    $session->pageTextContains('Resolve all issues below to continue the upgrade.');

    // Resubmit with correct credentials.
    $this->submitForm($edits, 'Review upgrade');
    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Restart the upgrade and test the file source paths.
    $this->drupalGet('/upgrade');
    $this->submitForm([], 'Continue');
    if ($version == 6) {
      $paths['d6_source_base_path'] = DRUPAL_ROOT . '/wrong-path';
    }
    else {
      $paths['source_base_path'] = 'https://example.com/wrong-path';
      $paths['source_private_file_path'] = DRUPAL_ROOT . '/wrong-path';
    }
    $this->submitForm($paths + $edits, 'Review upgrade');
    if ($version == 6) {
      $session->responseContains('Failed to read from Document root for files.');
    }
    else {
      $session->responseContains('Failed to read from Document root for public files.');
      $session->responseContains('Failed to read from Document root for private files.');
    }
  }

  /**
   * Data provider for testCredentialForm.
   */
  public function providerCredentialForm() {
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
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);
    return __DIR__ . '/d' . $version . '/files';
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
