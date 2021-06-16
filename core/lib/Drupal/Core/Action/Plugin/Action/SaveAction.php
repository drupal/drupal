<?php

namespace Drupal\Core\Action\Plugin\Action;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an action that can save any entity.
 *
 * @Action(
 *   id = "entity:save_action",
 *   action_label = @Translation("Save"),
 *   deriver = "Drupal\Core\Action\Plugin\Action\Derivative\EntityChangedActionDeriver",
 * )
 */
class SaveAction extends EntityActionBase {

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a SaveAction object.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->setChangedTime($this->time->getRequestTime())->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // It's not necessary to check the changed field access here, because
    // Drupal\Core\Field\ChangedFieldItemList would anyway return 'not allowed'.
    // Also changing the changed field value is only a workaround to trigger an
    // entity resave. Without a field change, this would not be possible.
    /** @var \Drupal\Core\Entity\EntityInterface $object */
    return $object->access('update', $account, $return_as_object);
  }

}
