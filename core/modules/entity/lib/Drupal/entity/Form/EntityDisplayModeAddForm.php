<?php

/**
 * @file
 * Contains \Drupal\entity\Form\EntityDisplayModeAddForm.
 */

namespace Drupal\entity\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the add form for entity display modes.
 */
class EntityDisplayModeAddForm extends EntityDisplayModeFormBase {

  /**
   * @var string
   */
  protected $entityType;

  /**
   * The entity manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new EntityDisplayModeAddForm.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param array $entity_info
   *   The entity type definition.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, QueryFactory $query_factory, array $entity_info, PluginManagerInterface $entity_manager) {
    parent::__construct($module_handler, $query_factory, $entity_info);

    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity.query'),
      $entity_info,
      $container->get('plugin.manager.entity')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $entity_type = NULL) {
    $this->entityType = $entity_type;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);

    form_set_value($form['id'], $this->entityType . '.' . $form_state['values']['id'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    $definition = $this->entityManager->getDefinition($this->entityType);
    if (!$definition['fieldable'] || !isset($definition['controllers']['render'])) {
      throw new NotFoundHttpException();
    }

    drupal_set_title(t('Add new %label @entity-type', array('%label' => $definition['label'], '@entity-type' => strtolower($this->entityInfo['label']))), PASS_THROUGH);
    $this->entity->targetEntityType = $this->entityType;
  }

}
