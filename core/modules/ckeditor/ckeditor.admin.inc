<?php

/**
 * @file
 * Callbacks and theming for the CKEditor toolbar configuration UI.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Language\LanguageInterface;

/**
 * Prepares variables for CKEditor settings toolbar templates.
 *
 * Default template: ckeditor-settings-toolbar.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - editor: An editor object.
 *   - plugins: A list of plugins.
 *   - active_buttons: A list of disabled buttons.
 *   - disabled_buttons: A list of disabled buttons.
 *   - multiple_buttons: A list of multiple buttons that may be added multiple
 *     times.
 */
function template_preprocess_ckeditor_settings_toolbar(&$variables) {
  $language_interface = \Drupal::languageManager()->getCurrentLanguage();

  // Create lists of active and disabled buttons.
  $editor = $variables['editor'];
  $plugins = $variables['plugins'];
  $buttons = array();
  $multiple_buttons = array();
  foreach ($plugins as $plugin_buttons) {
    foreach ($plugin_buttons as $button_name => $button) {
      $button['name'] = $button_name;
      if (!empty($button['multiple'])) {
        $multiple_buttons[$button_name] = $button;
      }
      $buttons[$button_name] = $button;
    }
  }
  $button_groups = array();
  $active_buttons = array();
  $settings = $editor->getSettings();
  foreach ($settings['toolbar']['rows'] as $row_number => $row) {
    $button_groups[$row_number] = array();
    foreach ($row as $group) {
      foreach ($group['items'] as $button_name) {
        if (isset($buttons[$button_name])) {
          // Save a reference to the button's configured toolbar group.
          $buttons[$button_name]['group'] = $group['name'];
          $active_buttons[$row_number][] = $buttons[$button_name];
          if (empty($buttons[$button_name]['multiple'])) {
            unset($buttons[$button_name]);
          }
          // Create a list of all the toolbar button groups.
          if (!in_array($group['name'], $button_groups[$row_number])) {
            array_push($button_groups[$row_number], $group['name']);
          }
        }
      }
    }
  }
  $disabled_buttons = array_diff_key($buttons, $multiple_buttons);

  $rtl = $language_interface->getDirection() === LanguageInterface::DIRECTION_RTL ? '_rtl' : '';

  $build_button_item = function($button, $rtl) {
    // Value of the button item.
    if (isset($button['image_alternative' . $rtl])) {
      $value = $button['image_alternative' . $rtl];
    }
    elseif (isset($button['image_alternative'])) {
      $value = $button['image_alternative'];
    }
    elseif (isset($button['image'])) {
      $value = array(
        '#theme' => 'image',
        '#uri' => $button['image' . $rtl],
        '#title' => $button['label'],
        '#prefix' => '<a href="#" role="button" title="' . $button['label'] . '" aria-label="' . $button['label'] . '"><span class="cke_button_icon">',
        '#suffix' => '</span></a>',
      );
    }
    else {
      $value = '?';
    }

    // Build the button attributes.
    $attributes = array(
      'data-drupal-ckeditor-button-name' => $button['name'],
    );
    if (!empty($button['attributes'])) {
      $attributes = array_merge($attributes, $button['attributes']);
    }

    // Build the button item.
    $button_item = array(
      'value' => $value,
      'attributes' => new Attribute($attributes),
    );
    // If this button has group information, add it to the attributes.
    if (!empty($button['group'])) {
      $button_item['group'] = $button['group'];
    }

    // Set additional flag on the button if it can occur multiple times.
    if (!empty($button['multiple'])) {
      $button_item['multiple'] = true;
    }

    return $button_item;
  };

  // Assemble list of disabled buttons (which are always a single row).
  $variables['active_buttons'] = array();
  foreach ($active_buttons as $row_number => $button_row) {
    foreach ($button_groups[$row_number] as $group_name) {
      // We cast the group name to string as array keys must not be objects
      // and t() may return a TranslationWrapper once issue #2557113 lands.
      $group_name = (string) $group_name;
      $variables['active_buttons'][$row_number][$group_name] = array(
        'group_name_class' => Html::getClass($group_name),
        'buttons' => array(),
      );
      $buttons = array_filter($button_row, function ($button) use ($group_name) {
        return $button['group'] === $group_name;
      });
      foreach ($buttons as $button) {
        $variables['active_buttons'][$row_number][$group_name]['buttons'][] = $build_button_item($button, $rtl);
      }
    }
  }
  // Assemble list of disabled buttons (which are always a single row).
  $variables['disabled_buttons'] = array();
  foreach ($disabled_buttons as $button) {
    $variables['disabled_buttons'][] = $build_button_item($button, $rtl);
  }
  // Assemble list of multiple buttons that may be added multiple times.
  $variables['multiple_buttons'] = array();
  foreach ($multiple_buttons as $button) {
    $variables['multiple_buttons'][] = $build_button_item($button, $rtl);
  }
}
