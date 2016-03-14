<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\CustomPageExceptionHtmlSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Exception subscriber for handling core custom HTML error pages.
 */
class CustomPageExceptionHtmlSubscriber extends DefaultExceptionHtmlSubscriber {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new CustomPageExceptionHtmlSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP Kernel service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Symfony\Component\Routing\Matcher\UrlMatcherInterface $access_unaware_router
   *   A router implementation which does not check access.
   */
  public function __construct(ConfigFactoryInterface $config_factory, HttpKernelInterface $http_kernel, LoggerInterface $logger, RedirectDestinationInterface $redirect_destination, UrlMatcherInterface $access_unaware_router) {
    parent::__construct($http_kernel, $logger, $redirect_destination, $access_unaware_router);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    return -50;
  }

  /**
   * {@inheritdoc}
   */
  public function on403(GetResponseForExceptionEvent $event) {
    $custom_403_path = $this->configFactory->get('system.site')->get('page.403');
    if (!empty($custom_403_path)) {
      $this->makeSubrequest($event, $custom_403_path, Response::HTTP_FORBIDDEN);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function on404(GetResponseForExceptionEvent $event) {
    $custom_404_path = $this->configFactory->get('system.site')->get('page.404');
    if (!empty($custom_404_path)) {
      $this->makeSubrequest($event, $custom_404_path, Response::HTTP_NOT_FOUND);
    }
  }

}
