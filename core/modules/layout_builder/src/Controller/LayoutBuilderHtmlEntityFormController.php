<?php

namespace Drupal\layout_builder\Controller;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Controller\FormController;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Overrides the entity form controller service for layout builder operations.
 */
class LayoutBuilderHtmlEntityFormController {

  use DependencySerializationTrait;

  /**
   * The entity form controller being decorated.
   *
   * @var \Drupal\Core\Controller\FormController
   */
  protected $entityFormController;

  /**
   * Constructs a LayoutBuilderHtmlEntityFormController object.
   *
   * @param \Drupal\Core\Controller\FormController $entity_form_controller
   *   The entity form controller being decorated.
   */
  public function __construct(FormController $entity_form_controller) {
    $this->entityFormController = $entity_form_controller;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentResult(Request $request, RouteMatchInterface $route_match) {
    $form = $this->entityFormController->getContentResult($request, $route_match);

    // If the form render element has a #layout_builder_element_keys property,
    // first set the form element as a child of the root render array. Use the
    // keys to get the layout builder element from the form render array and
    // copy it to a separate child element of the root element to prevent any
    // forms within the layout builder element from being nested.
    if (isset($form['#layout_builder_element_keys'])) {
      $build['form'] = &$form;
      $layout_builder_element = &NestedArray::getValue($form, $form['#layout_builder_element_keys']);
      $build['layout_builder'] = $layout_builder_element;
      // Remove the layout builder element within the form.
      $layout_builder_element = [];
      return $build;
    }

    // If no #layout_builder_element_keys property, return form as is.
    return $form;
  }

}
