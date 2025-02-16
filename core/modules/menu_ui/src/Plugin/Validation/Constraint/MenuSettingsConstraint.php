<?php

namespace Drupal\menu_ui\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for changing the menu settings in pending revisions.
 */
#[Constraint(
  id: 'MenuSettings',
  label: new TranslatableMarkup('Menu settings.', [], ['context' => 'Validation'])
)]
class MenuSettingsConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'You can only change the menu settings for the <em>published</em> version of this content.';

  /**
   * The violation message when the weight cannot be changed.
   *
   * @var string
   */
  public $messageWeight = 'You can only change the menu link weight for the <em>published</em> version of this content.';

  /**
   * The violation message when changing the parent for a unpublished content.
   *
   * @var string
   */
  public $messageParent = 'You can only change the parent menu link for the <em>published</em> version of this content.';

  /**
   * The violation message when removing a menu link for unpublished content.
   *
   * @var string
   */
  public $messageRemove = 'You can only remove the menu link in the <em>published</em> version of this content.';

}
