<?php

namespace Drupal\menu_ui\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for changing the menu settings in pending revisions.
 *
 * @deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Menu
 *   settings validation is now handled as a form validation callback in
 *   \Drupal\menu_ui\Hook\MenuUiHooks::formNodeFormValidate().
 *
 * @see https://www.drupal.org/node/3606616
 */
#[Constraint(
  id: 'MenuSettings',
  label: new TranslatableMarkup('Menu settings.', [], ['context' => 'Validation'])
)]
class MenuSettingsConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $message = 'You can only change the menu settings for the <em>published</em> version of this content.',
    public $messageWeight = 'You can only change the menu link weight for the <em>published</em> version of this content.',
    public $messageParent = 'You can only change the parent menu link for the <em>published</em> version of this content.',
    public $messageRemove = 'You can only remove the menu link in the <em>published</em> version of this content.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
