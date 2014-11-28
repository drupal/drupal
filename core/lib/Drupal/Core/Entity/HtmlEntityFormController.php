<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\HtmlEntityFormController.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Controller\FormController;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Wrapping controller for entity forms that serve as the main page body.
 */
class HtmlEntityFormController extends FormController {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $manager;

  /**
   * Constructs a new \Drupal\Core\Routing\Enhancer\FormEnhancer object.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $resolver
   *   The controller resolver.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(ControllerResolverInterface $resolver, FormBuilderInterface $form_builder, EntityManagerInterface $manager) {
    parent::__construct($resolver, $form_builder);
    $this->manager = $manager;
  }

  /**
   * @{inheritDoc}
   */
  protected function getFormArgument(Request $request) {
    return $request->attributes->get('_entity_form');
  }

  /**
   * {@inheritdoc}
   *
   * Instead of a class name or service ID, $form_arg will be a string
   * representing the entity and operation being performed.
   * Consider the following route:
   * @code
   *   path: '/foo/{node}/bar'
   *   defaults:
   *     _entity_form: 'node.edit'
   * @endcode
   * This means that the edit form for the node entity will used.
   * If the entity type has a default form, only the name of the
   * entity {param} needs to be passed:
   * @code
   *   path: '/foo/{node}/baz'
   *   defaults:
   *     _entity_form: 'node'
   * @endcode
   */
  protected function getFormObject(Request $request, $form_arg) {
    // If no operation is provided, use 'default'.
    $form_arg .= '.default';
    list ($entity_type, $operation) = explode('.', $form_arg);

    if ($request->attributes->has($entity_type)) {
      $entity = $request->attributes->get($entity_type);
    }
    else {
      $entity = $this->manager->getStorage($entity_type)->create([]);
    }

    return $this->manager->getFormObject($entity_type, $operation)->setEntity($entity);
  }

}
