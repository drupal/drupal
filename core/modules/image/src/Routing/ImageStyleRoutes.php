<?php

namespace Drupal\image\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a route subscriber to register a url for serving image styles.
 */
class ImageStyleRoutes implements ContainerInjectionInterface {

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a new PathProcessorImageStyles object.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager service.
   */
  public function __construct(StreamWrapperManagerInterface $stream_wrapper_manager) {
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

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
  public function routes() {
    $routes = [];
    // Generate image derivatives of publicly available files. If clean URLs are
    // disabled image derivatives will always be served through the menu system.
    // If clean URLs are enabled and the image derivative already exists, PHP
    // will be bypassed.
    $directory_path = $this->streamWrapperManager->getViaScheme('public')->getDirectoryPath();

    $routes['image.style_public'] = new Route(
      '/' . $directory_path . '/styles/{image_style}/{scheme}',
      [
        '_controller' => 'Drupal\image\Controller\ImageStyleDownloadController::deliver',
      ],
      [
        '_access' => 'TRUE',
      ]
    );
    return $routes;
  }

}
