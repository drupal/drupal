<?php

namespace Drupal\Core;

use Drupal\Core\DependencyInjection\Compiler\RegisterKernelListenersPass;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

/**
 * This is where Drupal core registers all of its services to the Dependency
 * Injection Container. Modules wishing to register services to the container
 * should extend Symfony's Bundle class directly, not this class.
 */
class CoreBundle extends Bundle
{
  public function build(ContainerBuilder $container) {

    $container->addScope(new Scope('request'));

    $container->register('dispatcher', 'Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher')
      ->addArgument(new Reference('service_container'));
    $container->register('resolver', 'Symfony\Component\HttpKernel\Controller\ControllerResolver');
    $container->register('http_kernel', 'Drupal\Core\HttpKernel')
      ->addArgument(new Reference('dispatcher'))
      ->addArgument(new Reference('service_container'))
      ->addArgument(new Reference('resolver'));
    $container->register('matcher', 'Drupal\Core\LegacyUrlMatcher');
    $container->register('router_listener', 'Drupal\Core\EventSubscriber\RouterListener')
      ->addArgument(new Reference('matcher'))
      ->addTag('kernel.event_subscriber');
    $container->register('content_negotiation', 'Drupal\Core\ContentNegotiation');
    $container->register('view_subscriber', 'Drupal\Core\EventSubscriber\ViewSubscriber')
      ->addArgument(new Reference('content_negotiation'))
      ->addTag('kernel.event_subscriber');
    $container->register('access_subscriber', 'Drupal\Core\EventSubscriber\AccessSubscriber')
      ->addTag('kernel.event_subscriber');
    $container->register('maintenance_mode_subscriber', 'Drupal\Core\EventSubscriber\MaintenanceModeSubscriber')
      ->addTag('kernel.event_subscriber');
    $container->register('path_subscriber', 'Drupal\Core\EventSubscriber\PathSubscriber')
      ->addTag('kernel.event_subscriber');
    $container->register('legacy_request_subscriber', 'Drupal\Core\EventSubscriber\LegacyRequestSubscriber')
      ->addTag('kernel.event_subscriber');
    $container->register('legacy_controller_subscriber', 'Drupal\Core\EventSubscriber\LegacyControllerSubscriber')
      ->addTag('kernel.event_subscriber');
    $container->register('finish_response_subscriber', 'Drupal\Core\EventSubscriber\FinishResponseSubscriber')
      ->addTag('kernel.event_subscriber');
    $container->register('request_close_subscriber', 'Drupal\Core\EventSubscriber\RequestCloseSubscriber')
      ->addTag('kernel.event_subscriber');
    $container->register('database', 'Drupal\Core\Database\Connection')
      ->setFactoryClass('Drupal\Core\Database\Database')
      ->setFactoryMethod('getConnection')
      ->addArgument('default');
    $container->register('database.slave', 'Drupal\Core\Database\Connection')
      ->setFactoryClass('Drupal\Core\Database\Database')
      ->setFactoryMethod('getConnection')
      ->addArgument('slave');
    $container->register('exception_listener', 'Symfony\Component\HttpKernel\EventListener\ExceptionListener')
      ->addTag('kernel.event_subscriber')
      ->addArgument(new Reference('service_container'))
      ->setFactoryClass('Drupal\Core\ExceptionController')
      ->setFactoryMethod('getExceptionListener');
    $container->register('request', 'Symfony\Component\HttpFoundation\Request')
      ->setSynthetic(TRUE);
    $container->register('language_manager', 'Drupal\Core\Language\LanguageManager')
      ->addArgument(new Reference('request'))
      ->setScope('request');

    // Add a compiler pass for registering event subscribers.
    $container->addCompilerPass(new RegisterKernelListenersPass(), PassConfig::TYPE_AFTER_REMOVING);
  }
}
