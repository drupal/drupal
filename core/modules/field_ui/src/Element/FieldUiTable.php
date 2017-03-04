<?php

namespace Drupal\field_ui\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Table;

/**
 * Provides a field_ui table element.
 *
 * @RenderElement("field_ui_table")
 */
class FieldUiTable extends Table {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#regions'] = ['' => []];
    $info['#theme'] = 'field_ui_table';
    // Prepend FieldUiTable's prerender callbacks.
    array_unshift($info['#pre_render'], [$this, 'tablePreRender'], [$this, 'preRenderRegionRows']);
    return $info;
  }

  /**
   * Performs pre-render tasks on field_ui_table elements.
   *
   * @param array $elements
   *   A structured array containing two sub-levels of elements. Properties
   *   used:
   *   - #tabledrag: The value is a list of $options arrays that are passed to
   *     drupal_attach_tabledrag(). The HTML ID of the table is added to each
   *     $options array.
   *
   * @return array
   *   The $element with prepared variables ready for field-ui-table.html.twig.
   *
   * @see drupal_render()
   * @see \Drupal\Core\Render\Element\Table::preRenderTable()
   */
  public static function tablePreRender($elements) {
    $js_settings = [];

    // For each region, build the tree structure from the weight and parenting
    // data contained in the flat form structure, to determine row order and
    // indentation.
    $regions = $elements['#regions'];
    $tree = ['' => ['name' => '', 'children' => []]];
    $trees = array_fill_keys(array_keys($regions), $tree);

    $parents = [];
    $children = Element::children($elements);
    $list = array_combine($children, $children);

    // Iterate on rows until we can build a known tree path for all of them.
    while ($list) {
      foreach ($list as $name) {
        $row = &$elements[$name];
        $parent = $row['parent_wrapper']['parent']['#value'];
        // Proceed if parent is known.
        if (empty($parent) || isset($parents[$parent])) {
          // Grab parent, and remove the row from the next iteration.
          $parents[$name] = $parent ? array_merge($parents[$parent], [$parent]) : [];
          unset($list[$name]);

          // Determine the region for the row.
          $region_name = call_user_func($row['#region_callback'], $row);

          // Add the element in the tree.
          $target = &$trees[$region_name][''];
          foreach ($parents[$name] as $key) {
            $target = &$target['children'][$key];
          }
          $target['children'][$name] = ['name' => $name, 'weight' => $row['weight']['#value']];

          // Add tabledrag indentation to the first row cell.
          if ($depth = count($parents[$name])) {
            $children = Element::children($row);
            $cell = current($children);
            $indentation = [
              '#theme' => 'indentation',
              '#size' => $depth,
              '#suffix' => isset($row[$cell]['#prefix']) ? $row[$cell]['#prefix'] : '',
            ];
            $row[$cell]['#prefix'] = \Drupal::service('renderer')->render($indentation);
          }

          // Add row id and associate JS settings.
          $id = Html::getClass($name);
          $row['#attributes']['id'] = $id;
          if (isset($row['#js_settings'])) {
            $row['#js_settings'] += [
              'rowHandler' => $row['#row_type'],
              'name' => $name,
              'region' => $region_name,
            ];
            $js_settings[$id] = $row['#js_settings'];
          }
        }
      }
    }

    // Determine rendering order from the tree structure.
    foreach ($regions as $region_name => $region) {
      $elements['#regions'][$region_name]['rows_order'] = array_reduce($trees[$region_name], [static::class, 'reduceOrder']);
    }

    $elements['#attached']['drupalSettings']['fieldUIRowsData'] = $js_settings;

    // If the custom #tabledrag is set and there is a HTML ID, add the table's
    // HTML ID to the options and attach the behavior.
    // @see \Drupal\Core\Render\Element\Table::preRenderTable()
    if (!empty($elements['#tabledrag']) && isset($elements['#attributes']['id'])) {
      foreach ($elements['#tabledrag'] as $options) {
        $options['table_id'] = $elements['#attributes']['id'];
        drupal_attach_tabledrag($elements, $options);
      }
    }

    return $elements;
  }

  /**
   * Performs pre-render to move #regions to rows.
   *
   * @param array $elements
   *   A structured array containing two sub-levels of elements. Properties
   *   used:
   *   - #tabledrag: The value is a list of $options arrays that are passed to
   *     drupal_attach_tabledrag(). The HTML ID of the table is added to each
   *     $options array.
   *
   * @return array
   *   The $element with prepared variables ready for field-ui-table.html.twig.
   */
  public static function preRenderRegionRows($elements) {
    // Determine the colspan to use for region rows, by checking the number of
    // columns in the headers.
    $columns_count = 0;
    foreach ($elements['#header'] as $header) {
      $columns_count += (is_array($header) && isset($header['colspan']) ? $header['colspan'] : 1);
    }

    $rows = [];
    foreach (Element::children($elements) as $key) {
      $rows[$key] = $elements[$key];
      unset($elements[$key]);
    }

    // Render rows, region by region.
    foreach ($elements['#regions'] as $region_name => $region) {
      $region_name_class = Html::getClass($region_name);

      // Add region rows.
      if (isset($region['title']) && empty($region['invisible'])) {
        $elements['#rows'][] = [
          'class' => [
            'region-title',
            'region-' . $region_name_class . '-title'
          ],
          'no_striping' => TRUE,
          'data' => [
            ['data' => $region['title'], 'colspan' => $columns_count],
          ],
        ];
      }
      if (isset($region['message'])) {
        $class = (empty($region['rows_order']) ? 'region-empty' : 'region-populated');
        $elements['#rows'][] = [
          'class' => [
            'region-message',
            'region-' . $region_name_class . '-message', $class,
          ],
          'no_striping' => TRUE,
          'data' => [
            ['data' => $region['message'], 'colspan' => $columns_count],
          ],
        ];
      }

      // Add form rows, in the order determined at pre-render time.
      foreach ($region['rows_order'] as $name) {
        $element = $rows[$name];

        $row = ['data' => []];
        if (isset($element['#attributes'])) {
          $row += $element['#attributes'];
        }

        // Render children as table cells.
        foreach (Element::children($element) as $cell_key) {
          $child = $element[$cell_key];
          // Do not render a cell for children of #type 'value'.
          if (!(isset($child['#type']) && $child['#type'] == 'value')) {
            $cell = ['data' => $child];
            if (isset($child['#cell_attributes'])) {
              $cell += $child['#cell_attributes'];
            }
            $row['data'][] = $cell;
          }
        }
        $elements['#rows'][] = $row;
      }
    }

    return $elements;
  }

  /**
   * Determines the rendering order of an array representing a tree.
   *
   * Callback for array_reduce() within ::tablePreRender().
   *
   * @param mixed $array
   *   Holds the return value of the previous iteration; in the case of the
   *   first iteration it instead holds the value of the initial array.
   * @param mixed $a
   *   Holds the value of the current iteration.
   *
   * @return array
   *   Array where rendering order has been determined.
   */
  public static function reduceOrder($array, $a) {
    $array = !$array ? [] : $array;
    if ($a['name']) {
      $array[] = $a['name'];
    }
    if (!empty($a['children'])) {
      uasort($a['children'], ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
      $array = array_merge($array, array_reduce($a['children'], [static::class, 'reduceOrder']));
    }

    return $array;
  }

}
