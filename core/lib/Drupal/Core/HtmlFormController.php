<?php

/**
 * @file
 * Contains \Drupal\Core\HtmlFormController.
 */

namespace Drupal\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Wrapping controller for forms that serve as the main page body.
 */
class HtmlFormController implements ContainerAwareInterface {

  /**
   * The injection container for this object.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Injects the service container used by this object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   */
  public function setContainer(ContainerInterface $container = NULL) {
    $this->container = $container;
  }

  /**
   * Controller method for generic HTML form pages.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param callable $_form
   *   The name of the form class for this request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  public function content(Request $request, $_form) {
    $form_object = $this->getFormObject($request, $_form);

    // Using reflection, find all of the parameters needed by the form in the
    // request attributes, skipping $form and $form_state.
    $attributes = $request->attributes->all();
    $reflection = new \ReflectionMethod($form_object, 'buildForm');
    $params = $reflection->getParameters();
    $args = array();
    foreach (array_splice($params, 2) as $param) {
      if (array_key_exists($param->name, $attributes)) {
        $args[] = $attributes[$param->name];
      }
    }
    $form_state['build_info']['args'] = $args;

    $form_id = _drupal_form_id($form_object, $form_state);
    $form = drupal_build_form($form_id, $form_state);
    return new Response(drupal_render_page($form));
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
      if (in_array('Drupal\Core\ControllerInterface', class_implements($form_arg))) {
        return $form_arg::create($this->container);
      }

      return new $form_arg();
    }

    // Otherwise, it is a service.
    return $this->container->get($form_arg);
  }

}
