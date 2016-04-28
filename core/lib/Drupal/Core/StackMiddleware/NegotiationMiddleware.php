<?php

namespace Drupal\Core\StackMiddleware;

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
   */
  public function __construct(HttpKernelInterface $app) {
    $this->app = $app;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    // Register available mime types.
    foreach ($this->formats as $format => $mime_type) {
      $request->setFormat($format, $mime_type);
    }

    // Determine the request format using the negotiator.
    $request->setRequestFormat($this->getContentType($request));
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

  /**
   * Gets the normalized type of a request.
   *
   * The normalized type is a short, lowercase version of the format, such as
   * 'html', 'json' or 'atom'.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object from which to extract the content type.
   *
   * @return string
   *   The normalized type of a given request.
   */
  protected function getContentType(Request $request) {
    // AJAX iframe uploads need special handling, because they contain a JSON
    // response wrapped in <textarea>.
    if ($request->get('ajax_iframe_upload', FALSE)) {
      return 'iframeupload';
    }

    if ($request->query->has('_format')) {
      return $request->query->get('_format');
    }

    // Do HTML last so that it always wins.
    return 'html';
  }

}
