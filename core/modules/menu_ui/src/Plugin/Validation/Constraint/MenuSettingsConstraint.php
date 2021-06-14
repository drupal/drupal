<?php

namespace Drupal\menu_ui\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for changing the menu settings in pending revisions.
 *
 * @Constraint(
 *   id = "MenuSettings",
 *   label = @Translation("Menu settings.", context = "Validation"),
 * )
 */
class MenuSettingsConstraint extends Constraint {

  public $message = 'You can only change the menu settings for the <em>published</em> version of this content.';
  public $messageWeight = 'You can only change the menu link weight for the <em>published</em> version of this content.';
  public $messageParent = 'You can only change the parent menu link for the <em>published</em> version of this content.';
  public $messageRemove = 'You can only remove the menu link in the <em>published</em> version of this content.';

}
