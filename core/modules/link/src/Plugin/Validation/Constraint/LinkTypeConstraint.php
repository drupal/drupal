<?php

/**
 * @file
 * Contains \Drupal\link\Plugin\Validation\Constraint\LinkTypeConstraint.
 */

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\link\LinkItemInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Validation constraint for links receiving data allowed by its settings.
 *
 * @Plugin(
 *   id = "LinkType",
 *   label = @Translation("Link data valid for link type.", context = "Validation"),
 * )
 */
class LinkTypeConstraint extends Constraint implements ConstraintValidatorInterface {

  public $message = 'The URL %url is not valid.';

  /**
   * @var \Symfony\Component\Validator\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritDoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return get_class($this);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (isset($value)) {
      $url_is_valid = FALSE;
      /** @var $link_item \Drupal\link\LinkItemInterface */
      $link_item = $value;
      $link_type = $link_item->getFieldDefinition()->getSetting('link_type');
      $url = $link_item->getUrl(TRUE);

      if ($url) {
        $url_is_valid = TRUE;
        if ($url->isExternal() && !($link_type & LinkItemInterface::LINK_EXTERNAL)) {
          $url_is_valid = FALSE;
        }
        if (!$url->isExternal() && !($link_type & LinkItemInterface::LINK_INTERNAL)) {
          $url_is_valid = FALSE;
        }
      }
      if (!$url_is_valid) {
        $this->context->addViolation($this->message, array('%url' => $link_item->uri));
      }
    }
  }
}

