<?php

/**
 * @file
 * Contains \Drupal\entity\Form\EntityDisplayModeFormBase.
 */

namespace Drupal\entity\Form;

use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the generic base class for entity display mode forms.
 */
abstract class EntityDisplayModeFormBase extends EntityFormController implements EntityControllerInterface {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The entity type definition.
   *
   * @var array
   */
  protected $entityInfo;

  /**
   * Constructs a new EntityDisplayModeFormBase.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param array $entity_info
   *   The entity type definition.
   */
  public function __construct(ModuleHandlerInterface $module_handler, QueryFactory $query_factory, array $entity_info) {
    parent::__construct($module_handler);

    $this->queryFactory = $query_factory;
    $this->entityInfo = $entity_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity.query'),
      $entity_info
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 100,
      '#default_value' => $this->entity->label(),
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => t('Machine-readable name'),
      '#description' => t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => $this->entity->id(),
      '#field_prefix' => $this->entity->isNew() ? $this->entity->getTargetType() . '.' : '',
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
        'replace_pattern' => '[^a-z0-9_.]+',
      ),
    );

    return $form;
  }

  /**
   * Determines if the display mode already exists.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param array $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if the display mode exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, array $form_state) {
    return (bool) $this->queryFactory
      ->get($this->entity->entityType())
      ->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    drupal_set_message(t('Saved the %label @entity-type.', array('%label' => $this->entity->label(), '@entity-type' => strtolower($this->entityInfo['label']))));
    $this->entity->save();
    entity_info_cache_clear();
    $short_type = str_replace('_mode', '', $this->entity->entityType());
    $form_state['redirect'] = "admin/structure/display-modes/$short_type";
  }

}
