<?php

/**
 * @file
 * Contains \Drupal\Core\Controler\HtmlFormController.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Constructs a new \Drupal\Core\Routing\Enhancer\FormEnhancer object.
   */
  public function __construct(ControllerResolverInterface $resolver, ContainerInterface $container, $class, FormBuilderInterface $form_builder) {
    parent::__construct($resolver, $form_builder);
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
    // If this is a class, instantiate it.
    if (class_exists($form_arg)) {
      if (in_array('Drupal\Core\DependencyInjection\ContainerInjectionInterface', class_implements($form_arg))) {
        return $form_arg::create($this->container);
      }

      return new $form_arg();
    }

    // Otherwise, it is a service.
    return $this->container->get($form_arg);
  }

}
