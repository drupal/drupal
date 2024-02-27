<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\TypedData\Validation\TypedDataAwareValidatorTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ChoiceValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * Validates the AllowedValues constraint.
 */
class AllowedValuesConstraintValidator extends ChoiceValidator implements ContainerInjectionInterface {

  use TypedDataAwareValidatorTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_user'));
  }

  /**
   * Constructs a new AllowedValuesConstraintValidator.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    $typed_data = $this->getTypedData();

    if ($typed_data instanceof OptionsProviderInterface) {
      $allowed_values = $typed_data->getSettableValues($this->currentUser);
      $constraint->choices = $allowed_values;

      // If the data is complex, we have to validate its main property.
      if ($typed_data instanceof ComplexDataInterface) {
        $name = $typed_data->getDataDefinition()->getMainPropertyName();
        if (!isset($name)) {
          throw new \LogicException('Cannot validate allowed values for complex data without a main property.');
        }
        $typed_data = $typed_data->get($name);
        $value = $typed_data->getValue();
      }
    }

    // The parent implementation ignores values that are not set, but makes
    // sure some choices are available firstly. However, we want to support
    // empty choices for undefined values; for instance, if a term reference
    // field points to an empty vocabulary.
    if (!isset($value)) {
      return;
    }

    // Get the value with the proper datatype in order to make strict
    // comparisons using in_array().
    if (!($typed_data instanceof PrimitiveInterface)) {
      throw new \LogicException('The data type must be a PrimitiveInterface at this point.');
    }
    $value = $typed_data->getCastedValue();

    // In a better world where typed data just returns typed values, we could
    // set a constraint callback to use the OptionsProviderInterface.
    // This is not possible right now though because we do the typecasting
    // further down.
    if ($constraint->callback) {
      if (!\is_callable($choices = [$this->context->getObject(), $constraint->callback])
        && !\is_callable($choices = [$this->context->getClassName(), $constraint->callback])
        && !\is_callable($choices = $constraint->callback)
      ) {
        throw new ConstraintDefinitionException('The AllowedValuesConstraint constraint expects a valid callback');
      }
      $allowed_values = \call_user_func($choices);
      $constraint->choices = $allowed_values;
      // parent::validate() does not need to invoke the callback again.
      $constraint->callback = NULL;
    }

    // Force the choices to be the same type as the value.
    $type = gettype($value);
    foreach ($constraint->choices as &$choice) {
      settype($choice, $type);
    }

    parent::validate($value, $constraint);
  }

}
