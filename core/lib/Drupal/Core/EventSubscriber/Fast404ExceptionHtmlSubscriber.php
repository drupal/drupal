<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\Fast404ExceptionHtmlSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * High-performance 404 exception subscriber.
 *
 * This subscriber will return a minimalist 404 response for HTML requests
 * without running a full page theming operation.
 */
class Fast404ExceptionHtmlSubscriber extends HttpExceptionSubscriberBase {

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new Fast404ExceptionHtmlSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP Kernel service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, HttpKernelInterface $http_kernel) {
    $this->configFactory = $config_factory;
    $this->httpKernel = $http_kernel;
  }


  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    // A very high priority so that it can take precedent over anything else,
    // and thus be fast.
    return 200;
  }

  /**
   * {@inheritDoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Handles a 404 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on404(GetResponseForExceptionEvent $event) {
    $request = $event->getRequest();

    $config = $this->configFactory->get('system.performance');
    $exclude_paths = $config->get('fast_404.exclude_paths');
    if ($config->get('fast_404.enabled') && $exclude_paths && !preg_match($exclude_paths, $request->getPathInfo())) {
      $fast_paths = $config->get('fast_404.paths');
      if ($fast_paths && preg_match($fast_paths, $request->getPathInfo())) {
        $fast_404_html = strtr($config->get('fast_404.html'), ['@path' => Html::escape($request->getUri())]);
        $response = new Response($fast_404_html, Response::HTTP_NOT_FOUND);
        $event->setResponse($response);
      }
    }
  }

}
