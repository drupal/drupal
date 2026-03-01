<?php

declare(strict_types=1);

namespace Drupal\admin\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\views\ViewExecutable;

/**
 * Provides views related hook implementations.
 */
readonly class ViewsHooks {

  /**
   * Implements hook_views_ui_display_tab_alter().
   */
  #[Hook('views_ui_display_tab_alter')]
  public function viewsUiDisplayTabAlter(array &$element): void {
    // We process the dropbutton-like element on views edit form's
    // display settings top section.
    //
    // That element should be a regular Dropbutton.
    //
    // After that the reported issue is fixed and the element is rendered with
    // the Dropbutton type, we just have to set it's '#dropbutton_type' to
    // 'extrasmall'.
    //
    // @todo Revisit after https://www.drupal.org/node/3057577 is fixed.
    $dummy_dropbutton = &$element['details']['top']['actions'];

    if ($dummy_dropbutton) {
      $child_keys = Element::children($dummy_dropbutton);
      $prefix_regex = '/(<.*class\s*= *["\']?)([^"\']*)(.*)/i';
      $child_count = 0;

      foreach ($child_keys as $key) {
        if (in_array($key, ['prefix', 'suffix'])) {
          continue;
        }
        $nested_child_keys = Element::children($dummy_dropbutton[$key], TRUE);

        if (!empty($nested_child_keys)) {
          foreach ($nested_child_keys as $nested_key) {
            $child_count++;
            $prefix = $dummy_dropbutton[$key][$nested_key]['#prefix'];
            $dummy_dropbutton[$key][$nested_key]['#prefix'] = preg_replace($prefix_regex, '$1$2 dropbutton__item$3', $prefix);
          }
        }
        else {
          $child_count++;
          $prefix = $dummy_dropbutton[$key]['#prefix'];
          $dummy_dropbutton[$key]['#prefix'] = preg_replace($prefix_regex, '$1$2 dropbutton__item$3', $prefix);
        }
      }

      if (!empty($dummy_dropbutton['prefix']) && !empty($dummy_dropbutton['prefix']['#markup'])) {
        $classes = 'dropbutton--extrasmall ';
        $classes .= ($child_count > 1) ? 'dropbutton--multiple' : 'dropbutton--single';
        $prefix = $dummy_dropbutton['prefix']['#markup'];
        $dummy_dropbutton['prefix']['#markup'] = preg_replace($prefix_regex, '$1$2 ' . $classes . '$3', $prefix);
      }
    }

    // If the top details have both actions and title, the `actions` weights
    // need to be adjusted so the DOM order matches the order of appearance.
    if (isset($element['details']['top']['actions'], $element['details']['top']['display_title'])) {
      $top = &$element['details']['top'];
      $top['actions']['#weight'] = 10;
      $top['#attributes']['class'] = array_diff($top['#attributes']['class'], ['clearfix']);
    }
  }

  /**
   * Implements hook_views_ui_display_top_alter().
   */
  #[Hook('views_ui_display_top_alter')]
  public function viewsUiDisplayTopAlter(array &$element): void {
    // @todo Remove this after https://www.drupal.org/node/3051605 has been
    //   solved.
    $element['tabs']['#prefix'] = preg_replace('/(class="(.+\s)?)tabs(\s.+"|")/', '$1views-tabs$3', $element['tabs']['#prefix']);
    $element['tabs']['#prefix'] = preg_replace('/(class="(.+\s)?)secondary(\s.+"|")/', '$1views-tabs--secondary$3', $element['tabs']['#prefix']);

    foreach (Element::children($element['tabs']) as $tab) {
      $element['tabs'][$tab]['#theme'] = 'menu_local_task__views_ui';
    }

    // Change top extra actions to use the small dropbutton variant.
    // @todo Revisit after https://www.drupal.org/node/3057581 is added.
    if (!empty($element['extra_actions'])) {
      $element['extra_actions']['#dropbutton_type'] = 'small';
    }

    // Add a class to each item in the add display dropdown so they can be
    // styled with a single selector.
    if (isset($element['add_display'])) {
      foreach ($element['add_display'] as &$display_item) {
        if (isset($display_item['#type']) && in_array($display_item['#type'], ['submit', 'button'], TRUE)) {
          $display_item['#attributes']['class'][] = 'views-tabs__action-list-button';
        }
      }
      unset($display_item);
    }

    // Rearrange top bar contents so floats aren't necessary for positioning.
    if (isset($element['extra_actions'], $element['add_display'])) {
      $element['extra_actions']['#weight'] = 10;
      $element['extra_actions']['#theme_wrappers'] = [
        'container' => [
          '#attributes' => [
            'class' => ['views-display-top__extra-actions-wrapper'],
          ],
        ],
      ];

      $element['#attributes']['class'] = array_diff($element['#attributes']['class'], ['clearfix']);
    }

  }

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function preRender(ViewExecutable $view): void {
    $add_classes = static function (&$option, array $classes_to_add) {
      $classes = preg_split('/\s+/', $option);
      $classes = array_filter($classes);
      $classes = array_merge($classes, $classes_to_add);
      $option = implode(' ', array_unique($classes));
    };

    if ($view->id() === 'media_library') {
      if ($view->display_handler->options['defaults']['css_class']) {
        $add_classes($view->displayHandlers->get('default')->options['css_class'], ['media-library-view']);
      }
      else {
        $add_classes($view->display_handler->options['css_class'], ['media-library-view']);
      }

      if ($view->current_display === 'page') {
        if (array_key_exists('media_bulk_form', $view->field)) {
          $add_classes($view->field['media_bulk_form']->options['element_class'], ['media-library-item__click-to-select-checkbox']);
        }
        if (array_key_exists('rendered_entity', $view->field)) {
          $add_classes($view->field['rendered_entity']->options['element_class'], ['media-library-item__content']);
        }
        if (array_key_exists('edit_media', $view->field)) {
          $add_classes($view->field['edit_media']->options['alter']['link_class'], ['media-library-item__edit']);
          $add_classes($view->field['edit_media']->options['alter']['link_class'], ['icon-link']);
        }
        if (array_key_exists('delete_media', $view->field)) {
          $add_classes($view->field['delete_media']->options['alter']['link_class'], ['media-library-item__remove']);
          $add_classes($view->field['delete_media']->options['alter']['link_class'], ['icon-link']);
        }
      }
      elseif (str_starts_with($view->current_display, 'widget')) {
        if (array_key_exists('rendered_entity', $view->field)) {
          $add_classes($view->field['rendered_entity']->options['element_class'], ['media-library-item__content']);
        }
        if (array_key_exists('media_library_select_form', $view->field)) {
          $add_classes($view->field['media_library_select_form']->options['element_wrapper_class'], ['media-library-item__click-to-select-checkbox']);
        }

        if ($view->display_handler->options['defaults']['css_class']) {
          $add_classes($view->displayHandlers->get('default')->options['css_class'], ['media-library-view--widget']);
        }
        else {
          $add_classes($view->display_handler->options['css_class'], ['media-library-view--widget']);
        }
      }
    }
  }

}
