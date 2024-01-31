<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Config\Schema\TypeResolver;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a bundle exists on a certain content entity type.
 */
class EntityBundleExistsConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs an EntityBundleExistsConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   The entity type bundle info service.
   */
  public function __construct(private readonly EntityTypeBundleInfoInterface $bundleInfo) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get(EntityTypeBundleInfoInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    assert($constraint instanceof EntityBundleExistsConstraint);

    if (!is_string($value)) {
      throw new UnexpectedTypeException($value, 'string');
    }
    // Resolve any dynamic tokens, like %parent, in the entity type ID.
    $entity_type_id = TypeResolver::resolveDynamicTypeName("[$constraint->entityTypeId]", $this->context->getObject());

    if (!array_key_exists($value, $this->bundleInfo->getBundleInfo($entity_type_id))) {
      $this->context->addViolation($constraint->message, [
        '@bundle' => $value,
        '@entity_type_id' => $entity_type_id,
      ]);
    }
  }

}
