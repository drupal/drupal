<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\DefaultExceptionHtmlSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Page\HtmlFragment;
use Drupal\Core\Page\HtmlFragmentRendererInterface;
use Drupal\Core\Page\HtmlPageRendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Handle most HTTP errors for HTML.
 */
class DefaultExceptionHtmlSubscriber extends HttpExceptionSubscriberBase {
  use StringTranslationTrait;

  /**
   * The HTML fragment renderer.
   *
   * @var \Drupal\Core\Page\HtmlFragmentRendererInterface
   */
  protected $fragmentRenderer;

  /**
   * The HTML page renderer.
   *
   * @var \Drupal\Core\Page\HtmlPageRendererInterface
   */
  protected $htmlPageRenderer;

  /**
   * Constructs a new DefaultExceptionHtmlSubscriber.
   *
   * @param \Drupal\Core\Page\HtmlFragmentRendererInterface $fragment_renderer
   *   The fragment renderer.
   * @param \Drupal\Core\Page\HtmlPageRendererInterface $page_renderer
   *   The page renderer.
   */
  public function __construct(HtmlFragmentRendererInterface $fragment_renderer, HtmlPageRendererInterface $page_renderer) {
    $this->fragmentRenderer = $fragment_renderer;
    $this->htmlPageRenderer = $page_renderer;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    // A very low priority so that custom handlers are almost certain to fire
    // before it, even if someone forgets to set a priority.
    return -128;
  }

  /**
   * {@inheritDoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Handles a 403 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event) {
    $response = $this->createResponse($this->t('Access denied'), $this->t('You are not authorized to access this page.'), Response::HTTP_FORBIDDEN);
    $response->headers->set('Content-type', 'text/html');
    $event->setResponse($response);
  }

  /**
   * Handles a 404 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on404(GetResponseForExceptionEvent $event) {
    $path = $event->getRequest()->getPathInfo();
    $response = $this->createResponse($this->t('Page not found'), $this->t('The requested page "@path" could not be found.', ['@path' => $path]), Response::HTTP_NOT_FOUND);
    $response->headers->set('Content-type', 'text/html');
    $event->setResponse($response);
  }

  /**
   * Handles a 405 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on405(GetResponseForExceptionEvent $event) {
    $response = new Response('Method Not Allowed', Response::HTTP_METHOD_NOT_ALLOWED);
    $response->headers->set('Content-type', 'text/html');
    $event->setResponse($response);
  }

  /**
   * @param $title
   *   The page title of the response.
   * @param $body
   *   The body of the error page.
   * @param $response_code
   *   The HTTP response code of the response.
   * @return Response
   *   An error Response object ready to return to the browser.
   */
  protected function createResponse($title, $body, $response_code) {
    $fragment = new HtmlFragment($body);
    $fragment->setTitle($title);

    $page = $this->fragmentRenderer->render($fragment, $response_code);
    return new Response($this->htmlPageRenderer->render($page), $page->getStatusCode());
  }

}
