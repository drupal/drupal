<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
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
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

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
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, HttpKernelInterface $http_kernel, LoggerInterface $logger, RedirectDestinationInterface $redirect_destination, UrlMatcherInterface $access_unaware_router, AccessManagerInterface $access_manager) {
    parent::__construct($http_kernel, $logger, $redirect_destination, $access_unaware_router);
    $this->configFactory = $config_factory;
    $this->accessManager = $access_manager;
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
  public function on403(ExceptionEvent $event) {
    $custom_403_path = $this->configFactory->get('system.site')->get('page.403');
    if (!empty($custom_403_path)) {
      $this->makeSubrequestToCustomPath($event, $custom_403_path, Response::HTTP_FORBIDDEN);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function on404(ExceptionEvent $event) {
    $custom_404_path = $this->configFactory->get('system.site')->get('page.404');
    if (!empty($custom_404_path)) {
      $this->makeSubrequestToCustomPath($event, $custom_404_path, Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Makes a subrequest to retrieve the custom error page.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event to process.
   * @param string $custom_path
   *   The custom path to which to make a subrequest for this error message.
   * @param int $status_code
   *   The status code for the error being handled.
   */
  protected function makeSubrequestToCustomPath(ExceptionEvent $event, $custom_path, $status_code) {
    $url = Url::fromUserInput($custom_path);
    if ($url->isRouted()) {
      $access_result = $this->accessManager->checkNamedRoute($url->getRouteName(), $url->getRouteParameters(), NULL, TRUE);
      $request = $event->getRequest();

      // Merge the custom path's route's access result's cacheability metadata
      // with the existing one (from the master request), otherwise create it.
      if (!$request->attributes->has(AccessAwareRouterInterface::ACCESS_RESULT)) {
        $request->attributes->set(AccessAwareRouterInterface::ACCESS_RESULT, $access_result);
      }
      else {
        $existing_access_result = $request->attributes->get(AccessAwareRouterInterface::ACCESS_RESULT);
        if ($existing_access_result instanceof RefinableCacheableDependencyInterface) {
          $existing_access_result->addCacheableDependency($access_result);
        }
      }

      // Only perform the subrequest if the custom path is actually accessible.
      if (!$access_result->isAllowed()) {
        return;
      }
    }

    $this->makeSubrequest($event, $custom_path, $status_code);
  }

}
