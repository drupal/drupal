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

    $definitions = $this->getDefinitions();

    foreach ($definitions as $id => $info) {
      $info += array(
        'tags' => array(),
        'references' => array(),
        'parameters' => array(),
        'methods' => array(),
        'arguments' => array(),
      );

      $references = array();
      foreach ($info['references'] as $ref_id) {
        $references[] = new Reference($ref_id);
      }

      $definition = new Definition($info['class'], $references);

      foreach ($info['parameters'] as $key => $param) {
        $container->setParameter($key, $param);
        $definition->addArgument("%{$key}%");
      }

      if (isset($info['factory_class']) && isset($info['factory_method'])) {
        $definition->setFactoryClass($info['factory_class']);
        $definition->setFactoryMethod($info['factory_method']);
      }

      foreach ($info['arguments'] as $argument) {
        $definition->addArgument($argument);
      }

      foreach($info['tags'] as $tag) {
        $definition->addTag($tag);
      }

      foreach ($info['methods'] as $method => $args) {
        $definition->addMethodCall($method, $args);
      }

      $container->setDefinition($id, $definition);
    }

    $this->registerLanguageServices($container);

    // Add a compiler pass for registering event subscribers.
    $container->addCompilerPass(new RegisterKernelListenersPass(), PassConfig::TYPE_AFTER_REMOVING);
  }

  /**
   * Returns an array of definitions for the services we want to register.
   */
  function getDefinitions() {
    return array(
      // Register configuration storage dispatcher.
      'config.storage.dispatcher' => array(
        'class' => 'Drupal\Core\Config\StorageDispatcher',
        'parameters' => array(
          'conifg.storage.info' => array(
            'Drupal\Core\Config\DatabaseStorage' => array(
              'connection' => 'default',
              'target' => 'default',
              'read' => TRUE,
              'write' => TRUE,
            ),
            'Drupal\Core\Config\FileStorage' => array(
              'directory' => config_get_config_directory(),
              'read' => TRUE,
              'write' => FALSE,
            ),
          ),
        ),
      ),
      'config.factory' => array(
        'class' => 'Drupal\Core\Config\ConfigFactory',
        'references' => array(
          'config.storage.dispatcher'
        )
      ),
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
      'database' => array(
        'class' => 'Drupal\Core\Database\Connection',
        'factory_class' => 'Drupal\Core\Database\Database',
        'factory_method' => 'getConnection',
        'arguments' => array('default'),
      ),
      'database.slave' => array(
        'class' => 'Drupal\Core\Database\Connection',
        'factory_class' => 'Drupal\Core\Database\Database',
        'factory_method' => 'getConnection',
        'arguments' => array('slave'),
      ),
    );
  }

  /**
   * Registers language-related services to the container.
   */
  function registerLanguageServices($container) {

    $types = language_types_get_all();

    // Ensure a language object is registered for each language type, whether the
    // site is multilingual or not.
    if (language_multilingual()) {
      include_once DRUPAL_ROOT . '/core/includes/language.inc';
      foreach ($types as $type) {
        $language = language_types_initialize($type);
        // We cannot pass an object as a parameter to a method on a service.
        $info = get_object_vars($language);
        $container->set($type, NULL);
        $container->register($type, 'Drupal\\Core\\Language\\Language')
          ->addMethodCall('extend', array($info));
      }
    }
    else {
      $info = variable_get('language_default', array(
        'langcode' => 'en',
        'name' => 'English',
        'direction' => 0,
        'weight' => 0,
        'locked' => 0,
      ));
      $info['default'] = TRUE;
      foreach ($types as $type) {
        $container->set($type, NULL);
        $container->register($type, 'Drupal\\Core\\Language\\Language')
          ->addMethodCall('extend', array($info));
      }
    }
  }
}