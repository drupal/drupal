<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Styles can only be specified for HTML5 tags and extra classes.
 *
 * @internal
 */
#[Constraint(
  id: 'StyleSensibleElement',
  label: new TranslatableMarkup('Styles can only be specified for already supported tags.', [], ['context' => 'Validation'])
)]
class StyleSensibleElementConstraint extends SymfonyConstraint {

  /**
   * When a style is defined for a non-HTML5 tag.
   *
   * @var string
   */
  public $nonHtml5TagMessage = 'A style can only be specified for an HTML 5 tag. <code>@tag</code> is not an HTML5 tag.';

  /**
   * When a Style is defined with classes supported by an enabled plugin.
   *
   * @var string
   */
  public $conflictingEnabledPluginMessage = 'A style must only specify classes not supported by other plugins. The <code>@classes</code> classes on <code>@tag</code> are already supported by the enabled %plugin plugin.';

  /**
   * When a Style is defined with classes supported by a disabled plugin.
   *
   * @var string
   */
  public $conflictingDisabledPluginMessage = 'A style must only specify classes not supported by other plugins. The <code>@classes</code> classes on <code>@tag</code> are supported by the %plugin plugin. Remove this style and enable that plugin instead.';

  /**
   * When a Style is defined for a plugin that does not yet support Style.
   *
   * @var string
   */
  public $unsupportedTagMessage = 'The <code>@tag</code> tag is not yet supported by the Style plugin.';

}
