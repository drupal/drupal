<?php

namespace Drupal\big_pipe\Render;

use Drupal\Core\Render\HtmlResponse;

/**
 * A response that is sent in chunks by the BigPipe service.
 *
 * Note we cannot use \Symfony\Component\HttpFoundation\StreamedResponse because
 * it makes the content inaccessible (hidden behind a callback), which means no
 * middlewares are able to modify the content anymore.
 *
 * @see \Drupal\big_pipe\Render\BigPipe
 *
 * @internal
 *   This is a temporary solution until a generic response emitter interface is
 *   created in https://www.drupal.org/node/2577631. Only code internal to
 *   BigPipe should instantiate or type hint to this class.
 */
class BigPipeResponse extends HtmlResponse {

  /**
   * The BigPipe service.
   *
   * @var \Drupal\big_pipe\Render\BigPipe
   */
  protected $bigPipe;

  /**
   * The original HTML response.
   *
   * Still contains placeholders. Its cacheability metadata and attachments are
   * for everything except the placeholders (since those are not yet rendered).
   *
   * @see \Drupal\Core\Render\StreamedResponseInterface
   * @see ::getStreamedResponse()
   *
   * @var \Drupal\Core\Render\HtmlResponse
   */
  protected $originalHtmlResponse;

  /**
   * Constructs a new BigPipeResponse.
   *
   * @param \Drupal\Core\Render\HtmlResponse $response
   *   The original HTML response.
   */
  public function __construct(HtmlResponse $response) {
    parent::__construct('', $response->getStatusCode(), []);

    $this->originalHtmlResponse = $response;

    $this->populateBasedOnOriginalHtmlResponse();
  }

  /**
   * Returns the original HTML response.
   *
   * @return \Drupal\Core\Render\HtmlResponse
   *   The original HTML response.
   */
  public function getOriginalHtmlResponse() {
    return $this->originalHtmlResponse;
  }

  /**
   * Populates this BigPipeResponse object based on the original HTML response.
   */
  protected function populateBasedOnOriginalHtmlResponse() {
    // Clone the HtmlResponse's data into the new BigPipeResponse.
    $this->headers = clone $this->originalHtmlResponse->headers;
    $this
      ->setStatusCode($this->originalHtmlResponse->getStatusCode())
      ->setContent($this->originalHtmlResponse->getContent())
      ->setAttachments($this->originalHtmlResponse->getAttachments())
      ->addCacheableDependency($this->originalHtmlResponse->getCacheableMetadata());

    // A BigPipe response can never be cached, because it is intended for a
    // single user.
    // @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.1
    $this->setPrivate();

    // Inform surrogates how they should handle BigPipe responses:
    // - "no-store" specifies that the response should not be stored in cache;
    //   it is only to be used for the original request
    // - "content" identifies what processing surrogates should perform on the
    //   response before forwarding it. We send, "BigPipe/1.0", which surrogates
    //   should not process at all, and in fact, they should not even buffer it
    //   at all.
    // @see http://www.w3.org/TR/edge-arch/
    $this->headers->set('Surrogate-Control', 'no-store, content="BigPipe/1.0"');

    // Add header to support streaming on NGINX + php-fpm (nginx >= 1.5.6).
    $this->headers->set('X-Accel-Buffering', 'no');
  }

  /**
   * Sets the BigPipe service to use.
   *
   * @param \Drupal\big_pipe\Render\BigPipe $big_pipe
   *   The BigPipe service.
   */
  public function setBigPipeService(BigPipe $big_pipe) {
    $this->bigPipe = $big_pipe;
  }

  /**
   * {@inheritdoc}
   */
  public function sendContent() {
    $this->bigPipe->sendContent($this);

    // All BigPipe placeholders are processed, so update this response's
    // attachments.
    unset($this->attachments['big_pipe_placeholders']);
    unset($this->attachments['big_pipe_nojs_placeholders']);

    return $this;
  }

}
