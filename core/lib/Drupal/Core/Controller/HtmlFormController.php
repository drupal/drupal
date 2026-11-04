<?php

namespace Drupal\Core\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

/**
 * Wrapping controller for forms that serve as the main page body.
 */
class HtmlFormController extends FormController {

  /**
   * Constructs a new \Drupal\Core\Controller\HtmlFormController object.
   *
   * @param \Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface $argument_resolver
   *   The argument resolver.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver.
   */
  public function __construct(
    ArgumentResolverInterface $argument_resolver,
    FormBuilderInterface $form_builder,
    protected ClassResolverInterface $classResolver,
  ) {
    parent::__construct($argument_resolver, $form_builder);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormArgument(RouteMatchInterface $route_match) {
    return $route_match->getRouteObject()->getDefault('_form');
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormObject(RouteMatchInterface $route_match, $form_arg) {
    return $this->classResolver->getInstanceFromDefinition($form_arg);
  }

}
