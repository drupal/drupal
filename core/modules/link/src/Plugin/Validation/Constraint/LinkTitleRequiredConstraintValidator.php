<?php

declare(strict_types=1);

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\link\LinkItemInterface;
use Drupal\link\LinkTitleVisibility;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Constraint validator for link title subfields if a URL was entered.
 */
class LinkTitleRequiredConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    if (!$value instanceof LinkItemInterface) {
      throw new UnexpectedValueException($value, LinkItemInterface::class);
    }

    /** @var \Drupal\link\LinkItemInterface $link_item */
    $link_item = $value;
    $title_setting = $link_item->getFieldDefinition()->getSetting('title');
    $title_visibility = LinkTitleVisibility::tryFrom((int) $title_setting);

    if ($title_visibility === LinkTitleVisibility::Required
      && $link_item->uri !== ''
      && $link_item->title === '') {
      $this->context->buildViolation($constraint->message)
        ->atPath('title')
        ->addViolation();
    }
  }

}
