<?php

/**
 * @file
 * Callbacks and theming for the CKEditor toolbar configuration UI.
 */

use Drupal\Core\Language\Language;
use Drupal\Core\Template\Attribute;

/**
 * Preprocess variables for theme_ckeditor_settings_toolbar().
 */
function template_preprocess_ckeditor_settings_toolbar(&$variables) {
  // Simplify the language direction information for toolbar buttons.
  $language_interface = language(Language::TYPE_INTERFACE);
  $variables['language_direction'] = $language_interface->direction ? 'rtl' : 'ltr';

  // Create lists of active and disabled buttons.
  $editor = $variables['editor'];
  $plugins = $variables['plugins'];
  $buttons = array();
  $variables['multiple_buttons'] = array();
  foreach ($plugins as $plugin => $plugin_buttons) {
    foreach ($plugin_buttons as $button_name => $button) {
      $button['name'] = $button_name;
      if (!empty($button['multiple'])) {
        $variables['multiple_buttons'][$button_name] = $button;
      }
      $buttons[$button_name] = $button;
    }
  }
  $variables['active_buttons'] = array();
  foreach ($editor->settings['toolbar']['buttons'] as $row_number => $row) {
    foreach ($row as $button_name) {
      if (isset($buttons[$button_name])) {
        $variables['active_buttons'][$row_number][] = $buttons[$button_name];
        if (empty($buttons[$button_name]['multiple'])) {
          unset($buttons[$button_name]);
        }
      }
    }
  }
  $variables['disabled_buttons'] = array_diff_key($buttons, $variables['multiple_buttons']);
}

/**
 * Displays the toolbar configuration for CKEditor.
 */
function theme_ckeditor_settings_toolbar($variables) {
  $editor = $variables['editor'];
  $plugins = $variables['plugins'];
  $rtl = $variables['language_direction'] === 'rtl' ? '_rtl' : '';

  $build_button_item = function($button, $rtl) {
    // Value of the button item.
    if (isset($button['image_alternative'. $rtl])) {
      $value = $button['image_alternative' . $rtl];
    }
    elseif (isset($button['image_alternative'])) {
      $value = $button['image_alternative'];
    }
    elseif (isset($button['image'])) {
      $image = array(
        '#theme' => 'image',
        '#uri' => $button['image' . $rtl],
        '#title' => $button['label'],
      );
      $value = '<a href="#" class="cke_button" role="button" title="' . $button['label'] . '" aria-label="' . $button['label'] . '"><span class="cke_button_icon">' . drupal_render($image) . '</span></a>';
    }
    else {
      $value = '?';
    }

    // Set additional attribute on the button if it can occur multiple times.
    if (!empty($button['multiple'])) {
     $button['attributes']['class'][] = 'ckeditor-multiple-button';
    }

    // Build the button item.
    $button_item = array(
      'value' => $value,
      'data-button-name' => $button['name'],
    );
    if (!empty($button['attributes'])) {
      $button_item = array_merge($button_item, $button['attributes']);
    }

    return $button_item;
  };

  // Assemble items to be added to active button rows.
  $active_buttons = array();
  foreach ($variables['active_buttons'] as $row_number => $row_buttons) {
    foreach ($row_buttons as $button) {
      $active_buttons[$row_number][] = $build_button_item($button, $rtl);
    }
  }
  // Assemble list of disabled buttons (which are always a single row).
  $disabled_buttons = array();
  foreach ($variables['disabled_buttons'] as $button) {
    $disabled_buttons[] = $build_button_item($button, $rtl);
  }
  // Assemble list of multiple buttons that may be added multiple times.
  $multiple_buttons = array();
  foreach ($variables['multiple_buttons'] as $button_name => $button) {
    $multiple_buttons[] = $build_button_item($button, $rtl);
  }

  $print_buttons = function($buttons) {
    $output = '';
    foreach ($buttons as $button) {
      $value = $button['value'];
      unset($button['value']);
      $attributes = (string) new Attribute($button);
      $output .= '<li' . $attributes . '>' . $value . '</li>';
    }
    return $output;
  };

  // We don't use theme_item_list() below in case there are no buttons in the
  // active or disabled list, as theme_item_list() will not print an empty UL.
  $output = '';
  $output .= '<fieldset role="form" aria-labelledby="ckeditor-button-configuration ckeditor-button-description">';
  $output .= '<legend id="ckeditor-button-configuration">' . t('Toolbar configuration') . '</legend>';
  $output .= '<div class="fieldset-wrapper">';

  // aria-live region for outputing aural information about the state of the
  // configuration.
  $output .= '<div id="ckeditor-button-configuration-aria-live" class="visually-hidden" aria-live="polite"></div>';

  $output .= '<div id="ckeditor-button-description" class="fieldset-description">' . t('Move a button into the <em>Active toolbar</em> to enable it, or into the list of <em>Available buttons</em> to disable it. Use dividers to create button groups. Buttons may be moved with the mouse or keyboard arrow keys.') . '</div>';

  $output .= '<div class="ckeditor-toolbar-disabled clearfix">';
  $output .= '<div class="ckeditor-toolbar-dividers">';
  $output .= '<label id="ckeditor-multiple-label">' . t('Dividers') . '</label>';
  $output .= '<ul class="ckeditor-multiple-buttons" role="form" aria-labelledby="ckeditor-multiple-label">';
  $output .= $print_buttons($multiple_buttons);
  $output .= '</ul>';
  $output .= '</div>';
  $output .= '<label id="ckeditor-available-buttons">' . t('Available buttons') . '</label>';
  $output .= '<ul class="ckeditor-buttons" role="form" aria-labelledby="ckeditor-available-buttons">';
  $output .= $print_buttons($disabled_buttons);
  $output .= '</ul>';
  $output .= '</div>';

  $output .= '<label id="ckeditor-active-toolbar">' . t('Active toolbar') . '</label>';

  $output .= '<div data-toolbar="active" class="ckeditor-toolbar-active clearfix">';
  foreach ($active_buttons as $button_row) {
    $output .= '<ul class="ckeditor-buttons" role="form" aria-labelledby="ckeditor-active-toolbar">';
    $output .= $print_buttons($button_row);
    $output .= '</ul>';
  }
  if (empty($active_buttons)) {
    $output .= '<ul class="ckeditor-buttons">';
    $output .= '</ul>';
  }

  $output .= '<div class="ckeditor-row-controls">';
  $output .= '<a href="#" role="button" aria-label="' . t('Remove last button row') . '" class="ckeditor-row-remove" title="' . t('Remove row') . '">-</a>';
  $output .= '<a href="#" role="button" aria-label="' . t('Add additional button row') . '" class="ckeditor-row-add" title="' . t('Add row') . '">+</a>';
  $output .= '</div>';

  $output .= '</div>';

  $output .= '</div>';
  $output .= '</fieldset>';

  return $output;
}
