<?php

namespace Drupal\Core;

use Drupal\Core\DependencyInjection\Compiler\RegisterKernelListenersPass;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

class DrupalBundle extends Bundle
{
  public function build(ContainerBuilder $container)
  {
    parent::build($container);
    // An interface language always needs to be available for t() and other
    // functions. This default is overridden by drupal_language_initialize()
    // during language negotiation.
    $container->register(LANGUAGE_TYPE_INTERFACE, 'Drupal\\Core\\Language\\Language');

    // Register the default language content.
    $container->register(LANGUAGE_TYPE_CONTENT, 'Drupal\\Core\\Language\\Language');

    $definitions = array(
      'dispatcher' => array(
        'class' => 'Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher',
        'references' => array(
          'service_container',
        ),
      ),
      'resolver' => array(
        'class' => 'Symfony\Component\HttpKernel\Controller\ControllerResolver',
      ),
      'http_kernel' => array(
        'class' => 'Symfony\Component\HttpKernel\HttpKernel',
        'references' => array(
          'dispatcher', 'resolver',
        )
      ),
      'matcher' => array(
        'class' => 'Drupal\Core\LegacyUrlMatcher',
      ),
      'router_listener' => array(
        'class' => 'Drupal\Core\EventSubscriber\RouterListener',
        'references' => array('matcher'),
        'tags' => array('kernel.event_subscriber')
      ),
      'content_negotiation' => array(
        'class' => 'Drupal\Core\ContentNegotiation',
      ),
      'view_subscriber' => array(
        'class' => 'Drupal\Core\EventSubscriber\ViewSubscriber',
        'references' => array('content_negotiation'),
        'tags' => array('kernel.event_subscriber')
      ),
      'access_subscriber' => array(
        'class' => 'Drupal\Core\EventSubscriber\AccessSubscriber',
        'tags' => array('kernel.event_subscriber')
      ),
      'maintenance_mode_subscriber' => array(
        'class' => 'Drupal\Core\EventSubscriber\MaintenanceModeSubscriber',
        'tags' => array('kernel.event_subscriber')
      ),
      'path_subscriber' => array(
        'class' => 'Drupal\Core\EventSubscriber\PathSubscriber',
        'tags' => array('kernel.event_subscriber')
      ),
      'legacy_request_subscriber' => array(
        'class' => 'Drupal\Core\EventSubscriber\LegacyRequestSubscriber',
        'tags' => array('kernel.event_subscriber')
      ),
      'legacy_controller_subscriber' => array(
        'class' => 'Drupal\Core\EventSubscriber\LegacyControllerSubscriber',
        'tags' => array('kernel.event_subscriber')
      ),
      'finish_response_subscriber' => array(
        'class' => 'Drupal\Core\EventSubscriber\FinishResponseSubscriber',
        'tags' => array('kernel.event_subscriber')
      ),
      'request_close_subscriber' => array(
        'class' => 'Drupal\Core\EventSubscriber\RequestCloseSubscriber',
        'tags' => array('kernel.event_subscriber')
      ),
      'exception_controller' => array(
        'class' => 'Drupal\Core\ExceptionController',
        'references' => array('content_negotiation'),
        'methods' => array('setContainer' => array('service_container'))
      ),
      'exception_listener' => array(
        'class' => 'Symfony\Component\HttpKernel\EventListener\ExceptionListener',
        'references' => array('exception_controller'),
        'tags' => array('kernel.event_subscriber')
      ),
    );

    foreach ($definitions as $id => $info) {
      $info += array(
        'tags' => array(),
        'references' => array(),
        'methods' => array(),
      );

      $references = array();
      foreach ($info['references'] as $ref_id) {
        $references[] = new Reference($ref_id);
      }

      $definition = new Definition($info['class'], $references);

      foreach($info['tags'] as $tag) {
        $definition->addTag($tag);
      }

      foreach ($info['methods'] as $method => $args) {
        $definition->addMethodCall($method, $args);
      }

      $container->setDefinition($id, $definition);
    }

    $container->addCompilerPass(new RegisterKernelListenersPass(), PassConfig::TYPE_AFTER_REMOVING);
  }
}