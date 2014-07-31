<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityFormBuilder.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;

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
  public function getForm(EntityInterface $entity, $operation = 'default', array $form_state_additions = array()) {
    $form_object = $this->entityManager->getFormObject($entity->getEntityTypeId(), $operation);
    $form_object->setEntity($entity);

    $form_state = new FormState($form_state_additions);
    $form_state['build_info']['callback_object'] = $form_object;
    $form_state['build_info']['base_form_id'] = $form_object->getBaseFormID();
    $form_state['build_info'] += array('args' => array());

    return $this->formBuilder->buildForm($form_object, $form_state);
  }

}
