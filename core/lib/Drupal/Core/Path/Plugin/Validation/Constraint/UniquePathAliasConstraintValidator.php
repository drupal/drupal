<?php

namespace Drupal\Core\Path\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for a unique path alias.
 */
class UniquePathAliasConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Creates a new UniquePathAliasConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint): void {
    /** @var \Drupal\path_alias\PathAliasInterface $entity */
    $path = $entity->getPath();
    $alias = $entity->getAlias();
    $langcode = $entity->language()->getId();

    $storage = $this->entityTypeManager->getStorage('path_alias');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('alias', $alias, '=')
      ->condition('langcode', $langcode, '=');

    if (!$entity->isNew()) {
      $query->condition('id', $entity->id(), '<>');
    }
    if ($path) {
      $query->condition('path', $path, '<>');
    }

    if ($result = $query->range(0, 1)->execute()) {
      $existing_alias_id = reset($result);
      $existing_alias = $storage->load($existing_alias_id);

      if ($existing_alias->getAlias() !== $alias) {
        $this->context->buildViolation($constraint->differentCapitalizationMessage, [
          '%alias' => $alias,
          '%stored_alias' => $existing_alias->getAlias(),
        ])->addViolation();
      }
      else {
        $this->context->buildViolation($constraint->message, [
          '%alias' => $alias,
        ])->addViolation();
      }
    }
  }

}
