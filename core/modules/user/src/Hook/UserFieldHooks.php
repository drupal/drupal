<?php

declare(strict_types=1);

namespace Drupal\user\Hook;

use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Field and Field UI hook implementations for User module.
 */
class UserFieldHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly FieldTypePluginManagerInterface $fieldTypeManager,
  ) {}

  /**
   * Implements hook_field_ui_preconfigured_options_alter().
   */
  #[Hook('field_ui_preconfigured_options_alter')]
  public function preConfiguredDescription(array &$options, $field_type): void {
    // If the field is not an "entity_reference"-based field, then bail out.
    $class = $this->fieldTypeManager->getPluginClass($field_type);
    if (!is_a($class, EntityReferenceItem::class, TRUE)) {
      return;
    }

    // Set the description for the "Add field" page.
    if (!empty($options['user'])) {
      $options['user']['description'] = [
        $this->t('Refer to any user on the site'),
        $this->t("Examples: show the user's email address or a link to their contact form"),
      ];
    }
  }

}
