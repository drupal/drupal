<?php

namespace Drupal\Core\Field;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Template\AttributeHelper;

/**
 * Field theme preprocess.
 *
 * @internal
 */
class FieldPreprocess {

  use StringTranslationTrait;

  /**
   * Prepares variables for field templates.
   *
   * Default template: field.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: A render element representing the field.
   *   - attributes: A string containing the attributes for the wrapping div.
   *   - title_attributes: A string containing the attributes for the title.
   */
  public function preprocessField(array &$variables): void {
    $element = $variables['element'];

    // Creating variables for the template.
    $variables['entity_type'] = $element['#entity_type'];
    $variables['field_name'] = $element['#field_name'];
    $variables['field_type'] = $element['#field_type'];
    $variables['label_display'] = $element['#label_display'];

    $variables['label_hidden'] = ($element['#label_display'] == 'hidden');
    // Always set the field label - allow themes to decide whether to display
    // it. In addition the label should be rendered but hidden to support screen
    // readers.
    $variables['label'] = $element['#title'];

    $variables['multiple'] = $element['#is_multiple'];

    static $default_attributes;
    if (!isset($default_attributes)) {
      $default_attributes = new Attribute();
    }

    // Merge attributes when a single-value field has a hidden label.
    if ($element['#label_display'] == 'hidden' && !$variables['multiple'] && !empty($element['#items'][0]->_attributes)) {
      $variables['attributes'] = AttributeHelper::mergeCollections($variables['attributes'], (array) $element['#items'][0]->_attributes);
    }

    // We want other preprocess functions and the theme implementation to have
    // fast access to the field item render arrays. The item render array keys
    // (deltas) should always be numerically indexed starting from 0, and
    // looping on those keys is faster than calling Element::children() or
    // looping on all keys within $element, since that requires traversal of all
    // element properties.
    $variables['items'] = [];
    $delta = 0;
    while (!empty($element[$delta])) {
      $variables['items'][$delta]['content'] = $element[$delta];

      // Modules can add field item attributes (to
      // $item->_attributes) within hook_entity_prepare_view(). Some field
      // formatters move those attributes into some nested formatter-specific
      // element in order have them rendered on the desired HTML element (e.g.,
      // on the <a> element of a field item being rendered as a link). Other
      // field formatters leave them within
      // $element['#items'][$delta]['_attributes'] to be rendered on the item
      // wrappers provided by field.html.twig.
      $variables['items'][$delta]['attributes'] = !empty($element['#items'][$delta]->_attributes) ? new Attribute($element['#items'][$delta]->_attributes) : clone($default_attributes);
      $delta++;
    }
  }

  /**
   * Prepares variables for individual form element templates.
   *
   * Default template: field-multiple-value-form.html.twig.
   *
   * Combines multiple values into a table with drag-n-drop reordering.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: A render element representing the form element.
   */
  public function preprocessFieldMultipleValueForm(array &$variables): void {
    $element = $variables['element'];
    $variables['multiple'] = $element['#cardinality_multiple'];
    $variables['attributes'] = $element['#attributes'];

    if ($variables['multiple']) {
      $table_id = Html::getUniqueId($element['#field_name'] . '_values');
      // Using table id allows handing nested content with the same field names.
      $order_class = $table_id . '-delta-order';
      $header_attributes = new Attribute(['class' => ['label']]);
      if (!empty($element['#required'])) {
        $header_attributes['class'][] = 'js-form-required';
        $header_attributes['class'][] = 'form-required';
      }
      $header = [
        [
          'data' => [
            '#type' => 'html_tag',
            '#tag' => 'h4',
            '#value' => $element['#title'],
            '#attributes' => $header_attributes,
          ],
          'colspan' => 2,
          'class' => ['field-label'],
        ],
        [],
        $this->t('Order', [], ['context' => 'Sort order']),
      ];
      $rows = [];

      // Sort items according to '_weight' (needed when the form comes back
      // after preview or failed validation).
      $items = [];
      $variables['button'] = [];
      foreach (Element::children($element) as $key) {
        if ($key === 'add_more') {
          $variables['button'] = &$element[$key];
        }
        else {
          $items[] = &$element[$key];
        }
      }
      usort($items, function ($a, $b) {
        // Sorts using ['_weight']['#value'].
        $a_weight = (is_array($a) && isset($a['_weight']['#value']) ? $a['_weight']['#value'] : 0);
        $b_weight = (is_array($b) && isset($b['_weight']['#value']) ? $b['_weight']['#value'] : 0);
        return $a_weight - $b_weight;
      });

      // Add the items as table rows.
      foreach ($items as $item) {
        $item['_weight']['#attributes']['class'] = [$order_class];

        // Remove weight form element from item render array so it can be
        // rendered in a separate table column.
        $delta_element = $item['_weight'];
        unset($item['_weight']);

        // Render actions in a separate column.
        $actions = [];
        if (isset($item['_actions'])) {
          $actions = $item['_actions'];
          unset($item['_actions']);
        }

        $cells = [
          ['data' => '', 'class' => ['field-multiple-drag']],
          ['data' => $item],
          ['data' => $actions],
          ['data' => $delta_element, 'class' => ['delta-order']],
        ];
        $rows[] = [
          'data' => $cells,
          'class' => ['draggable'],
        ];
      }

      $variables['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => [
          'id' => $table_id,
          'class' => ['field-multiple-table'],
        ],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => $order_class,
          ],
        ],
      ];

      if (!empty($element['#description'])) {
        $description_id = $element['#attributes']['aria-describedby'];
        $description_attributes['id'] = $description_id;
        $variables['description']['attributes'] = new Attribute($description_attributes);
        $variables['description']['content'] = $element['#description'];

        // Add the description's id to the table aria attributes.
        $variables['table']['#attributes']['aria-describedby'] = $element['#attributes']['aria-describedby'];
      }
    }
    else {
      $variables['elements'] = [];
      foreach (Element::children($element) as $key) {
        $variables['elements'][] = $element[$key];
      }
    }
  }

}
