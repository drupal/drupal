<?php

declare(strict_types=1);

namespace Drupal\taxonomy\Hook;

use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Field and Field UI hook implementations for Taxonomy module.
 */
class TaxonomyFieldHooks {

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
    if (!empty($options['taxonomy_term'])) {
      $options['taxonomy_term']['description'] = [
        $this->t('Attach a term from any vocabulary'),
        $this->t('Examples: free tag, site section, article topic'),
      ];
    }
  }

}
