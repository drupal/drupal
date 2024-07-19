<?php

namespace Drupal\views;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Defines a helper class for stuff related to views data.
 */
class ViewsDataHelper {

  /**
   * The views data object, containing the cached information.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $data;

  /**
   * A prepared list of all fields, keyed by base_table and handler type.
   *
   * @var array
   */
  protected $fields;

  /**
   * Constructs a ViewsData object.
   *
   * @param \Drupal\views\ViewsData $views_data
   *   The views data object, containing the cached table information.
   */
  public function __construct(ViewsData $views_data) {
    $this->data = $views_data;
  }

  /**
   * Fetches a list of all fields available for a given base type.
   *
   * @param array|string $base
   *   A list or a single base_table, for example node.
   * @param string $type
   *   The handler type, for example field or filter.
   * @param bool $grouping
   *   Should the result grouping by its 'group' label.
   * @param string $sub_type
   *   An optional sub type. E.g. Allows making an area plugin available for
   *   header only, instead of header, footer, and empty regions.
   *
   * @return array
   *   A keyed array of in the form of 'base_table' => 'Description'.
   */
  public function fetchFields($base, $type, $grouping = FALSE, $sub_type = NULL) {
    if (!$this->fields) {
      $data = $this->data->getAll();
      // This constructs this ginormous multi dimensional array to
      // collect the important data about fields. In the end,
      // the structure looks a bit like this (using nid as an example)
      // $strings['nid']['filter']['title'] = 'string'.
      //
      // This is constructed this way because the above referenced strings
      // can appear in different places in the actual data structure so that
      // the data doesn't have to be repeated a lot. This essentially lets
      // each field have a cheap kind of inheritance.

      foreach ($data as $table => $table_data) {
        $bases = [];
        $strings = [];
        $skip_bases = [];
        foreach ($table_data as $field => $info) {
          // Collect table data from this table
          if ($field == 'table') {
            // Calculate what tables this table can join to.
            if (!empty($info['join'])) {
              $bases = array_keys($info['join']);
            }
            // And it obviously joins to itself.
            $bases[] = $table;
            continue;
          }
          foreach (['field', 'sort', 'filter', 'argument', 'relationship', 'area'] as $key) {
            if (!empty($info[$key])) {
              if ($grouping && !empty($info[$key]['no group by'])) {
                continue;
              }
              if ($sub_type && isset($info[$key]['sub_type']) && (!in_array($sub_type, (array) $info[$key]['sub_type']))) {
                continue;
              }
              if (!empty($info[$key]['skip base'])) {
                foreach ((array) $info[$key]['skip base'] as $base_name) {
                  $skip_bases[$field][$key][$base_name] = TRUE;
                }
              }
              elseif (!empty($info['skip base'])) {
                foreach ((array) $info['skip base'] as $base_name) {
                  $skip_bases[$field][$key][$base_name] = TRUE;
                }
              }
              foreach (['title', 'group', 'help', 'base', 'aliases'] as $string) {
                // First, try the lowest possible level
                if (!empty($info[$key][$string])) {
                  $strings[$field][$key][$string] = $info[$key][$string];
                }
                // Then try the field level
                elseif (!empty($info[$string])) {
                  $strings[$field][$key][$string] = $info[$string];
                }
                // Finally, try the table level
                elseif (!empty($table_data['table'][$string])) {
                  $strings[$field][$key][$string] = $table_data['table'][$string];
                }
                // We don't have any help provided for this field. If a better
                // description should be used for the Views UI you use
                // hook_views_data_alter() in module.views.inc or implement a
                // custom entity views_data handler.
                // @see hook_views_data_alter()
                // @see \Drupal\node\NodeViewsData
                elseif ($string == 'help') {
                  $strings[$field][$key][$string] = '';
                }
                else {
                  if ($string != 'base') {
                    $strings[$field][$key][$string] = new FormattableMarkup("Error: missing @component", ['@component' => $string]);
                  }
                }
              }
            }
          }
        }
        foreach ($bases as $base_name) {
          foreach ($strings as $field => $field_strings) {
            foreach ($field_strings as $type_name => $type_strings) {
              if (empty($skip_bases[$field][$type_name][$base_name])) {
                $this->fields[$base_name][$type_name]["$table.$field"] = $type_strings;
              }
            }
          }
        }
      }
    }

    // If we have an array of base tables available, go through them
    // all and add them together. Duplicate keys will be lost and that's
    // Just Fine.
    if (is_array($base)) {
      $strings = [];
      foreach ($base as $base_table) {
        if (isset($this->fields[$base_table][$type])) {
          $strings += $this->fields[$base_table][$type];
        }
      }
      uasort($strings, [$this, 'fetchedFieldSort']);
      return $strings;
    }

    if (isset($this->fields[$base][$type])) {
      uasort($this->fields[$base][$type], [$this, 'fetchedFieldSort']);
      return $this->fields[$base][$type];
    }
    return [];
  }

  /**
   * Sort function for fetched fields.
   *
   * @param array $a
   *   First item for comparison. The compared items should be associative arrays
   *   that include a 'group' and a 'title' key.
   * @param array $b
   *   Second item for comparison.
   *
   * @return int
   *   Returns -1 if $a comes before $b, 1 other way round and 0 if it cannot be
   *   decided.
   */
  protected static function fetchedFieldSort($a, $b) {
    $a_group = mb_strtolower($a['group']);
    $b_group = mb_strtolower($b['group']);
    if ($a_group != $b_group) {
      return $a_group <=> $b_group;
    }

    $a_title = mb_strtolower($a['title']);
    $b_title = mb_strtolower($b['title']);
    return $a_title <=> $b_title;
  }

}
