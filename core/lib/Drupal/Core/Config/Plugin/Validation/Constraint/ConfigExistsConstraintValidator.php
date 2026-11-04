<?php

declare(strict_types = 1);

namespace Drupal\Core\Config\Plugin\Validation\Constraint;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Schema\TypeResolver;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a given config object exists.
 */
class ConfigExistsConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs a ConfigExistsConstraintValidator object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $name, Constraint $constraint): void {
    assert($constraint instanceof ConfigExistsConstraint);

    // This constraint may be used to validate nullable (optional) values.
    if ($name === NULL) {
      return;
    }

    $constraint->prefix = TypeResolver::resolveDynamicTypeName($constraint->prefix, $this->context->getObject());

    if (!in_array($constraint->prefix . $name, $this->configFactory->listAll($constraint->prefix), TRUE)) {
      $this->context->addViolation($constraint->message, ['@name' => $constraint->prefix . $name]);
    }
  }

}
