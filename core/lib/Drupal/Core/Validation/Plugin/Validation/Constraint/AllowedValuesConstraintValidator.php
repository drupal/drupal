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
  public function validate($value, Constraint $constraint) {
    $typed_data = $this->getTypedData();

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
      // parent::validate() does not need to invoke the callback again.
      $constraint->callback = NULL;
    }
    elseif ($typed_data instanceof OptionsProviderInterface) {
      $allowed_values = $typed_data->getSettableValues($this->currentUser);

      // If the data is complex, we have to validate its main property.
      if ($typed_data instanceof ComplexDataInterface) {
        $name = $typed_data->getDataDefinition()->getMainPropertyName();
        if (!isset($name)) {
          throw new \LogicException('Cannot validate allowed values for complex data without a main property.');
        }
        $value = $typed_data->get($name)->getValue();
      }
    }

    // The parent implementation ignores values that are not set, but makes
    // sure some choices are available firstly. However, we want to support
    // empty choices for undefined values; for instance, if a term reference
    // field points to an empty vocabulary.
    if (!isset($value)) {
      return;
    }

    if (isset($allowed_values)) {
      $constraint->choices = $allowed_values;
      // Make the types match for strict checking. We can't use typed data here
      // because types are not enforced everywhere. For example,
      // \Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem::getSettableValues()
      // uses 0 and 1 to represent possible Boolean values whilst
      // \Drupal\Core\TypedData\Plugin\DataType\BooleanData::getCastedValue()
      // will return a proper typed Boolean. Therefore assume that
      // $allowed_values contains values of the same type and cast $value to
      // match.
      settype($value, gettype(reset($allowed_values)));
    }
    elseif ($typed_data instanceof PrimitiveInterface) {
      $value = $typed_data->getCastedValue();
    }

    parent::validate($value, $constraint);
  }

}
