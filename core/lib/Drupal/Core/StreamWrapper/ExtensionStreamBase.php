<?php

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a base stream wrapper implementation.
 *
 * ExtensionStreamBase is a read-only Drupal stream wrapper base class for
 * system files located in extensions: modules, themes and installed profile.
 */
abstract class ExtensionStreamBase extends LocalReadOnlyStream {

  // @todo Move this in \Drupal\Core\StreamWrapper\LocalStream in Drupal 9.0.x.
  use StringTranslationTrait;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::LOCAL | StreamWrapperInterface::READ;
  }

  /**
   * Gets the module, theme, or profile name of the current URI.
   *
   * This will return the name of the module, theme or profile e.g.
   * @code SystemStream::getOwnerName('module://foo') @endcode and @code
   * SystemStream::getOwnerName('module://foo/')@endcode will both return @code
   * 'foo'@endcode
   *
   * @return string
   *   The extension name.
   */
  protected function getOwnerName(): string {
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
    return $this->getRequestStack()->getCurrentRequest()->getUriForPath($path);
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

    return "$scheme://{$this->getOwnerName()}{$dirname}";
  }

  /**
   * Returns the request stack object.
   *
   * @return \Symfony\Component\HttpFoundation\RequestStack
   *   The request stack object.
   */
  protected function getRequestStack() {
    return $this->requestStack;
  }

}
