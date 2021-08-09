<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Database\Database;

/**
 * Tests the installer with database errors.
 *
 * @group Installer
 */
class InstallerDatabaseErrorMessagesTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUpSettings() {
    // We are creating a table here to force an error in the installer because
    // it will try and create the drupal_install_test table as this is part of
    // the standard database tests performed by the installer in
    // Drupal\Core\Database\Install\Tasks.
    $spec = [
      'fields' => [
        'id' => [
          'type' => 'int',
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['id'],
    ];

    Database::getConnection('default')->schema()->createTable('drupal_install_test', $spec);
    parent::setUpSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite() {
    // This step should not appear as we had a failure on the settings screen.
  }

  /**
   * Verifies that the error message in the settings step is correct.
   */
  public function testSetUpSettingsErrorMessage() {
    $this->assertSession()->responseContains('<ul><li>Failed to <strong>CREATE</strong> a test table');
  }

}
