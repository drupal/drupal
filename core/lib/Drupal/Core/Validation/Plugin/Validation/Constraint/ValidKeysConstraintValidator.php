<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\Config\Schema\SequenceDataDefinition;
use Drupal\Core\Config\TypedConfigManager;
use Drupal\Core\TypedData\MapDataDefinition;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the ValidKeys constraint.
 */
class ValidKeysConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint) {
    assert($constraint instanceof ValidKeysConstraint);

    if (!is_array($value)) {
      // If the value is NULL, then the `NotNull` constraint validator will
      // set the appropriate validation error message.
      // @see \Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraintValidator
      if ($value === NULL) {
        return;
      }
      throw new UnexpectedTypeException($value, 'array');
    }

    // Indexed arrays are invalid by definition. array_is_list() returns TRUE
    // for empty arrays, so only do this check if $value is not empty.
    if ($value && array_is_list($value)) {
      $this->context->addViolation($constraint->indexedArrayMessage);
      return;
    }

    $mapping = $this->context->getObject();
    assert($mapping instanceof Mapping);
    $resolved_type = $mapping->getDataDefinition()->getDataType();

    $valid_keys = $constraint->getAllowedKeys($this->context);
    $dynamically_valid_keys = $mapping->getDynamicallyValidKeys();
    $all_dynamically_valid_keys = array_merge(...array_values($dynamically_valid_keys));

    // Statically valid: keys that are valid for all possible types matching the
    // type definition of this mapping.
    // For example, `block.block.*:settings` has the following statically valid
    // keys: id, label, label_display, provider, status, info, view_mode and
    // context_mapping.
    // @see \Drupal\KernelTests\Config\Schema\MappingTest::providerMappingInterpretation()
    $invalid_keys = array_diff(array_keys($value), $valid_keys, $all_dynamically_valid_keys);
    foreach ($invalid_keys as $key) {
      $this->context->buildViolation($constraint->invalidKeyMessage)
        ->setParameter('@key', $key)
        ->atPath((string) $key)
        ->setInvalidValue($key)
        ->addViolation();
    }
    // Dynamically valid: keys that are valid not for all possible types, but
    // for the actually resolved type definition of this mapping (in addition to
    // the statically valid keys).
    // @see \Drupal\Core\Config\Schema\Mapping::getDynamicallyValidKeys()
    if (!empty($all_dynamically_valid_keys)) {
      // For example, `block.block.*:settings` has the following dynamically valid
      // keys when the block plugin is `system_branding_block`:
      // - use_site_logo
      // - use_site_name
      // - use_site_slogan
      // @see \Drupal\KernelTests\Config\Schema\MappingTest::providerMappingInterpretation()
      $resolved_type_dynamically_valid_keys = $dynamically_valid_keys[$resolved_type] ?? [];
      // But if the `local_tasks_block` plugin is being used, then the
      // dynamically valid keys are:
      // - primary
      // - secondary
      // And for the `block.settings.search_form_block` plugin the dynamically
      // valid keys are:
      // - page_id
      // To help determine which keys are dynamically invalid, gather all keys
      // except for those for the actual resolved type of this mapping.
      // @see \Drupal\Core\Config\Schema\Mapping::getPossibleTypes()
      $other_types_valid_keys = array_diff($all_dynamically_valid_keys, $resolved_type_dynamically_valid_keys);
      $dynamically_invalid_keys = array_intersect(array_keys($value), $other_types_valid_keys);
      foreach ($dynamically_invalid_keys as $key) {
        $this->context->addViolation($constraint->dynamicInvalidKeyMessage, ['@key' => $key] + self::getDynamicMessageParameters($mapping));
      }
    }

    // All keys are optional by default (meaning they can be omitted). This is
    // unintuitive and contradicts Drupal core's documentation.
    // @see https://www.drupal.org/node/2264179
    // To gradually evolve configuration schemas in the Drupal ecosystem to be
    // validatable, this needs to be clarified in a non-disruptive way. Any
    // config schema type definition — that is, a top-level entry in a
    // *.schema.yml file — can opt into stricter behavior, whereby a key is
    // required unless it specifies `requiredKey: false`, by adding
    // `FullyValidatable` as a top-level validation constraint.
    // @see https://www.drupal.org/node/3364108
    // @see https://www.drupal.org/node/3364109
    $root_type = $this->context->getObject()->getRoot()->getDataDefinition()->getDataType();
    $root_type_has_opted_in = FALSE;
    foreach ($this->context->getObject()->getRoot()->getConstraints() as $c) {
      if ($c instanceof FullyValidatableConstraint) {
        $root_type_has_opted_in = TRUE;
        break;
      }
    }
    // Return early: do not generate validation errors for keys that are
    // required.
    if (!$root_type_has_opted_in) {
      return;
    }
    // If this is a dynamically typed property path, then not only must the
    // (absolute) root type be considered, but also the (relative) static root
    // type: the resolved type.
    // For example, `block.block.*:settings` has a dynamic type defined:
    // `block.settings.[%parent.plugin]`, but `block.block.*:plugin` does not.
    // Consequently, the value at the `plugin` property path depends only on the
    // `block.block.*` config schema type and hence only that config schema type
    // must have the `FullyValidatable` constraint, because it defines which
    // keys are required.
    // In contrast, the `block.block.*:settings` property path depends on
    // whichever dynamic type `block.settings.[%parent.plugin]` resolved to, to
    // be able to know which keys are required. Therefore that resolved type
    // determines which keys are required and whether it is fully validatable.
    // So for example the `block.settings.system_branding_block` config schema
    // type would also need to have the `FullyValidatable` constraint to
    // consider its schema-defined keys to be required:
    // - use_site_logo
    // - use_site_name
    // - use_site_slogan
    $static_type_root = TypedConfigManager::getStaticTypeRoot($this->context->getObject());
    $static_type_root_type = $static_type_root->getDataDefinition()->getDataType();
    if ($root_type !== $static_type_root_type) {
      $root_type_has_opted_in = FALSE;
      foreach ($static_type_root->getConstraints() as $c) {
        if ($c instanceof FullyValidatableConstraint) {
          $root_type_has_opted_in = TRUE;
          break;
        }
      }
      // Return early: do not generate validation errors for keys that are
      // required.
      if (!$root_type_has_opted_in) {
        return;
      }
    }

    $required_keys = array_intersect($mapping->getRequiredKeys(), $constraint->getAllowedKeys($this->context));

    // Statically required: same principle as for "statically valid" above, but
    // this time restricted to the subset of statically valid keys that do not
    // have `requiredKey: false`.
    $statically_required_keys = array_diff($required_keys, $all_dynamically_valid_keys);
    $missing_keys = array_diff($statically_required_keys, array_keys($value));
    foreach ($missing_keys as $key) {
      $this->context->addViolation($constraint->missingRequiredKeyMessage, ['@key' => $key]);
    }
    // Dynamically required: same principle as for "dynamically valid" above,
    // but this time restricted to the subset of dynamically valid keys that do
    // not have `requiredKey: false`.
    $dynamically_required_keys = array_intersect($required_keys, $all_dynamically_valid_keys);
    $missing_dynamically_required_keys = array_diff($dynamically_required_keys, array_keys($value));
    foreach ($missing_dynamically_required_keys as $key) {
      $this->context->addViolation($constraint->dynamicMissingRequiredKeyMessage, ['@key' => $key] + self::getDynamicMessageParameters($mapping));
    }
  }

  /**
   * Computes message parameters for dynamic type violations.
   *
   * @param \Drupal\Core\Config\Schema\Mapping $mapping
   *   A `type: mapping` instance, with values.
   *
   * @return array
   *   An array containing the following message parameters:
   *   - '@unresolved_dynamic_type': unresolved dynamic type
   *   - '@resolved_dynamic_type': resolved dynamic type
   *   - '@dynamic_type_property_path': (relative) property path of the condition
   *   - '@dynamic_type_property_value': value of the condition
   *
   * @see \Drupal\Core\Validation\Plugin\Validation\Constraint\ValidKeysConstraint::$dynamicInvalidKeyMessage
   */
  protected static function getDynamicMessageParameters(Mapping $mapping): array {
    $definition = $mapping->getDataDefinition();
    assert($definition instanceof MapDataDefinition);
    $definition = $definition->toArray();
    assert(array_key_exists('mapping', $definition));

    // The original mapping definition is used to determine the unresolved type.
    // e.g. if $unresolved_type is …
    // 1. `editor.settings.[%parent.editor]`, then $resolved_type could perhaps
    //    `editor.settings.ckeditor5`, `editor.settings.unicorn`, etc.
    // 2. `block.settings.[%parent.plugin]`, then $resolved_type could perhaps
    //    be `block.settings.*`, `block.settings.system_branding_block`, etc.
    $parent_data_def = $mapping->getParent()->getDataDefinition();
    $unresolved_type = match (TRUE) {
      $parent_data_def instanceof MapDataDefinition => $parent_data_def->toArray()['mapping'][$mapping->getName()]['type'],
      $parent_data_def instanceof SequenceDataDefinition => $parent_data_def->toArray()['sequence']['type'],
      default => throw new \LogicException('Invalid config schema detected.'),
    };
    $resolved_type = $definition['type'];

    // $unresolved_type must be a dynamic type and the resolved type must be
    // different and not be dynamic.
    // @see \Drupal\Core\Config\TypedConfigManager::buildDataDefinition()
    assert(strpos($unresolved_type, ']'));
    assert($unresolved_type !== $resolved_type);
    assert(!strpos($resolved_type, ']'));

    $message_parameters = [
      '@unresolved_dynamic_type' => $unresolved_type,
      '@resolved_dynamic_type' => $resolved_type,
    ];

    $config = $mapping->getRoot();
    // Every config object is a mapping.
    assert($config instanceof Mapping);
    // Find the relative property path where this mapping starts.
    assert(str_starts_with($mapping->getPropertyPath(), $config->getName() . '.'));
    $property_path_mapping = substr($mapping->getPropertyPath(), strlen($config->getName()) + 1);

    // Extract the expressions stored in the dynamic type name.
    $matches = [];
    // @see \Drupal\Core\Config\TypedConfigManager::replaceDynamicTypeName()
    $result = preg_match("/\[(.*)\]/U", $unresolved_type, $matches);
    assert($result === 1);
    // @see \Drupal\Core\Config\TypedConfigManager::replaceExpression()
    $expression = $matches[1];
    // From the expression, extract the instructions for where to retrieve a value.
    $instructions = explode('.', $expression);

    // Determine the property path to the configuration key that has determined
    // this type.
    // @see \Drupal\Core\Config\TypedConfigManager::replaceExpression()
    $property_path_parts = explode('.', $property_path_mapping);
    // @see \Drupal\Core\Config\Schema\Mapping::getDynamicallyValidKeys()
    assert(!in_array('%type', $instructions, TRUE));

    // The %key instruction can only be used on its own. In this case, there is
    // no need to fetch a value, only the string that was used as the key is
    // responsible for determining the mapping type.
    if ($instructions === ['%key']) {
      $key = array_pop($property_path_parts);
      array_push($property_path_parts, '%key');
      $resolved_property_path = implode('.', $property_path_parts);
      return $message_parameters + [
        '@dynamic_type_property_path' => $resolved_property_path,
        '@dynamic_type_property_value' => $key,
      ];
    }

    // Do not replace variables, do not traverse the tree of data, but instead
    // resolve the property path that contains the value causing this particular
    // type to be selected.
    while ($instructions) {
      $instruction = array_shift($instructions);
      // Go up one level: remove the last part of the property path.
      if ($instruction === '%parent') {
        array_pop($property_path_parts);
      }
      // Go down one level: append the given key.
      else {
        array_push($property_path_parts, $instruction);
      }
    }
    $resolved_property_path = implode('.', $property_path_parts);
    $message_parameters += [
      '@dynamic_type_property_path' => $resolved_property_path,
    ];

    // Determine the corresponding value for that property path.
    $val = $config->get($resolved_property_path)->getValue();
    // @see \Drupal\Core\Config\TypedConfigManager::replaceExpression()
    $val = is_bool($val) ? (int) $val : $val;
    return $message_parameters + [
      '@dynamic_type_property_value' => $val,
    ];
  }

}
