<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityFormBuilder.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Form\FormBuilderInterface;

/**
 * Builds entity forms.
 */
class EntityFormBuilder implements EntityFormBuilderInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new EntityFormBuilder.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityManagerInterface $entity_manager, FormBuilderInterface $form_builder) {
    $this->entityManager = $entity_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(EntityInterface $entity, $operation = 'default', array $form_state = array()) {
    $controller = $this->entityManager->getFormController($entity->getEntityTypeId(), $operation);
    $controller->setEntity($entity);

    $form_state['build_info']['callback_object'] = $controller;
    $form_state['build_info']['base_form_id'] = $controller->getBaseFormID();
    $form_state['build_info'] += array('args' => array());

    return $this->formBuilder->buildForm($controller->getFormId(), $form_state);
  }

}
