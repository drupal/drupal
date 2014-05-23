<?php

/**
 * @file
 * Contains \Drupal\Core\Controler\HtmlFormController.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Controller\ControllerResolverInterface;

/**
 * Wrapping controller for forms that serve as the main page body.
 */
class HtmlFormController extends FormController {

  /**
   * The injection container for this object.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The name of a class implementing FormInterface that defines a form.
   *
   * @var string
   */
  protected $formClass;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface;
   */
  protected $classResolver;

  /**
   * Constructs a new \Drupal\Core\Routing\Enhancer\FormEnhancer object.
   */
  public function __construct(ClassResolverInterface $class_resolver, ControllerResolverInterface $controller_resolver, ContainerInterface $container, $class, FormBuilderInterface $form_builder) {
    parent::__construct($controller_resolver, $form_builder);
    $this->classResolver = $class_resolver;
    $this->container = $container;
    $this->formDefinition = $class;
  }

  /**
   * Returns the object used to build the form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request using this form.
   * @param string $form_arg
   *   Either a class name or a service ID.
   *
   * @return \Drupal\Core\Form\FormInterface
   *   The form object to use.
   */
  protected function getFormObject(Request $request, $form_arg) {
    return $this->classResolver->getInstanceFromDefinition($form_arg);
  }

}
