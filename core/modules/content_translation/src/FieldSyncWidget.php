<?php

declare(strict_types=1);

namespace Drupal\content_translation;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Field Sync Widget for content_translation.
 */
class FieldSyncWidget {

  use StringTranslationTrait;

  public function __construct(
    protected readonly FieldTypePluginManagerInterface $fieldTypePluginManager,
  ) {}

  /**
   * Returns a form element to configure field synchronization.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   A field definition object.
   * @param string $element_name
   *   (optional) The element name, which is added to drupalSettings so that
   *   javascript can manipulate the form element.
   *
   * @return array
   *   A form element to configure field synchronization.
   */
  public function widget(FieldDefinitionInterface $field, $element_name = 'third_party_settings[content_translation][translation_sync]'): array {
    // No way to store field sync information on this field.
    if (!($field instanceof ThirdPartySettingsInterface)) {
      return [];
    }

    $element = [];
    $definition = $this->fieldTypePluginManager->getDefinition($field->getType());
    $column_groups = $definition['column_groups'];
    if (!empty($column_groups) && count($column_groups) > 1) {
      $options = [];
      $default = [];
      $require_all_groups_for_translation = [];

      foreach ($column_groups as $group => $info) {
        $options[$group] = $info['label'];
        $default[$group] = !empty($info['translatable']) ? $group : FALSE;
        if (!empty($info['require_all_groups_for_translation'])) {
          $require_all_groups_for_translation[] = $group;
        }
      }

      $default = $field->getThirdPartySetting('content_translation', 'translation_sync', $default);

      $element = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Translatable elements'),
        '#options' => $options,
        '#default_value' => $default,
      ];

      if ($require_all_groups_for_translation) {
        // The actual checkboxes are sometimes rendered separately and the
        // parent element is ignored. Attach to the first option to ensure that
        // this does not get lost.
        $element[key($options)]['#attached']['drupalSettings']['contentTranslationDependentOptions'] = [
          'dependent_selectors' => [
            $element_name => $require_all_groups_for_translation,
          ],
        ];
        $element[key($options)]['#attached']['library'][] = 'content_translation/drupal.content_translation.admin';
      }
    }

    return $element;
  }

}
