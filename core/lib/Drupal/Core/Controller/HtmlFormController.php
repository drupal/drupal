<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\HtmlFormController.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\DependencyInjection\ClassResolverInterface;

/**
 * Wrapping controller for forms that serve as the main page body.
 */
class HtmlFormController extends FormController {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface;
   */
  protected $classResolver;

  /**
   * Constructs a new \Drupal\Core\Routing\Enhancer\FormEnhancer object.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, FormBuilderInterface $form_builder, ClassResolverInterface $class_resolver) {
    parent::__construct($controller_resolver, $form_builder);
    $this->classResolver = $class_resolver;
  }

  /**
   * @{inheritDoc}
   */
  protected function getFormArgument(Request $request) {
    return $request->attributes->get('_form');
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
