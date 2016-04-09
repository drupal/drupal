<?php

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

  /**
   * Tests container invalidation.
   */
  public function testContainerInvalidation() {

    // Ensure that parameter is not set.
    $this->drupalGet('<front>');
    $this->assertHeader('container_rebuild_test_parameter', FALSE);

    // Ensure that after setting the parameter, without a container rebuild the
    // parameter is still not set.
    $this->writeSettings(['settings' => ['container_rebuild_test_parameter' => (object) ['value' => 'rebuild_me_please', 'required' => TRUE]]]);

    $this->drupalGet('<front>');
    $this->assertHeader('container_rebuild_test_parameter', FALSE);

    // Ensure that after container invalidation the parameter is set.
    \Drupal::service('kernel')->invalidateContainer();
    $this->drupalGet('<front>');
    $this->assertHeader('container_rebuild_test_parameter', 'rebuild_me_please');
  }

}
