<?php

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\link\LinkItemInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for links receiving data allowed by its settings.
 */
class LinkTypeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    if (isset($value)) {
      $uri_is_valid = TRUE;

      /** @var \Drupal\link\LinkItemInterface $link_item */
      $link_item = $value;
      $link_type = $link_item->getFieldDefinition()->getSetting('link_type');

      // Try to resolve the given URI to a URL. It may fail if it's schemeless.
      try {
        $url = $link_item->getUrl();
      }
      catch (\InvalidArgumentException $e) {
        $uri_is_valid = FALSE;
      }

      // If the link field doesn't support both internal and external links,
      // check whether the URL (a resolved URI) is in fact violating either
      // restriction.
      if ($uri_is_valid && $link_type !== LinkItemInterface::LINK_GENERIC) {
        if (!($link_type & LinkItemInterface::LINK_EXTERNAL) && $url->isExternal()) {
          $uri_is_valid = FALSE;
        }
        if (!($link_type & LinkItemInterface::LINK_INTERNAL) && !$url->isExternal()) {
          $uri_is_valid = FALSE;
        }
      }

      if (!$uri_is_valid) {
        $this->context->addViolation($constraint->message, ['@uri' => $link_item->uri]);
      }
    }
  }

}
