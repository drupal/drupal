<?php

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\link\LinkItemInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates the LinkNotExistingInternal constraint.
 */
class LinkNotExistingInternalConstraintValidator extends ConstraintValidator {

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

    try {
      /** @var \Drupal\Core\Url $url */
      $url = $value->getUrl();
    }
    // If the URL is malformed this constraint cannot check further.
    catch (\InvalidArgumentException) {
      return;
    }

    if ($url->isRouted()) {
      $allowed = TRUE;
      try {
        $url->toString(TRUE);
      }
      // The following exceptions are all possible during URL generation, and
      // should be considered as disallowed URLs.
      catch (RouteNotFoundException) {
        $allowed = FALSE;
      }
      catch (InvalidParameterException) {
        $allowed = FALSE;
      }
      catch (MissingMandatoryParametersException) {
        $allowed = FALSE;
      }
      if (!$allowed) {
        $this->context->buildViolation($constraint->message, ['@uri' => $value->uri])
          ->atPath('uri')
          ->addViolation();
      }
    }
  }

}
