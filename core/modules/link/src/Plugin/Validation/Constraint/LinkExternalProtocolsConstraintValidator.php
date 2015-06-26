<?php

/**
 * @file
 * Contains \Drupal\link\Plugin\Validation\Constraint\LinkExternalProtocolsConstraintValidator.
 */

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Validates the LinkExternalProtocols constraint.
 */
class LinkExternalProtocolsConstraintValidator implements ConstraintValidatorInterface {

  /**
   * Stores the validator's state during validation.
   *
   * @var \Symfony\Component\Validator\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (isset($value)) {
      try {
        /** @var \Drupal\Core\Url $url */
        $url = $value->getUrl();
      }
      // If the URL is malformed this constraint cannot check further.
      catch (\InvalidArgumentException $e) {
        return;
      }
      // Disallow external URLs using untrusted protocols.
      if ($url->isExternal() && !in_array(parse_url($url->getUri(), PHP_URL_SCHEME), UrlHelper::getAllowedProtocols())) {
        $this->context->addViolation($constraint->message, array('@uri' => $value->uri));
      }
    }
  }

}
