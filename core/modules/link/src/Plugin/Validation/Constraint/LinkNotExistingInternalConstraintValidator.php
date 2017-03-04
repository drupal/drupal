<?php

namespace Drupal\link\Plugin\Validation\Constraint;

use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the LinkNotExistingInternal constraint.
 */
class LinkNotExistingInternalConstraintValidator extends ConstraintValidator {

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

      if ($url->isRouted()) {
        $allowed = TRUE;
        try {
          $url->toString();
        }
        // The following exceptions are all possible during URL generation, and
        // should be considered as disallowed URLs.
        catch (RouteNotFoundException $e) {
          $allowed = FALSE;
        }
        catch (InvalidParameterException $e) {
          $allowed = FALSE;
        }
        catch (MissingMandatoryParametersException $e) {
          $allowed = FALSE;
        }
        if (!$allowed) {
          $this->context->addViolation($constraint->message, ['@uri' => $value->uri]);
        }
      }
    }
  }

}
