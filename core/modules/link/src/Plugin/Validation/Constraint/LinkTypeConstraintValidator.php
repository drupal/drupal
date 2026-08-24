<?php

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\link\LinkItemInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Constraint validator for links receiving data allowed by its settings.
 */
class LinkTypeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    if (!$value instanceof LinkItemInterface) {
      throw new UnexpectedValueException($value, LinkItemInterface::class);
    }
    if ($value->isEmpty()) {
      return;
    }

    // Try to resolve the given URI to a URL. It may fail if it's schemeless.
    try {
      $url = $value->getUrl();
      $field_definition = $value->getFieldDefinition();

      // If the link field doesn't support both internal and external links,
      // check whether the URL (a resolved URI) is in fact violating either
      // restriction.
      $link_type = $field_definition->getSetting('link_type');
      if ($url->isExternal() && !($link_type & LinkItemInterface::LINK_EXTERNAL)) {
        $this->context->buildViolation($constraint->onlyInternalMessage, [
          '@uri' => $value->uri,
          '@field-label' => $field_definition->getLabel(),
        ])
          ->atPath('uri')
          ->addViolation();
      }
      elseif (!$url->isExternal() && !($link_type & LinkItemInterface::LINK_INTERNAL)) {
        $this->context->buildViolation($constraint->onlyExternalMessage, [
          '@uri' => $value->uri,
          '@field-label' => $field_definition->getLabel(),
        ])
          ->atPath('uri')
          ->addViolation();
      }
    }
    catch (\InvalidArgumentException) {
      $this->context->buildViolation($constraint->invalidMessage, ['@uri' => $value->uri])
        ->atPath('uri')
        ->addViolation();
    }
  }

}
