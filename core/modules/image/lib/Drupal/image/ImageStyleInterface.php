<?php

/**
 * @file
 * Contains \Drupal\image\ImageStyleInterface.
 */

namespace Drupal\image;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an image style entity.
 */
interface ImageStyleInterface extends ConfigEntityInterface {

  /**
   * Delivers an image derivative.
   *
   * Transfers a generated image derivative to the requesting agent. Modules may
   * implement this method to set different serve different image derivatives
   * from different stream wrappers or to customize different permissions on
   * each image style.
   *
   * @param string $scheme
   *   The scheme name of the original image file stream wrapper ('public',
   *   'private', 'temporary', etc.).
   * @param string $target
   *   The target part of the uri.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\Response
   *   The image to be delivered.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException
   *
   * @todo Move to controller after https://drupal.org/node/1987712.
   */
  public function deliver($scheme, $target);

  /**
   * Returns the URI of this image when using this style.
   *
   * The path returned by this function may not exist. The default generation
   * method only creates images when they are requested by a user's browser.
   * Modules may implement this method to decide where to place derivatives.
   *
   * @param string $uri
   *   The URI or path to the original image.
   *
   * @return string
   *   The URI to the image derivative for this style.
   */
  public function buildUri($uri);

  /**
   * Returns the URL of this image derivative for an original image path or URI.
   *
   * @param string $path
   *   The path or URI to the original image.
   * @param mixed $clean_urls
   *   (optional) Whether clean URLs are in use.
   *
   * @return string
   *   The absolute URL where a style image can be downloaded, suitable for use
   *   in an <img> tag. Requesting the URL will cause the image to be created.
   *
   * @see \Drupal\image\ImageStyleInterface::deliver()
   */
  public function buildUrl($path, $clean_urls = NULL);

  /**
   * Flushes cached media for this style.
   *
   * @param string $path
   *   (optional) The original image path or URI. If it's supplied, only this
   *   image derivative will be flushed.
   */
  public function flush($path = NULL);

  /**
   * Creates a new image derivative based on this image style.
   *
   * Generates an image derivative applying all image effects and saving the
   * resulting image.
   *
   * @param string $original_uri
   *   Original image file URI.
   * @param string $derivative_uri
   *   Derivative image file URI.
   *
   * @return bool
   *   TRUE if an image derivative was generated, or FALSE if the image
   *   derivative could not be generated.
   */
  public function createDerivative($original_uri, $derivative_uri);

  /**
   * Determines the dimensions of this image style.
   *
   * Stores the dimensions of this image style into $dimensions associative
   * array. Implementations have to provide at least values to next keys:
   * - width: Integer with the derivative image width.
   * - height: Integer with the derivative image height.
   *
   * @param array $dimensions
   *   Associative array passed by reference. Implementations have to store the
   *   resulting width and height, in pixels.
   */
  public function transformDimensions(array &$dimensions);

}
