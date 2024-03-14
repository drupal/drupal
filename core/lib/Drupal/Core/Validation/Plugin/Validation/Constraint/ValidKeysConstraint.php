<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Checks that all the keys of a mapping are valid and required keys present.
 */
#[Constraint(
  id: 'ValidKeys',
  label: new TranslatableMarkup('Valid mapping keys', [], ['context' => 'Validation']),
  type: ['mapping']
)]
class ValidKeysConstraint extends SymfonyConstraint {

  /**
   * The error message if a key is invalid.
   *
   * @var string
   */
  public string $invalidKeyMessage = "'@key' is not a supported key.";

  /**
   * The error message if a key is unknown for the resolved type.
   *
   * @var string
   */
  public string $dynamicInvalidKeyMessage = "'@key' is an unknown key because @dynamic_type_property_path is @dynamic_type_property_value (see config schema type @resolved_dynamic_type).";

  /**
   * The error message if a key is missing.
   *
   * @var string
   */
  public string $missingRequiredKeyMessage = "'@key' is a required key.";

  /**
   * The error message if a dynamically required key is missing.
   *
   * @var string
   */
  public string $dynamicMissingRequiredKeyMessage = "'@key' is a required key because @dynamic_type_property_path is @dynamic_type_property_value (see config schema type @resolved_dynamic_type).";

  /**
   * The error message if the array being validated is a list.
   *
   * @var string
   */
  public string $indexedArrayMessage = 'Numerically indexed arrays are not allowed.';

  /**
   * Keys which are allowed in the validated array, or `<infer>` to auto-detect.
   *
   * @var array|string
   */
  public array|string $allowedKeys;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'allowedKeys';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['allowedKeys'];
  }

  /**
   * Returns the list of valid keys.
   *
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
   *   The current execution context.
   *
   * @return string[]
   *   The keys that will be considered valid.
   */
  public function getAllowedKeys(ExecutionContextInterface $context): array {
    $mapping = $context->getObject();
    assert($mapping instanceof Mapping);
    $resolved_type = $mapping->getDataDefinition()->getDataType();
    $valid_keys = $mapping->getValidKeys();

    // If we were given an explicit array of allowed keys, return that.
    if (is_array($this->allowedKeys)) {
      if (!empty(array_diff($this->allowedKeys, $valid_keys))) {
        throw new InvalidArgumentException(sprintf(
          'The type \'%s\' explicitly specifies the allowed keys (%s), but they are not a subset of the statically defined mapping keys in the schema (%s).',
          $resolved_type,
          implode(', ', $this->allowedKeys),
          implode(', ', $valid_keys)
        ));
      }
      return array_intersect($valid_keys, $this->allowedKeys);
    }
    // The only other value we'll accept is the string `<infer>`.
    elseif ($this->allowedKeys === '<infer>') {
      return $mapping->getValidKeys();
    }
    throw new InvalidArgumentException("'$this->allowedKeys' is not a valid set of allowed keys.");
  }

}
