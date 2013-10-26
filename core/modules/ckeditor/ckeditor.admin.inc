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
  $button_groups = array();
  $variables['active_buttons'] = array();
  foreach ($editor->settings['toolbar']['rows'] as $row_number => $row) {
    $button_groups[$row_number] = array();
    foreach ($row as $group) {
      foreach ($group['items'] as $button_name) {
        if (isset($buttons[$button_name])) {
          // Save a reference to the button's configured toolbar group.
          $buttons[$button_name]['group'] = $group['name'];
          $variables['active_buttons'][$row_number][] = $buttons[$button_name];
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
  $variables['disabled_buttons'] = array_diff_key($buttons, $variables['multiple_buttons']);
  $variables['button_groups'] = $button_groups;
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
      $value = '<a href="#" role="button" title="' . $button['label'] . '" aria-label="' . $button['label'] . '"><span class="cke_button_icon">' . drupal_render($image) . '</span></a>';
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
      'data-drupal-ckeditor-button-name' => $button['name'],
      'class' => array('ckeditor-button'),
    );
    // If this button has group information, add it to the attributes.
    if (!empty($button['group'])) {
      $button_item['group'] = $button['group'];
    }
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
      unset($button['group']);
      $value = $button['value'];
      unset($button['value']);
      $attributes = (string) new Attribute($button);
      $output .= '<li' . $attributes . '>' . $value . '</li>';
    }
    return $output;
  };

  $print_button_group = function($buttons, $group_name, $print_buttons) {
    $group = drupal_html_class($group_name);

    $output = '';
    $output .= "<li class=\"ckeditor-toolbar-group\" role=\"presentation\" data-drupal-ckeditor-type=\"group\" data-drupal-ckeditor-toolbar-group-name=\"{$group_name}\" tabindex=\"0\">";
    $output .= "<h3 class=\"ckeditor-toolbar-group-name\" id=\"ckeditor-toolbar-group-aria-label-for-{$group}\">{$group_name}</h3>";
    $output .= "<ul class=\"ckeditor-buttons ckeditor-toolbar-group-buttons\" role=\"toolbar\" data-drupal-ckeditor-button-sorting=\"target\" aria-labelledby=\"ckeditor-toolbar-group-aria-label-for-{$group}\">";
    $output .= $print_buttons($buttons);
    $output .= "</ul></li>";

    return $output;
  };

  // We don't use theme_item_list() below in case there are no buttons in the
  // active or disabled list, as theme_item_list() will not print an empty UL.
  $output = '';
  $output .= '<fieldset role="form" aria-labelledby="ckeditor-button-configuration ckeditor-button-description">';
  $output .= '<legend id="ckeditor-button-configuration">' . t('Toolbar configuration') . '</legend>';
  $output .= '<div class="fieldset-wrapper">';

  $output .= '<div id="ckeditor-button-description" class="fieldset-description">' . t('Move a button into the <em>Active toolbar</em> to enable it, or into the list of <em>Available buttons</em> to disable it. Buttons may be moved with the mouse or keyboard arrow keys. Toolbar group names are provided to support screen reader users. Empty toolbar groups will be removed upon save.') . '</div>';

  $output .= '<div class="ckeditor-toolbar-disabled clearfix">';
  // Available buttons.
  $output .= '<div class="ckeditor-toolbar-available">';
  $output .= '<label for="ckeditor-available-buttons">' . t('Available buttons') . '</label>';
  $output .= '<ul id="ckeditor-available-buttons" class="ckeditor-buttons" role="form" data-drupal-ckeditor-button-sorting="source">';
  $output .= $print_buttons($disabled_buttons);
  $output .= '</ul>';
  $output .= '</div>';
  // Dividers.
  $output .= '<div class="ckeditor-toolbar-dividers">';
  $output .= '<label for="ckeditor-multiple-buttons">' . t('Button divider') . '</label>';
  $output .= '<ul id="ckeditor-multiple-buttons" class="ckeditor-multiple-buttons" role="form" data-drupal-ckeditor-button-sorting="dividers">';
  $output .= $print_buttons($multiple_buttons);
  $output .= '</ul>';
  $output .= '</div>';
  $output .= '</div>';
  // Active toolbar.
  $output .= '<div class="clearfix"><label id="ckeditor-active-toolbar">' . t('Active toolbar') . '</label></div>';
  $output .= '<div data-toolbar="active" role="form" class="ckeditor-toolbar ckeditor-toolbar-active clearfix">';
  $output .= '<ul class="ckeditor-active-toolbar-configuration" role="presentation" aria-label="' . t('CKEditor toolbar and button configuration.') . '">';
  foreach ($active_buttons as $row_number => $button_row) {
    $output .= '<li class="ckeditor-row" role="group" aria-labelledby="ckeditor-active-toolbar">';
    $output .= '<ul class="ckeditor-toolbar-groups clearfix">';
    foreach ($variables['button_groups'][$row_number] as $group_name) {
      $buttons = array_filter($button_row, function ($button) use ($group_name) {
        return $button['group'] === $group_name;
      });
      $output .= $print_button_group($buttons, $group_name, $print_buttons);
    }
    $output .= '</ul>';
    $output .= '</li>';
  }
  if (empty($active_buttons)) {
    $output .= '<ul class="ckeditor-buttons">';
    $output .= '</ul>';
  }

  $output .= '</div>';

  $output .= '</div>';
  $output .= '</fieldset>';

  return $output;
}
