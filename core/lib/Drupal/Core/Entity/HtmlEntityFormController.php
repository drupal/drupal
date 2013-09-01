<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\HtmlEntityFormController.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Controller\HtmlFormController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Wrapping controller for entity forms that serve as the main page body.
 */
class HtmlEntityFormController extends HtmlFormController {

  /**
   * {@inheritdoc}
   *
   * Due to reflection, the argument must be named $_entity_form. The parent
   * method has $request and $_form, but the parameter must match the route.
   */
  public function content(Request $request, $_entity_form) {
    return parent::content($request, $_entity_form);
  }

  /**
   * {@inheritdoc}
   *
   * Instead of a class name or service ID, $form_arg will be a string
   * representing the entity and operation being performed.
   * Consider the following route:
   * @code
   *   pattern: '/foo/{node}/bar'
   *   defaults:
   *     _entity_form: 'node.edit'
   * @endcode
   * This means that the edit form controller for the node entity will used.
   * If the entity type has a default form controller, only the name of the
   * entity {param} needs to be passed:
   * @code
   *   pattern: '/foo/{node}/baz'
   *   defaults:
   *     _entity_form: 'node'
   * @endcode
   */
  protected function getFormObject(Request $request, $form_arg) {
    $manager = $this->container->get('entity.manager');

    // If no operation is provided, use 'default'.
    $form_arg .= '.default';
    list ($entity_type, $operation) = explode('.', $form_arg);

    if ($request->attributes->has($entity_type)) {
      $entity = $request->attributes->get($entity_type);
    }
    else {
      $entity = $manager->getStorageController($entity_type)->create(array());
    }

    return $manager->getFormController($entity_type, $operation)->setEntity($entity);
  }

}
