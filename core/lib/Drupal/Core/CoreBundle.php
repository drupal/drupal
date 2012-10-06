<?php

/**
 * @file
 * Definition of Drupal\Core\CoreBundle.
 */

namespace Drupal\Core;

use Drupal\Core\DependencyInjection\Compiler\RegisterKernelListenersPass;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

use Drupal\Core\Database\Database;

/**
 * Bundle class for mandatory core services.
 *
 * This is where Drupal core registers all of its services to the Dependency
 * Injection Container. Modules wishing to register services to the container
 * should extend Symfony's Bundle class directly, not this class.
 */
class CoreBundle extends Bundle
{
  public function build(ContainerBuilder $container) {

    // The 'request' scope and service enable services to depend on the Request
    // object and get reconstructed when the request object changes (e.g.,
    // during a subrequest).
    $container->addScope(new Scope('request'));
    $container->register('request', 'Symfony\Component\HttpFoundation\Request')
      ->setSynthetic(TRUE);

    $container->register('dispatcher', 'Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher')
      ->addArgument(new Reference('service_container'));
    $container->register('resolver', 'Drupal\Core\ControllerResolver')
      ->addArgument(new Reference('service_container'));
    $container->register('http_kernel', 'Drupal\Core\HttpKernel')
      ->addArgument(new Reference('dispatcher'))
      ->addArgument(new Reference('service_container'))
      ->addArgument(new Reference('resolver'));
    $container->register('language_manager', 'Drupal\Core\Language\LanguageManager')
      ->addArgument(new Reference('request'))
      ->setScope('request');
    $container->register('database', 'Drupal\Core\Database\Connection')
      ->setFactoryClass('Drupal\Core\Database\Database')
      ->setFactoryMethod('getConnection')
      ->addArgument('default');
    $container->register('database.slave', 'Drupal\Core\Database\Connection')
      ->setFactoryClass('Drupal\Core\Database\Database')
      ->setFactoryMethod('getConnection')
      ->addArgument('slave');
    $container->register('typed_data', 'Drupal\Core\TypedData\TypedDataManager');
    // Add the user's storage for temporary, non-cache data.
    $container->register('lock', 'Drupal\Core\Lock\DatabaseLockBackend');
    $container->register('user.tempstore', 'Drupal\user\TempStoreFactory')
      ->addArgument(new Reference('database'))
      ->addArgument(new Reference('lock'));

    $container->register('router.dumper', '\Drupal\Core\Routing\MatcherDumper')
      ->addArgument(new Reference('database'));
    $container->register('router.builder', 'Drupal\Core\Routing\RouteBuilder')
      ->addArgument(new Reference('router.dumper'));

    // @todo Replace below lines with the commented out block below it when it's
    //   performant to do so: http://drupal.org/node/1706064.
    $dispatcher = $container->get('dispatcher');
    $matcher = new \Drupal\Core\Routing\ChainMatcher();
    $matcher->add(new \Drupal\Core\LegacyUrlMatcher());

    $nested = new \Drupal\Core\Routing\NestedMatcher();
    $nested->setInitialMatcher(new \Drupal\Core\Routing\PathMatcher(Database::getConnection()));
    $nested->addPartialMatcher(new \Drupal\Core\Routing\HttpMethodMatcher());
    $nested->setFinalMatcher(new \Drupal\Core\Routing\FirstEntryFinalMatcher());
    $matcher->add($nested, 5);

    $content_negotation = new \Drupal\Core\ContentNegotiation();
    $dispatcher->addSubscriber(new \Symfony\Component\HttpKernel\EventListener\RouterListener($matcher));
    $dispatcher->addSubscriber(new \Drupal\Core\EventSubscriber\ViewSubscriber($content_negotation));
    $dispatcher->addSubscriber(new \Drupal\Core\EventSubscriber\AccessSubscriber());
    $dispatcher->addSubscriber(new \Drupal\Core\EventSubscriber\MaintenanceModeSubscriber());
    $dispatcher->addSubscriber(new \Drupal\Core\EventSubscriber\PathSubscriber());
    $dispatcher->addSubscriber(new \Drupal\Core\EventSubscriber\LegacyRequestSubscriber());
    $dispatcher->addSubscriber(new \Drupal\Core\EventSubscriber\LegacyControllerSubscriber());
    $dispatcher->addSubscriber(new \Drupal\Core\EventSubscriber\FinishResponseSubscriber());
    $dispatcher->addSubscriber(new \Drupal\Core\EventSubscriber\RequestCloseSubscriber());
    $dispatcher->addSubscriber(new \Drupal\Core\EventSubscriber\ConfigGlobalOverrideSubscriber());
    $dispatcher->addSubscriber(new \Drupal\Core\EventSubscriber\RouteProcessorSubscriber());
    $container->set('content_negotiation', $content_negotation);
    $dispatcher->addSubscriber(\Drupal\Core\ExceptionController::getExceptionListener($container));

    /*
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
      ->addArgument(new Reference('language_manager'))
      ->setScope('request')
      ->addTag('kernel.event_subscriber');
    $container->register('request_close_subscriber', 'Drupal\Core\EventSubscriber\RequestCloseSubscriber')
      ->addTag('kernel.event_subscriber');
    $container->register('config_global_override_subscriber', '\Drupal\Core\EventSubscriber\ConfigGlobalOverrideSubscriber');
    $container->register('exception_listener', 'Symfony\Component\HttpKernel\EventListener\ExceptionListener')
      ->addTag('kernel.event_subscriber')
      ->addArgument(new Reference('service_container'))
      ->setFactoryClass('Drupal\Core\ExceptionController')
      ->setFactoryMethod('getExceptionListener');

    // Add a compiler pass for registering event subscribers.
    $container->addCompilerPass(new RegisterKernelListenersPass(), PassConfig::TYPE_AFTER_REMOVING);
    */
  }
}
