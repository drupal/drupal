<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\DefaultExceptionHtmlSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
   * The page alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a new CustomPageExceptionHtmlSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager service.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP Kernel service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, HttpKernelInterface $http_kernel, LoggerInterface $logger, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($http_kernel, $logger, $redirect_destination);
    $this->configFactory = $config_factory;
    $this->aliasManager = $alias_manager;
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
    $path = $this->aliasManager->getPathByAlias($this->configFactory->get('system.site')->get('page.403'));
    $this->makeSubrequest($event, $path, Response::HTTP_FORBIDDEN);
  }

  /**
   * {@inheritdoc}
   */
  public function on404(GetResponseForExceptionEvent $event) {
    $path = $this->aliasManager->getPathByAlias($this->configFactory->get('system.site')->get('page.404'));
    $this->makeSubrequest($event, $path, Response::HTTP_NOT_FOUND);
  }

}
