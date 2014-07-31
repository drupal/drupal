<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\FormController.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Symfony\Component\HttpFoundation\Request;

/**
 * Common base class for form interstitial controllers.
 *
 * @todo Make this a trait in PHP 5.4.
 */
abstract class FormController {
  use DependencySerializationTrait;
  /**
   * The form definition. The format may vary depending on the child class.
   *
   * @var string
   */
  protected $formDefinition;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new \Drupal\Core\Controller\FormController object.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, FormBuilderInterface $form_builder) {
    $this->controllerResolver = $controller_resolver;
    $this->formBuilder = $form_builder;
  }

  /**
   * Invokes the form and returns the result.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   The render array that results from invoking the controller.
   */
  public function getContentResult(Request $request) {
    $form_object = $this->getFormObject($request, $this->formDefinition);

    // Add the form and form_state to trick the getArguments method of the
    // controller resolver.
    $form_state = new FormState();
    $request->attributes->set('form', array());
    $request->attributes->set('form_state', $form_state);
    $args = $this->controllerResolver->getArguments($request, array($form_object, 'buildForm'));
    $request->attributes->remove('form');
    $request->attributes->remove('form_state');

    // Remove $form and $form_state from the arguments, and re-index them.
    unset($args[0], $args[1]);
    $form_state['build_info']['args'] = array_values($args);

    return $this->formBuilder->buildForm($form_object, $form_state);
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
  abstract protected function getFormObject(Request $request, $form_arg);

}
