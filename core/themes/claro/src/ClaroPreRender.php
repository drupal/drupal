<?php

namespace Drupal\claro;

use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Implements trusted prerender callbacks for the Claro theme.
 *
 * @internal
 */
class ClaroPreRender implements TrustedCallbackInterface {

  /**
   * Prerender callback for managed_file.
   */
  public static function managedFile($element) {
    if (!empty($element['remove_button']) && is_array($element['remove_button'])) {
      $element['remove_button']['#attributes']['class'][] = 'button--extrasmall';
      $element['remove_button']['#attributes']['class'][] = 'remove-button';
    }

    if (!empty($element['upload_button']) && is_array($element['upload_button'])) {
      $element['upload_button']['#attributes']['class'][] = 'upload-button';
    }

    // Wrap single-cardinality widgets with a details element.
    $single_file_widget = empty($element['#do_not_wrap_in_details']) && !empty($element['#cardinality']) && $element['#cardinality'] === 1;
    if ($single_file_widget && empty($element['#single_wrapped'])) {
      $element['#theme_wrappers']['details'] = [
        '#title' => $element['#title'],
        '#summary_attributes' => [],
        '#attributes' => ['open' => TRUE],
        '#value' => NULL,
        // The description of the single cardinality file widgets will be
        // displayed by the managed file widget.
        '#description' => NULL,
        '#required' => $element['#required'],
        '#errors' => NULL,
        '#disabled' => !empty($element['#disabled']),
      ];
      $element['#single_wrapped'] = TRUE;

      $upload_is_accessible = empty($element['#default_value']['fids']) && (!isset($element['upload']['#access']) || $element['upload']['#access'] !== FALSE);
      if ($upload_is_accessible) {
        // Change widget title. This is the same title that is used by the
        // multiple file widget.
        // @see https://git.drupalcode.org/project/drupal/blob/ade7b950a1/core/modules/file/src/Plugin/Field/FieldWidget/FileWidget.php#L192
        $element['#title'] = t('Add a new file');
      }
      else {
        // If the field has a value, the file upload title doesn't have to be
        // visible because the wrapper element will have the same title as the
        // managed file widget. The title is kept in the markup as visually
        // hidden for accessibility.
        $element['#title_display'] = 'invisible';
      }
    }

    return $element;
  }

  /**
   * Prerender callback for Vertical Tabs element.
   */
  public static function verticalTabs($element) {
    $group_type_is_details = isset($element['group']['#type']) && $element['group']['#type'] === 'details';
    $groups_are_present = isset($element['group']['#groups']) && is_array($element['group']['#groups']);

    // If the vertical tabs have a details group, add attributes to those
    // details elements so they are styled as accordion items and have BEM
    // classes.
    if ($group_type_is_details && $groups_are_present) {
      $group_keys = Element::children($element['group']['#groups'], TRUE);
      $first_key = TRUE;
      $last_group_with_child_key = NULL;
      $last_group_with_child_key_last_child_key = NULL;

      $group_key = implode('][', $element['#parents']);
      // Only check siblings against groups because we are only looking for
      // group elements.
      if (in_array($group_key, $group_keys)) {
        $children_keys = Element::children($element['group']['#groups'][$group_key], TRUE);

        foreach ($children_keys as $child_key) {
          $last_group_with_child_key = $group_key;
          $type = $element['group']['#groups'][$group_key][$child_key]['#type'] ?? NULL;
          if ($type === 'details') {
            // Add BEM class to specify the details element is in a vertical
            // tabs group.
            $element['group']['#groups'][$group_key][$child_key]['#attributes']['class'][] = 'vertical-tabs__item';
            $element['group']['#groups'][$group_key][$child_key]['#vertical_tab_item'] = TRUE;

            if ($first_key) {
              $element['group']['#groups'][$group_key][$child_key]['#attributes']['class'][] = 'vertical-tabs__item--first';
              $first_key = FALSE;
            }

            $last_group_with_child_key_last_child_key = $child_key;
          }
        }
      }

      if ($last_group_with_child_key && $last_group_with_child_key_last_child_key) {
        $element['group']['#groups'][$last_group_with_child_key][$last_group_with_child_key_last_child_key]['#attributes']['class'][] = 'vertical-tabs__item--last';
      }

      $element['#attributes']['class'][] = 'vertical-tabs__items';
    }

    return $element;
  }

  /**
   * Prerender callback for the Operations element.
   */
  public static function operations($element) {
    if (empty($element['#dropbutton_type'])) {
      $element['#dropbutton_type'] = 'extrasmall';
    }
    return $element;
  }

  /**
   * Prerender callback for container elements.
   *
   * @param array $element
   *   The container element.
   *
   * @return array
   *   The processed container element.
   */
  public static function container(array $element) {
    if (!empty($element['#accordion'])) {
      // The container must work as an accordion list wrapper.
      $element['#attributes']['class'][] = 'accordion';
      $children_keys = Element::children($element['#groups']['advanced'], TRUE);

      foreach ($children_keys as $key) {
        $element['#groups']['advanced'][$key]['#attributes']['class'][] = 'accordion__item';

        // Mark children with type Details as accordion item.
        if (!empty($element['#groups']['advanced'][$key]['#type']) && $element['#groups']['advanced'][$key]['#type'] === 'details') {
          $element['#groups']['advanced'][$key]['#accordion_item'] = TRUE;
        }
      }
    }

    return $element;
  }

  /**
   * Prerender callback for text_format elements.
   */
  public static function textFormat($element) {
    // Add clearfix for filter wrapper.
    $element['format']['#attributes']['class'][] = 'clearfix';
    // Hide format select label visually.
    $element['format']['format']['#wrapper_attributes']['class'][] = 'form-item--editor-format';
    $element['format']['format']['#attributes']['class'][] = 'form-element--extrasmall';
    $element['format']['format']['#attributes']['class'][] = 'form-element--editor-format';

    // Fix JS inconsistencies of the 'text_textarea_with_summary' widgets.
    // @todo Remove when https://www.drupal.org/node/3016343 is fixed.
    if (
      !empty($element['summary']) &&
      $element['summary']['#type'] === 'textarea'
    ) {
      $element['#attributes']['class'][] = 'js-text-format-wrapper';
      $element['value']['#wrapper_attributes']['class'][] = 'js-form-type-textarea';
    }
    return $element;
  }

  /**
   * Prerender callback for status_messages placeholder.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   The updated renderable array containing the placeholder.
   */
  public static function messagePlaceholder(array $element) {
    if (isset($element['fallback']['#markup'])) {
      $element['fallback']['#markup'] = '<div data-drupal-messages-fallback class="hidden messages-list"></div>';
    }
    return $element;
  }

  /**
   * Prerender callback for table elements.
   */
  public static function tablePositionSticky(array $element) {
    if (isset($element['#attributes']['class']) && in_array('sticky-enabled', $element['#attributes']['class'])) {
      unset($element['#attributes']['class'][array_search('sticky-enabled', $element['#attributes']['class'])]);
      $element['#attributes']['class'][] = 'position-sticky';
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'managedFile',
      'verticalTabs',
      'operations',
      'container',
      'textFormat',
      'messagePlaceholder',
      'tablePositionSticky',
    ];
  }

}
