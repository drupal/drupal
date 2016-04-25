<?php

namespace Drupal\Core\Annotation;

use Drupal\Component\Annotation\AnnotationBase;

/**
 * Defines an annotation object for strings that require plural forms.
 *
 * Note that the return values for both 'singular' and 'plural' keys needs to be
 * passed to
 * \Drupal\Core\StringTranslation\TranslationInterface::formatPlural().
 *
 * For example, the annotation can look like this:
 * @code
 *   label_count = @ PluralTranslation(
 *     singular = "@count item",
 *     plural = "@count items",
 *     context = "cart_items",
 *   ),
 * @endcode
 * Remove spaces after @ in your actual plugin - these are put into this sample
 * code so that it is not recognized as annotation.
 *
 * Code samples that make use of this annotation class and the definition sample
 * above:
 * @code
 *   // Returns: 1 item
 *   $entity_type->getCountLabel(1);
 *
 *   // Returns: 5 items
 *   $entity_type->getCountLabel(5);
 * @endcode
 *
 * @see \Drupal\Core\Entity\EntityType::getSingularLabel()
 * @see \Drupal\Core\Entity\EntityType::getPluralLabel()
 * @see \Drupal\Core\Entity\EntityType::getCountLabel()
 *
 * @ingroup plugin_translatable
 *
 * @Annotation
 */
class PluralTranslation extends AnnotationBase {

  /**
   * The string for the singular case.
   *
   * @var string
   */
  protected $singular;

  /**
   * The string for the plural case.
   *
   * @var string
   */
  protected $plural;

  /**
   * The context the source strings belong to.
   *
   * @var string
   */
  protected $context;

  /**
   * Constructs a new class instance.
   *
   * @param array $values
   *   An associative array with the following keys:
   *   - singular: The string for the singular case.
   *   - plural: The string for the plural case.
   *   - context: The context the source strings belong to.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the keys 'singular' or 'plural' are missing from the $values
   *   array.
   */
  public function __construct(array $values) {
    if (!isset($values['singular'])) {
      throw new \InvalidArgumentException('Missing "singular" value in the PluralTranslation annotation');
    }
    if (!isset($values['plural'])) {
      throw new \InvalidArgumentException('Missing "plural" value in the PluralTranslation annotation');
    }

    $this->singular = $values['singular'];
    $this->plural = $values['plural'];
    if (isset($values['context'])) {
      $this->context = $values['context'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    return [
      'singular' => $this->singular,
      'plural' => $this->plural,
      'context' => $this->context,
    ];
  }

}
