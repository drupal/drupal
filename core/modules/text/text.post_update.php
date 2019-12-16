<?php

/**
 * @file
 * Contains post update hooks for the text module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\text\Plugin\Field\FieldWidget\TextareaWithSummaryWidget;

/**
 * Update text_with_summary fields to add summary required flags.
 */
function text_post_update_add_required_summary_flag(&$sandbox = NULL) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $field_callback = function (FieldConfigInterface $field) {
    if ($field->getType() !== 'text_with_summary') {
      return FALSE;
    }
    $field->setSetting('required_summary', FALSE);
    return TRUE;
  };

  $config_entity_updater->update($sandbox, 'field_config', $field_callback);
}

/**
 * Update text_with_summary widgets to add summary required flags.
 */
function text_post_update_add_required_summary_flag_form_display(&$sandbox = NULL) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  /** @var \Drupal\Core\Field\WidgetPluginManager $field_widget_manager */
  $field_widget_manager = \Drupal::service('plugin.manager.field.widget');

  $widget_callback = function (EntityDisplayInterface $display) use ($field_widget_manager) {
    $needs_save = FALSE;
    foreach ($display->getComponents() as $field_name => $component) {
      if (empty($component['type'])) {
        continue;
      }

      $plugin_definition = $field_widget_manager->getDefinition($component['type'], FALSE);
      if (is_a($plugin_definition['class'], TextareaWithSummaryWidget::class, TRUE)) {
        $component['settings']['show_summary'] = FALSE;
        $display->setComponent($field_name, $component);
        $needs_save = TRUE;
      }
    }

    return $needs_save;
  };

  $config_entity_updater->update($sandbox, 'entity_form_display', $widget_callback);
}
