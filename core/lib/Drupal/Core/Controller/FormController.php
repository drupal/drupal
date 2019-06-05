<?php

namespace Drupal\Core\Controller;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

/**
 * Common base class for form interstitial controllers.
 */
abstract class FormController {
  use DependencySerializationTrait;

  /**
   * The argument resolver.
   *
   * @var \Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface
   */
  protected $argumentResolver;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   *
   * @deprecated
   *   Deprecated property that is only assigned when the 'controller_resolver'
   *   service is used as the first parameter to FormController::__construct().
   *
   * @see https://www.drupal.org/node/2959408
   * @see \Drupal\Core\Controller\FormController::__construct()
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
   * @param \Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface $argument_resolver
   *   The argument resolver.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(ArgumentResolverInterface $argument_resolver, FormBuilderInterface $form_builder) {
    $this->argumentResolver = $argument_resolver;
    if ($argument_resolver instanceof ControllerResolverInterface) {
      @trigger_error("Using the 'controller_resolver' service as the first argument is deprecated, use the 'http_kernel.controller.argument_resolver' instead. If your subclass requires the 'controller_resolver' service add it as an additional argument. See https://www.drupal.org/node/2959408.", E_USER_DEPRECATED);
      $this->controllerResolver = $argument_resolver;
    }
    $this->formBuilder = $form_builder;
  }

  /**
   * Invokes the form and returns the result.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   The render array that results from invoking the controller.
   */
  public function getContentResult(Request $request, RouteMatchInterface $route_match) {
    $form_arg = $this->getFormArgument($route_match);
    $form_object = $this->getFormObject($route_match, $form_arg);

    // Add the form and form_state to trick the getArguments method of the
    // controller resolver.
    $form_state = new FormState();
    $request->attributes->set('form', []);
    $request->attributes->set('form_state', $form_state);
    $args = $this->argumentResolver->getArguments($request, [$form_object, 'buildForm']);
    $request->attributes->remove('form');
    $request->attributes->remove('form_state');

    // Remove $form and $form_state from the arguments, and re-index them.
    unset($args[0], $args[1]);
    $form_state->addBuildInfo('args', array_values($args));

    return $this->formBuilder->buildForm($form_object, $form_state);
  }

  /**
   * Extracts the form argument string from a request.
   *
   * Depending on the type of form the argument string may be stored in a
   * different request attribute.
   *
   * One example of a route definition is given below.
   * @code
   *   defaults:
   *     _form: Drupal\example\Form\ExampleForm
   * @endcode
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object from which to extract a form definition string.
   *
   * @return string
   *   The form definition string.
   */
  abstract protected function getFormArgument(RouteMatchInterface $route_match);

  /**
   * Returns the object used to build the form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param string $form_arg
   *   Either a class name or a service ID.
   *
   * @return \Drupal\Core\Form\FormInterface
   *   The form object to use.
   */
  abstract protected function getFormObject(RouteMatchInterface $route_match, $form_arg);

}
