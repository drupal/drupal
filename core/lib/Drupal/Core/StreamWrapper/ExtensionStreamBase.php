<?php

namespace Drupal\Core\StreamWrapper;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a base stream wrapper implementation.
 *
 * ExtensionStreamBase is a read-only Drupal stream wrapper base class for
 * system files located in extensions: modules, themes and installed profile.
 */
abstract class ExtensionStreamBase extends LocalReadOnlyStream {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::LOCAL | StreamWrapperInterface::READ;
  }

  /**
   * Gets the module, theme, or profile name from the URI.
   *
   * This will return the name of the module, theme or profile e.g.:
   * @code
   * ModuleStream::getExtensionName('module://foo')
   * @endcode
   * and
   * @code
   * ModuleStream::getExtensionName('module://foo/')
   * @endcode
   * will both return
   * @code
   * 'foo'
   * @endcode
   *
   * @return string
   *   The extension name.
   */
  protected function getExtensionName(): string {
    $uri_parts = explode('://', $this->uri, 2);
    return strtok($uri_parts[1], '/');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTarget($uri = NULL) {
    if ($target = strstr(parent::getTarget($uri), '/')) {
      return trim($target, '/');
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $dir = $this->getDirectoryPath();
    if (empty($dir)) {
      throw new \RuntimeException("Extension directory for {$this->uri} does not exist.");
    }
    $path = rtrim(base_path() . $dir . '/' . $this->getTarget(), '/');
    return $this->getRequest()->getUriForPath($path);
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }
    else {
      $this->uri = $uri;
    }

    list($scheme) = explode('://', $uri, 2);
    $dirname = dirname($this->getTarget($uri));
    $dirname = $dirname !== '.' ? rtrim("/$dirname", '/') : '';

    return "$scheme://{$this->getExtensionName()}{$dirname}";
  }

  /**
   * Returns the current request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The current request object.
   */
  protected function getRequest(): Request {
    if (!isset($this->request)) {
      $this->request = \Drupal::service('request_stack')->getCurrentRequest();
    }
    return $this->request;
  }

}
