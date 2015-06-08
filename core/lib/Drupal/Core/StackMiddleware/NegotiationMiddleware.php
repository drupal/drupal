<?php

/**
 * @file
 * Contains \Drupal\Core\StackMiddleware\NegotationMiddleware.
 */

namespace Drupal\Core\StackMiddleware;

use Drupal\Core\ContentNegotiation;
use Drupal\Core\ContentNegotiationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides a middleware to determine the content type upon the accept header.
 *
 * @todo This is a temporary solution, remove this in https://www.drupal.org/node/2364011
 */
class NegotiationMiddleware implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $app;

  /**
   * The content negotiator.
   *
   * @var \Drupal\Core\ContentNegotiation
   */
  protected $negotiator;

  /**
   * Contains a hashmap of format as key and mimetype as value.
   *
   * @var array
   */
  protected $formats = [];

  /**
   * Constructs a new NegotiationMiddleware.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $app
   *   The wrapper HTTP kernel
   * @param \Drupal\Core\ContentNegotiation $negotiator
   *   The content negotiator.
   */
  public function __construct(HttpKernelInterface $app, ContentNegotiation $negotiator) {
    $this->app = $app;
    $this->negotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true) {
    // Register available mime types.
    foreach ($this->formats as $format => $mime_type) {
      $request->setFormat($format, $mime_type);
    }

    // Determine the request format using the negotiator.
    $request->setRequestFormat($this->negotiator->getContentType($request));
    return $this->app->handle($request, $type, $catch);
  }

  /**
   * Registers a format for a given MIME type.
   *
   * @param string $format
   *   The format.
   * @param string $mime_type
   *   The MIME type.
   *
   * @return $this
   */
  public function registerFormat($format, $mime_type) {
    $this->formats[$format] = $mime_type;
    return $this;
  }

}
