<?php

namespace Drupal\Tests\rest\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Ensures that update hook is run properly for REST Export config.
 *
 * @group legacy
 */
class RestExportAuthCorrectionUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/rest-export-with-authentication-correction.php',
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
