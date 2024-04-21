<?php

namespace Drupal\media\Plugin\Validation\Constraint;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\media\MediaTypeInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates media mappings.
 */
class MediaMappingsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    if (!$constraint instanceof MediaMappingsConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\MediaMappingsConstraint');
    }
    if (!$value instanceof MediaTypeInterface) {
      throw new UnexpectedTypeException($value, MediaTypeInterface::class);
    }
    // The source field cannot be the target of a field mapping because that
    // would cause it to be overwritten, possibly with invalid data. This is
    // also enforced in the UI.
    if (is_array($value->getFieldMap())) {
      try {
        $source_field_name = $value->getSource()
          ->getSourceFieldDefinition($value)
          ?->getName();

        if (in_array($source_field_name, $value->getFieldMap(), TRUE)) {
          $this->context->addViolation($constraint->invalidMappingMessage, [
            '@source_field_name' => $source_field_name,
          ]);
        }
      }
      catch (PluginException) {
        // The source references an invalid plugin.
      }
    }
  }

}
