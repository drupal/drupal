<?php

/**
 * @file
 * Definition of Drupal\Core\DependencyInjection\Container.
 */

namespace Drupal\Core\DependencyInjection;

use Drupal\Core\ContentNegotiation;
use Drupal\Core\EventSubscriber\AccessSubscriber;
use Drupal\Core\EventSubscriber\FinishResponseSubscriber;
use Drupal\Core\EventSubscriber\LegacyControllerSubscriber;
use Drupal\Core\EventSubscriber\LegacyRequestSubscriber;
use Drupal\Core\EventSubscriber\MaintenanceModeSubscriber;
use Drupal\Core\EventSubscriber\PathSubscriber;
use Drupal\Core\EventSubscriber\RequestCloseSubscriber;
use Drupal\Core\EventSubscriber\RouterListener;
use Drupal\Core\EventSubscriber\ViewSubscriber;
use Drupal\Core\ExceptionController;
use Drupal\Core\LegacyUrlMatcher;
use Symfony\Component\DependencyInjection\ContainerBuilder as BaseContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener;

/**
 * Drupal's dependency injection container.
 */
class ContainerBuilder extends BaseContainerBuilder {

  /**
   * Registers the base Drupal services for the dependency injection container.
   */
  public function __construct() {
    parent::__construct();

    // An interface language always needs to be available for t() and other
    // functions. This default is overridden by drupal_language_initialize()
    // during language negotiation.
    $this->register(LANGUAGE_TYPE_INTERFACE, 'Drupal\\Core\\Language\\Language');

    // Register the default language content.
    $this->register(LANGUAGE_TYPE_CONTENT, 'Drupal\\Core\\Language\\Language');

    // Register configuration storage dispatcher.
    $this->setParameter('config.storage.info', array(
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
    ));
    $this->register('config.storage.dispatcher', 'Drupal\Core\Config\StorageDispatcher')
      ->addArgument('%config.storage.info%');

    // Register configuration object factory.
    $this->register('config.factory', 'Drupal\Core\Config\ConfigFactory')
      ->addArgument(new Reference('config.storage.dispatcher'));

    // Register the HTTP kernel services.
    $this->register('dispatcher', 'Symfony\Component\EventDispatcher\EventDispatcher')
      ->addArgument(new Reference('service_container'))
      ->setFactoryClass('Drupal\Core\DependencyInjection\ContainerBuilder')
      ->setFactoryMethod('getKernelEventDispatcher');
    $this->register('resolver', 'Symfony\Component\HttpKernel\Controller\ControllerResolver');
    $this->register('httpkernel', 'Symfony\Component\HttpKernel\HttpKernel')
      ->addArgument(new Reference('dispatcher'))
      ->addArgument(new Reference('resolver'));
  }

  /**
   * Creates an EventDispatcher for the HttpKernel. Factory method.
   *
   * @param Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The dependency injection container that contains the HTTP kernel.
   *
   * @return Symfony\Component\EventDispatcher\EventDispatcher
   *   An EventDispatcher with the default listeners attached to it.
   */
  public static function getKernelEventDispatcher($container) {
    $dispatcher = new EventDispatcher();

    $matcher = new LegacyUrlMatcher();
    $dispatcher->addSubscriber(new RouterListener($matcher));

    $negotiation = new ContentNegotiation();

    // @todo Make this extensible rather than just hard coding some.
    // @todo Add a subscriber to handle other things, too, like our Ajax
    //   replacement system.
    $dispatcher->addSubscriber(new ViewSubscriber($negotiation));
    $dispatcher->addSubscriber(new AccessSubscriber());
    $dispatcher->addSubscriber(new MaintenanceModeSubscriber());
    $dispatcher->addSubscriber(new PathSubscriber());
    $dispatcher->addSubscriber(new LegacyRequestSubscriber());
    $dispatcher->addSubscriber(new LegacyControllerSubscriber());
    $dispatcher->addSubscriber(new FinishResponseSubscriber());
    $dispatcher->addSubscriber(new RequestCloseSubscriber());

    // Some other form of error occured that wasn't handled by another kernel
    // listener. That could mean that it's a method/mime-type/error combination
    // that is not accounted for, or some other type of error. Either way, treat
    // it as a server-level error and return an HTTP 500. By default, this will
    // be an HTML-type response because that's a decent best guess if we don't
    // know otherwise.
    $exceptionController = new ExceptionController($negotiation);
    $exceptionController->setContainer($container);
    $dispatcher->addSubscriber(new ExceptionListener(array($exceptionController, 'execute')));

    return $dispatcher;
  }
}
