<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Routing;

use Drupal\Core\Routing\PathChangedHelper;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the PathChangedHelper class.
 *
 * @coversDefaultClass \Drupal\Core\Routing\PathChangedHelper
 * @group Routing
 */
class PathChangedHelperTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['path_changed_helper_test', 'system'];

  /**
   * Tests creating a PathChangedHelper object and getting paths.
   *
   * @covers ::__construct
   * @covers ::oldPath
   * @covers ::newPath
   * @covers ::redirect
   */
  public function testPathChangedHelper(): void {
    $route = \Drupal::service('router.route_provider')->getRouteByName('path.changed.bc');
    $raw_parameters = [
      'block_type' => 'test_block_type',
    ];
    $query = [
      'destination' => 'admin/structure/block',
      'plugin_id' => 'some_block_config',
    ];
    $helper = new PathChangedHelper(
      new RouteMatch('path.changed.bc', $route, [], $raw_parameters),
      new Request($query)
    );

    // Assert that oldPath() returns the internal path for path.changed.bc.
    $this->assertEquals('old/path/test_block_type', $helper->oldPath());
    // Assert that newPath() returns the internal path for path.changed.
    $this->assertEquals('new/path/test_block_type', $helper->newPath());
    // Assert that redirect() returns a RedirectResponse for the absolute URL of
    // path.changed, and the query string comes from the Request object with the
    // destination parameter removed.
    $redirect = $helper->redirect();
    $this->assertInstanceOf(RedirectResponse::class, $redirect);
    $this->assertEquals(301, $redirect->getStatusCode());
    $base_path = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $this->assertEquals($base_path . 'new/path/test_block_type?plugin_id=some_block_config', $redirect->getTargetUrl());
  }

}
