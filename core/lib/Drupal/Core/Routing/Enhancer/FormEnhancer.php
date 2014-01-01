<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\Enhancer\FormEnhancer.
 */

namespace Drupal\Core\Routing\Enhancer;

use Drupal\Core\Controller\HtmlFormController;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;

/**
 * Enhances a form route with the appropriate controller.
 */
class FormEnhancer implements RouteEnhancerInterface {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $resolver;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new \Drupal\Core\Routing\Enhancer\FormEnhancer object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Drupal\Core\Controller\ControllerResolverInterface $resolver
   *   The controller resolver.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(ContainerInterface $container, ControllerResolverInterface $resolver, FormBuilderInterface $form_builder) {
    $this->container = $container;
    $this->resolver = $resolver;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (!empty($defaults['_form'])) {
      $wrapper = new HtmlFormController($this->resolver, $this->container, $defaults['_form'], $this->formBuilder);
      $defaults['_content'] = array($wrapper, 'getContentResult');
    }
    return $defaults;
  }

}
