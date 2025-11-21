<?php

declare(strict_types=1);

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Routing\RequestContext;

/**
 * Defines a base stream wrapper implementation for extension assets.
 */
abstract class ExtensionStreamBase extends LocalReadOnlyStream {

  /**
   * {@inheritdoc}
   */
  public static function getType(): int {
    return StreamWrapperInterface::LOCAL | StreamWrapperInterface::READ;
  }

  /**
   * {@inheritdoc}
   */
  public function setUri($uri): void {
    if (!str_contains($uri, '://')) {
      throw new \InvalidArgumentException("Malformed extension URI: {$uri}");
    }
    $this->checkFileExtension($uri);
    $this->uri = $uri;
  }

  /**
   * Gets the extension name from the URI.
   *
   * @return string
   *   The extension name.
   */
  protected function getExtensionName(): string {
    $uri_parts = explode('://', $this->uri, 2);
    $extension_name = strtok($uri_parts[1], '/');
    // Any string that evaluates to empty is considered an invalid extension
    // name.
    if (empty($extension_name)) {
      throw new \RuntimeException("Unable to determine the extension name.");
    }
    return $extension_name;
  }

  /**
   * Gets the extension object.
   *
   * @param string $extension_name
   *   The extension name.
   *
   * @return \Drupal\Core\Extension\Extension
   *   The extension object.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   Thrown when the extension is missing.
   */
  abstract protected function getExtension(string $extension_name): Extension;

  /**
   * {@inheritdoc}
   */
  protected function getTarget($uri = NULL): string {
    if ($target = strstr(parent::getTarget($uri), '/')) {
      $this->checkFileExtension($uri ?? $this->uri);
      return trim($target, '/');
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl(): string {
    $dir = $this->getDirectoryPath();
    return \Drupal::service(RequestContext::class)->getCompleteBaseUrl() . rtrim("/$dir/" . $this->getTarget(), '/');
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri = NULL): string {
    if (isset($uri)) {
      $this->setUri($uri);
    }
    else {
      $uri = $this->uri;
    }
    [$scheme] = explode('://', $uri, 2);
    $dirname = dirname($this->getTarget($uri));
    $dirname = $dirname !== '.' ? rtrim("/$dirname", '/') : '';

    // Call the getExtension() method to ensure the extension exists.
    $extension = $this->getExtension($this->getExtensionName());
    return "$scheme://{$extension->getName()}{$dirname}";
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    $extension_name = $this->getExtensionName();
    return $this->getExtension($extension_name)->getPath();
  }

  /**
   * Checks that the given URI has an allowed file extension.
   *
   * This checks the `stream_wrapper.allowed_file_extensions` container
   * parameter, which lists all file extensions allowed for different URI
   * schemes. If there is no list for the given scheme, then the file is assumed
   * to be disallowed.
   *
   * @param string $uri
   *   A URI to check.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the given URI has a file extension that is not allowed by the
   *   container parameter.
   */
  protected function checkFileExtension(string $uri): void {
    [$scheme] = explode('://', $uri, 2);

    $allowed = \Drupal::getContainer()
      ->getParameter('stream_wrapper.allowed_file_extensions');

    $extension = pathinfo($uri, PATHINFO_EXTENSION);
    if (isset($allowed[$scheme]) && in_array(strtolower($extension), $allowed[$scheme], TRUE)) {
      return;
    }
    throw new \InvalidArgumentException("The $scheme stream wrapper does not support the '$extension' file type.");
  }

}
