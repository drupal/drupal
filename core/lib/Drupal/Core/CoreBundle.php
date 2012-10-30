<?php

/**
 * @file
 * Definition of Drupal\Core\CoreBundle.
 */

namespace Drupal\Core;

use Drupal\Core\DependencyInjection\Compiler\RegisterKernelListenersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterMatchersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterNestedMatchersPass;
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

    // Add the entity query factory.
    $container->register('entity.query', 'Drupal\Core\Entity\Query\QueryFactory')
      ->addArgument(new Reference('service_container'));

    $container->register('router.dumper', 'Drupal\Core\Routing\MatcherDumper')
      ->addArgument(new Reference('database'));
    $container->register('router.builder', 'Drupal\Core\Routing\RouteBuilder')
      ->addArgument(new Reference('router.dumper'))
      ->addArgument(new Reference('lock'));

    $container->register('matcher', 'Drupal\Core\Routing\ChainMatcher');
    $container->register('legacy_url_matcher', 'Drupal\Core\LegacyUrlMatcher')
      ->addTag('chained_matcher');
    $container->register('nested_matcher', 'Drupal\Core\Routing\NestedMatcher')
      ->addTag('chained_matcher', array('priority' => 5));

    // The following services are tagged as 'nested_matcher' services and are
    // processed in the RegisterNestedMatchersPass compiler pass. Each one
    // needs to be set on the matcher using a different method, so we use a
    // tag attribute, 'method', which can be retrieved and passed to the
    // addMethodCall() method that gets called on the matcher service in the
    // compiler pass.
    $container->register('path_matcher', 'Drupal\Core\Routing\PathMatcher')
      ->addArgument(new Reference('database'))
      ->addTag('nested_matcher', array('method' => 'setInitialMatcher'));
    $container->register('http_method_matcher', 'Drupal\Core\Routing\HttpMethodMatcher')
      ->addTag('nested_matcher', array('method' => 'addPartialMatcher'));
    $container->register('first_entry_final_matcher', 'Drupal\Core\Routing\FirstEntryFinalMatcher')
      ->addTag('nested_matcher', array('method' => 'setFinalMatcher'));

    $container->register('router_processor_subscriber', 'Drupal\Core\EventSubscriber\RouteProcessorSubscriber')
      ->addTag('event_subscriber');
    $container->register('router_listener', 'Symfony\Component\HttpKernel\EventListener\RouterListener')
      ->addArgument(new Reference('matcher'))
      ->addTag('event_subscriber');
    $container->register('content_negotiation', 'Drupal\Core\ContentNegotiation');
    $container->register('view_subscriber', 'Drupal\Core\EventSubscriber\ViewSubscriber')
      ->addArgument(new Reference('content_negotiation'))
      ->addTag('event_subscriber');
    $container->register('access_subscriber', 'Drupal\Core\EventSubscriber\AccessSubscriber')
      ->addTag('event_subscriber');
    $container->register('maintenance_mode_subscriber', 'Drupal\Core\EventSubscriber\MaintenanceModeSubscriber')
      ->addTag('event_subscriber');
    $container->register('path_subscriber', 'Drupal\Core\EventSubscriber\PathSubscriber')
      ->addTag('event_subscriber');
    $container->register('legacy_request_subscriber', 'Drupal\Core\EventSubscriber\LegacyRequestSubscriber')
      ->addTag('event_subscriber');
    $container->register('legacy_controller_subscriber', 'Drupal\Core\EventSubscriber\LegacyControllerSubscriber')
      ->addTag('event_subscriber');
    $container->register('finish_response_subscriber', 'Drupal\Core\EventSubscriber\FinishResponseSubscriber')
      ->addArgument(new Reference('language_manager'))
      ->setScope('request')
      ->addTag('event_subscriber');
    $container->register('request_close_subscriber', 'Drupal\Core\EventSubscriber\RequestCloseSubscriber')
      ->addTag('event_subscriber');
    $container->register('config_global_override_subscriber', 'Drupal\Core\EventSubscriber\ConfigGlobalOverrideSubscriber')
      ->addTag('event_subscriber');
    $container->register('exception_listener', 'Drupal\Core\EventSubscriber\ExceptionListener')
      ->addTag('event_subscriber')
      ->addArgument(new Reference('service_container'))
      ->setFactoryClass('Drupal\Core\ExceptionController')
      ->setFactoryMethod('getExceptionListener');

    $container->addCompilerPass(new RegisterMatchersPass());
    $container->addCompilerPass(new RegisterNestedMatchersPass());
    // Add a compiler pass for registering event subscribers.
    $container->addCompilerPass(new RegisterKernelListenersPass(), PassConfig::TYPE_AFTER_REMOVING);
  }

}
