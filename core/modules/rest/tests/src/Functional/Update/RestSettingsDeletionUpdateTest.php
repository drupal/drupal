<?php

namespace Drupal\Tests\rest\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Ensures that update hook is run properly for deleting obsolete REST settings.
 *
 * @group legacy
 */
class RestSettingsDeletionUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/rest-module-installed.php',
    ];
  }

  /**
   * Ensures that update hook is run for "rest" module.
   */
  public function testUpdate() {
    // @todo Remove this in https://www.drupal.org/project/drupal/issues/3095333.
    \Drupal::entityDefinitionUpdateManager()->installEntityType(\Drupal::entityTypeManager()->getDefinition('rest_resource_config'));

    $rest_settings = $this->config('rest.settings');
    $this->assertFalse($rest_settings->isNew());

    $this->runUpdates();

    $rest_settings = \Drupal::configFactory()->get('rest.settings');
    $this->assertTrue($rest_settings->isNew());
  }

}
