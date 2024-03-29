<?php

declare(strict_types = 1);

namespace Drupal\Core\Config\Plugin\Validation\Constraint;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a given config object exists.
 */
class ConfigExistsConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a ConfigExistsConstraintValidator object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
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

    if (!in_array($constraint->prefix . $name, $this->configFactory->listAll($constraint->prefix), TRUE)) {
      $this->context->addViolation($constraint->message, ['@name' => $constraint->prefix . $name]);
    }
  }

}
