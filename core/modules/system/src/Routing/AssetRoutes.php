<?php

namespace Drupal\system\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a routes' callback to register a URL for serving assets.
 */
class AssetRoutes implements ContainerInjectionInterface {

  /**
   * Constructs an asset routes object.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   The stream wrapper manager service.
   */
  public function __construct(
    protected readonly StreamWrapperManagerInterface $streamWrapperManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes(): array {
    $routes = [];
    // Generate assets. If clean URLs are disabled image derivatives will always
    // be served through the routing system. If clean URLs are enabled and the
    // image derivative already exists, PHP will be bypassed.
    $directory_path = $this->streamWrapperManager->getViaScheme('assets')->getDirectoryPath();

    $routes['system.css_asset'] = new Route(
      '/' . $directory_path . '/css/{file_name}',
      [
        '_controller' => 'Drupal\system\Controller\CssAssetController::deliver',
      ],
      [
        '_access' => 'TRUE',
      ]
    );
    $routes['system.js_asset'] = new Route(
      '/' . $directory_path . '/js/{file_name}',
      [
        '_controller' => 'Drupal\system\Controller\JsAssetController::deliver',
      ],
      [
        '_access' => 'TRUE',
      ]
    );
    return $routes;
  }

}
