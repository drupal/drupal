<?php

namespace Drupal\workspaces\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if new entities created for entity reference fields are supported.
 */
class EntityReferenceSupportedNewEntitiesConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new EntityReferenceSupportedNewEntitiesConstraintValidator instance.
   */
  public function __construct(WorkspaceManagerInterface $workspaceManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->workspaceManager = $workspaceManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspaces.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    // The validator should run only if we are in a active workspace context.
    if (!$this->workspaceManager->hasActiveWorkspace()) {
      return;
    }

    $target_entity_type_id = $value->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type');
    $target_entity_type = $this->entityTypeManager->getDefinition($target_entity_type_id);

    if ($value->hasNewEntity() && !$this->workspaceManager->isEntityTypeSupported($target_entity_type)) {
      $this->context->addViolation($constraint->message, ['%collection_label' => $target_entity_type->getCollectionLabel()]);
    }
  }

}
