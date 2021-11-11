<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Html;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * CKEditor 5 element validator.
 *
 * @internal
 */
class CKEditor5ElementConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown when the given constraint is not supported by this validator.
   */
  public function validate($element, $constraint) {
    if (!$constraint instanceof CKEditor5ElementConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\CKEditor5Element');
    }
    $body_child_nodes = Html::load(str_replace('>', ' />', trim($element)))->getElementsByTagName('body')->item(0)->childNodes;

    if ($body_child_nodes->count() !== 1 || $body_child_nodes->item(0)->nodeType !== XML_ELEMENT_NODE) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('%provided_element', $element)
        ->addViolation();
    }
  }

}
