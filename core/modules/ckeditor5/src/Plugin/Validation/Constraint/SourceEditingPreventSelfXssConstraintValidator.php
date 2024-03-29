<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\ckeditor5\HTMLRestrictions;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Ensures Source Editing cannot be configured to allow self-XSS.
 *
 * @internal
 */
class SourceEditingPreventSelfXssConstraintValidator extends ConstraintValidator {

  use TextEditorObjectDependentValidatorTrait;

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown when the given constraint is not supported by this validator.
   */
  public function validate($value, Constraint $constraint): void {
    if (!$constraint instanceof SourceEditingPreventSelfXssConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\SourceEditingPreventSelfXssConstraint');
    }
    if (empty($value)) {
      return;
    }

    $restrictions = HTMLRestrictions::fromString($value);
    // @todo Remove this early return in
    //   https://www.drupal.org/project/drupal/issues/2820364. It is only
    //   necessary because CKEditor5ElementConstraintValidator does not run
    //   before this, which means that this validator cannot assume it receives
    //   valid values.
    if ($restrictions->allowsNothing() || count($restrictions->getAllowedElements()) > 1) {
      return;
    }

    // This validation constraint only validates attributes, not tags; so if all
    // attributes are allowed (TRUE) or no attributes are allowed (FALSE),
    // return early. Only proceed when some attributes are allowed (an array).
    $allowed_elements = $restrictions->getAllowedElements(FALSE);
    assert(count($allowed_elements) === 1);
    $tag = array_key_first($allowed_elements);
    $attribute_restrictions = $allowed_elements[$tag];
    if (!is_array($attribute_restrictions)) {
      return;
    }

    $text_editor = $this->createTextEditorObjectFromContext();
    $text_format_allowed_elements = HTMLRestrictions::fromTextFormat($text_editor->getFilterFormat())
      ->getAllowedElements();
    // Any XSS-prevention related measures imposed by filter plugins are relayed
    // through their ::getHtmlRestrictions() return value. The global attribute
    // `*` HTML tag allows attributes to be forbidden.
    // @see https://html.spec.whatwg.org/multipage/dom.html#global-attributes
    // @see \Drupal\ckeditor5\HTMLRestrictions::validateAllowedRestrictionsPhase4()
    // @see \Drupal\filter\Plugin\Filter\FilterHtml::getHTMLRestrictions()
    $forbidden_attributes = [];
    if (array_key_exists('*', $text_format_allowed_elements)) {
      $forbidden_attributes = array_keys(array_filter($text_format_allowed_elements['*'], function ($attribute_value_restriction, string $attribute_name) {
        return $attribute_value_restriction === FALSE;
      }, ARRAY_FILTER_USE_BOTH));
    }

    foreach ($forbidden_attributes as $forbidden_attribute_name) {
      // Forbidden attributes not containing wildcards, such as `style`.
      if (!self::isWildcardAttributeName($forbidden_attribute_name)) {
        if (array_key_exists($forbidden_attribute_name, $attribute_restrictions)) {
          $this->context->buildViolation($constraint->message)
            ->setParameter('%dangerous_tag', $value)
            ->addViolation();
        }
      }
      // Forbidden attributes containing wildcards such as `on*`.
      else {
        $regex = self::getRegExForWildCardAttributeName($forbidden_attribute_name);
        if (!empty(preg_grep($regex, array_keys($attribute_restrictions)))) {
          $this->context->buildViolation($constraint->message)
            ->setParameter('%dangerous_tag', $value)
            ->addViolation();
        }
      }
    }
  }

  /**
   * Checks whether the given attribute name contains a wildcard, e.g. `data-*`.
   *
   * @param string $attribute_name
   *   The attribute name to check.
   *
   * @return bool
   *   Whether the given attribute name contains a wildcard.
   */
  private static function isWildcardAttributeName(string $attribute_name): bool {
    assert($attribute_name !== '*');
    return str_contains($attribute_name, '*');
  }

  /**
   * Computes a regular expression for matching a wildcard attribute name.
   *
   * @param string $wildcard_attribute_name
   *   The wildcard attribute name for which to compute a regular expression.
   *
   * @return string
   *   The computed regular expression.
   */
  private static function getRegExForWildCardAttributeName(string $wildcard_attribute_name): string {
    assert(self::isWildcardAttributeName($wildcard_attribute_name));
    return '/^' . str_replace('*', '.*', $wildcard_attribute_name) . '$/';
  }

}
