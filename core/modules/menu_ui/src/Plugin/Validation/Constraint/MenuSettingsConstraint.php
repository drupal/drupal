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

}
