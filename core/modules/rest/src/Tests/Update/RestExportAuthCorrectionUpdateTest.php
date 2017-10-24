<?php

namespace Drupal\rest\Tests\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Ensures that update hook is run properly for REST Export config.
 *
 * @group Update
 */
class RestExportAuthCorrectionUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../tests/fixtures/update/rest-export-with-authentication-correction.php',
    ];
  }

  /**
   * Ensures that update hook is run for "rest" module.
   */
  public function testUpdate() {
    $this->runUpdates();

    // Get particular view.
    $view = \Drupal::entityTypeManager()->getStorage('view')->load('rest_export_with_authorization_correction');
    $displays = $view->get('display');
    $this->assertIdentical($displays['rest_export_1']['display_options']['auth'], ['cookie'], 'Cookie is used for authentication');
  }

}
