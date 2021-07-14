<?php

namespace Drupal\Core\StackMiddleware;

use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides a middleware to determine the content type.
 *
 * Due to historically poor support for header-based content negotiation by user
 * agents, reverse proxies and other infrastructure, Drupal supports the use of
 * a `_format` query parameter for expressing a content type that would
 * otherwise be sent in an `accept` HTTP header.
 *
 * @see https://www.drupal.org/node/2501221
 *
 * For certain applications (JSON:API, for instance) developers may wish to
 * utilize the `accept` header for content negotiation; this behaviour can be
 * disabled by configuring the `content_negotiation.config.enabled` container
 * parameter.
 */
class NegotiationMiddleware implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $app;

  /**
   * The content negotiation config.
   *
   * @var array
   */
  protected $contentNegotiationConfig;

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
   * Sets the content negotiation config.
   *
   * @param array $content_negotiation_config
   *   The content negotiation configuration container parameter.
   */
  public function setContentNegotiationConfig(array $content_negotiation_config) {
    $this->contentNegotiationConfig = $content_negotiation_config;
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
    if ($requested_format = $this->getContentType($request)) {
      $request->setRequestFormat($requested_format);
    }
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
    if ($request->request->get('ajax_iframe_upload', FALSE)) {
      return 'iframeupload';
    }

    if ($request->query->has('_format')) {
      return $request->query->get('_format');
    }

    if ($this->contentNegotiationConfig['headers']['accept'] ?? FALSE) {
      $accept = AcceptHeader::fromString($request->headers->get('accept'));
      if (count($accept->all()) === 1 && $format = $request->getFormat($accept->first()->getValue())) {
        return $format;
      }
    }

    // No format was specified in the request.
    return NULL;
  }

}
