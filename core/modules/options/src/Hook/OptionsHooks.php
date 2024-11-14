<?php

namespace Drupal\options\Hook;

use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for options.
 */
class OptionsHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.options':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Options module allows you to create fields where data values are selected from a fixed list of options. Usually these items are entered through a select list, checkboxes, or radio buttons. See the <a href=":field">Field module help</a> and the <a href=":field_ui">Field UI help</a> pages for general information on fields and how to create and manage them. For more information, see the <a href=":options_do">online documentation for the Options module</a>.', [
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
          ':field_ui' => \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', [
            'name' => 'field_ui',
          ])->toString() : '#',
          ':options_do' => 'https://www.drupal.org/documentation/modules/options',
        ]) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Managing and displaying list fields') . '</dt>';
        $output .= '<dd>' . t('The <em>settings</em> and the <em>display</em> of the list fields can be configured separately. See the <a href=":field_ui">Field UI help</a> for more information on how to manage fields and their display.', [
          ':field_ui' => \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', [
            'name' => 'field_ui',
          ])->toString() : '#',
        ]) . '</dd>';
        $output .= '<dt>' . t('Defining option keys and labels') . '</dt>';
        $output .= '<dd>' . t('When you define the list options you can define a key and a label for each option in the list. The label will be shown to the users while the key gets stored in the database.') . '</dd>';
        $output .= '<dt>' . t('Choosing list field type') . '</dt>';
        $output .= '<dd>' . t('There are three types of list fields, which store different types of data: <em>float</em>, <em>integer</em> or, <em>text</em>. The <em>float</em> type allows storing approximate decimal values. The <em>integer</em> type allows storing whole numbers, such as years (for example, 2012) or values (for example, 1, 2, 5, 305). The <em>text</em> list field type allows storing text values. No matter which type of list field you choose, you can define whatever labels you wish for data entry.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for 'field_storage_config'.
   */
  #[Hook('field_storage_config_update')]
  public function fieldStorageConfigUpdate(FieldStorageConfigInterface $field_storage) {
    drupal_static_reset('options_allowed_values');
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for 'field_storage_config'.
   */
  #[Hook('field_storage_config_delete')]
  public function fieldStorageConfigDelete(FieldStorageConfigInterface $field_storage) {
    drupal_static_reset('options_allowed_values');
  }

  /**
   * Implements hook_field_storage_config_update_forbid().
   */
  #[Hook('field_storage_config_update_forbid')]
  public function fieldStorageConfigUpdateForbid(FieldStorageConfigInterface $field_storage, FieldStorageConfigInterface $prior_field_storage) {
    if ($field_storage->getTypeProvider() == 'options' && $field_storage->hasData()) {
      // Forbid any update that removes allowed values with actual data.
      $allowed_values = $field_storage->getSetting('allowed_values');
      $prior_allowed_values = $prior_field_storage->getSetting('allowed_values');
      $lost_keys = array_keys(array_diff_key($prior_allowed_values, $allowed_values));
      if (_options_values_in_use($field_storage->getTargetEntityTypeId(), $field_storage->getName(), $lost_keys)) {
        throw new FieldStorageDefinitionUpdateForbiddenException("A list field '{$field_storage->getName()}' with existing data cannot have its keys changed.");
      }
    }
  }

}
