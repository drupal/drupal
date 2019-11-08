<?php

namespace Drupal\Tests\system\Functional\DrupalKernel;

use Drupal\Tests\BrowserTestBase;

/**
 * Ensures that the container rebuild works as expected.
 *
 * @group DrupalKernel
 */
class ContainerRebuildWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['service_provider_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Sets a different deployment identifier.
   */
  public function testSetContainerRebuildWithDifferentDeploymentIdentifier() {
    $assert = $this->assertSession();

    // Ensure the parameter is not set.
    $this->drupalGet('<front>');
    $assert->responseHeaderEquals('container_rebuild_indicator', NULL);

    $this->writeSettings(['settings' => ['deployment_identifier' => (object) ['value' => 'new-identifier', 'required' => TRUE]]]);

    $this->drupalGet('<front>');

    $assert->responseHeaderEquals('container_rebuild_indicator', 'new-identifier');
  }

  /**
   * Tests container invalidation.
   */
  public function testContainerInvalidation() {
    $assert = $this->assertSession();

    // Ensure that parameter is not set.
    $this->drupalGet('<front>');
    $assert->responseHeaderEquals('container_rebuild_test_parameter', NULL);

    // Ensure that after setting the parameter, without a container rebuild the
    // parameter is still not set.
    $this->writeSettings(['settings' => ['container_rebuild_test_parameter' => (object) ['value' => 'rebuild_me_please', 'required' => TRUE]]]);

    $this->drupalGet('<front>');
    $assert->responseHeaderEquals('container_rebuild_test_parameter', NULL);

    // Ensure that after container invalidation the parameter is set.
    \Drupal::service('kernel')->invalidateContainer();
    $this->drupalGet('<front>');
    $assert->responseHeaderEquals('container_rebuild_test_parameter', 'rebuild_me_please');
  }

}
