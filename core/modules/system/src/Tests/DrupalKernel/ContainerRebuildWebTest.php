<?php

/**
 * @file
 * Contains \Drupal\system\Tests\DrupalKernel\ContainerRebuildWebTest.
 */

namespace Drupal\system\Tests\DrupalKernel;

use Drupal\simpletest\WebTestBase;

/**
 * Ensures that the container rebuild works as expected.
 *
 * @group DrupalKernel
 */
class ContainerRebuildWebTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['service_provider_test'];

  /**
   * Sets a different deployment identifier.
   */
  public function testSetContainerRebuildWithDifferentDeploymentIdentifier() {
    $this->drupalGet('<front>');
    $this->assertHeader('container_rebuild_indicator', FALSE);

    $this->writeSettings(['settings' => ['deployment_identifier' => (object) ['value' => 'new-identifier', 'required' => TRUE]]]);

    $this->drupalGet('<front>');

    $this->assertHeader('container_rebuild_indicator', 'new-identifier');
  }

}
