<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\ContentFormControllerSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Controller\HtmlFormController;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Subscriber for setting wrapping form logic.
 */
class ContentFormControllerSubscriber implements EventSubscriberInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The controller resolver.
   *
   * @var  \Drupal\Core\DependencyInjection\ClassResolverInterface.
   */
  protected $classResolver;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new ContentFormControllerSubscriber object.
   *
   * @param  \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The class resolver.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(ClassResolverInterface $class_resolver, ControllerResolverInterface $controller_resolver, FormBuilderInterface $form_builder) {
    $this->classResolver = $class_resolver;
    $this->controllerResolver = $controller_resolver;
    $this->formBuilder = $form_builder;
  }

  /**
   * Sets the _controllere on a request based on the request format.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onRequestDeriveFormWrapper(GetResponseEvent $event) {
    $request = $event->getRequest();

    if ($form = $request->attributes->get('_form')) {
      $wrapper = new HtmlFormController($this->classResolver, $this->controllerResolver, $this->container, $form, $this->formBuilder);
      $request->attributes->set('_content', array($wrapper, 'getContentResult'));
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onRequestDeriveFormWrapper', 29);

    return $events;
  }
}
