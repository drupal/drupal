<?php
// $Id: base.inc,v 1.3.4.6 2010/12/01 20:05:06 dereine Exp $
/**
 * @file
 *
 * Provides the basic object definitions used by plugins and handlers.
 */

/**
 * Basic definition for many views objects
 */
class views_object {
  /**
   * Except for displays, options for the object will be held here.
   */
  var $options = array();
  /**
   * Information about options for all kinds of purposes will be held here.
   * @code
   * 'option_name' => array(
   *  - 'default' => default value,
   *  - 'translatable' => TRUE/FALSE (wrap in t() on export if true),
   *  - 'contains' => array of items this contains, with its own defaults, etc.
   *      If contains is set, the default will be ignored and assumed to
   *      be array()
   *
   *  ),
   *  @endcode
   * Each option may have any of the following functions:
   *  - export_option_OPTIONNAME -- Special export handling if necessary.
   *  - translate_option_OPTIONNAME -- Special handling for translating data
   *    within the option, if necessary.
   */
  function option_definition() { return array(); }

  /**
   * Views handlers use a special construct function so that we can more
   * easily construct them with variable arguments.
   */
  function construct() { $this->set_default_options(); }

  /**
   * Set default options on this object. Called by the constructor in a
   * complex chain to deal with backward compatibility.
   */
  function options(&$options) { }

  /**
   * Set default options.
   * For backward compatibility, it sends the options array; this is a
   * feature that will likely disappear at some point.
   */
  function set_default_options() {
    $this->_set_option_defaults($this->options, $this->option_definition());

    // Retained for complex defaults plus backward compatibility.
    $this->options($this->options);
  }

  function _set_option_defaults(&$storage, $options, $level = 0) {
    foreach ($options as $option => $definition) {
      if (isset($definition['contains']) && is_array($definition['contains'])) {
        $storage[$option] = array();
        $this->_set_option_defaults($storage[$option], $definition['contains'], $level++);
      }
      elseif (!empty($definition['translatable']) && !empty($definition['default'])) {
        $storage[$option] = t($definition['default']);
      }
      else {
        $storage[$option] = isset($definition['default']) ? $definition['default'] : NULL;
      }
    }
  }

  /**
   * Unpack options over our existing defaults, drilling down into arrays
   * so that defaults don't get totally blown away.
   */
  function unpack_options(&$storage, $options, $definition = NULL, $all = TRUE, $check = TRUE, $localization_keys = array()) {
    if ($check && !is_array($options)) {
      return;
    }

    if (!isset($definition)) {
      $definition = $this->option_definition();
    }

    if (!empty($this->view)) {
      // Ensure we have a localization plugin.
      $this->view->init_localization();
    }

    foreach ($options as $key => $value) {
      if (is_array($value)) {
        // Ignore arrays with no definition.
        if (!$all && empty($definition[$key])) {
          continue;
        }

        if (!isset($storage[$key]) || !is_array($storage[$key])) {
          $storage[$key] = array();
        }

        // If we're just unpacking our known options, and we're dropping an
        // unknown array (as might happen for a dependent plugin fields) go
        // ahead and drop that in.
        if (!$all && isset($definition[$key]) && !isset($definition[$key]['contains'])) {
          $storage[$key] = $value;
          continue;
        }

        $this->unpack_options($storage[$key], $value, isset($definition[$key]['contains']) ? $definition[$key]['contains'] : array(), $all, FALSE);
      }
      elseif (!empty($definition[$key]['translatable']) && !empty($value)) {
        $storage[$key] = t($value);
        $this->unpack_options($storage[$key], array(), isset($definition[$key]['contains']) ? $definition[$key]['contains'] : array(), $all, FALSE, array_merge($localization_keys, array($key)));
      }
      // Don't localize strings during editing. When editing, we need to work with
      // the original data, not the translated version.
      else if (empty($this->view->editing) && !empty($definition[$key]['translatable']) && !empty($value) || !empty($definition['contains'][$key]['translatable']) && !empty($value)) {
        if (!empty($this->view) && $this->view->is_translatable()) {
          // Allow other modules to make changes to the string before it's
          // sent for translation.
          // The $keys array is built from the view name, any localization keys
          // sent in, and the name of the property being processed.
          $translation_data = array(
          'value' => $value,
          'format' => isset($options[$key . '_format']) ? $options[$key . '_format'] : NULL,
          'keys' => array_merge(array($this->view->name), $localization_keys, array($key)),
          );
          $storage[$key] = $this->view->localization_plugin->translate($translation_data);
        }
        // Otherwise, this is a code-based string, so we can use t().
        else {
          $storage[$key] = t($value);
        }
      }
      else if ($all || !empty($definition[$key])) {
        $storage[$key] = $value;
      }
    }
  }

  /**
   * Let the handler know what its full definition is.
   */
  function set_definition($definition) {
    $this->definition = $definition;
    if (isset($definition['field'])) {
      $this->real_field = $definition['field'];
    }
  }

  function destroy() {
    if (isset($this->view)) {
      unset($this->view);
    }

    if (isset($this->display)) {
      unset($this->display);
    }

    if (isset($this->query)) {
      unset($this->query);
    }
  }

  function export_options($indent, $prefix) {
    $output = '';
    foreach ($this->option_definition() as $option => $definition) {
      $output .= $this->export_option($indent, $prefix, $this->options, $option, $definition, array());
    }

    return $output;
  }

  function export_option($indent, $prefix, $storage, $option, $definition, $parents) {
    // Do not export options for which we have no settings.
    if (!isset($storage[$option])) {
      return;
    }

    if (isset($definition['export'])) {
      if ($definition['export'] === FALSE) {
        return;
      }

      // Special handling for some items
      if (method_exists($this, $definition['export'])) {
        return $this->{$definition['export']}($indent, $prefix, $storage, $option, $definition, $parents);
      }
    }

    // Add the current option to the parents tree.
    $parents[] = $option;
    $output = '';

    // If it has child items, export those separately.
    if (isset($definition['contains'])) {
      foreach ($definition['contains'] as $sub_option => $sub_definition) {
        $output .= $this->export_option($indent, $prefix, $storage[$option], $sub_option, $sub_definition, $parents);
      }
    }
    // Otherwise export just this item.
    else {
      $default = isset($definition['default']) ? $definition['default'] : NULL;
      $value = $storage[$option];
      if (isset($definition['bool'])) {
        $value = (bool) $value;
      }

      if ($value !== $default) {
        $output .= $indent . $prefix . "['" . implode("']['", $parents) . "'] = ";
        if (isset($definition['bool'])) {
          $output .= empty($storage[$option]) ? 'FALSE' : 'TRUE';
        }
        else {
          $output .= views_var_export($storage[$option], $indent);
        }

        $output .= ";\n";
      }
    }
    return $output;
  }

  /**
   * Unpacks each handler to store translatable texts.
   */
  function unpack_translatables(&$translatable, $parents = array()) {
    foreach ($this->option_definition() as $option => $definition) {
      $this->unpack_translatable($translatable, $this->options, $option, $definition, $parents, array());
    }
  }

  /**
   * Unpack a single option definition.
   *
   * This function run's through all suboptions recursive.
   *
   * @param $translatable
   *   Stores all availible translatable items.
   * @param $storage
   * @param $option
   * @param $definition
   * @param $parents
   * @param $keys
   */
  function unpack_translatable(&$translatable, $storage, $option, $definition, $parents, $keys = array()) {
    // Do not export options for which we have no settings.
    if (!isset($storage[$option])) {
      return;
    }

    // Special handling for some items
    if (isset($definition['unpack_translatable']) && method_exists($this, $definition['unpack_translatable'])) {
      return $this->{$definition['unpack_translatable']}($translatable, $storage, $option, $definition, $parents, $keys);
    }

    if (isset($definition['translatable'])) {
      if ($definition['translatable'] === FALSE) {
        return;
      }
    }

    // Add the current option to the parents tree.
    $parents[] = $option;

    // If it has child items, unpack those separately.
    if (isset($definition['contains'])) {
      foreach ($definition['contains'] as $sub_option => $sub_definition) {
        $translation_keys = array_merge($keys, array($sub_option));
        $this->unpack_translatable($translatable, $storage[$option], $sub_option, $sub_definition, $parents, $translation_keys);
      }
    }

    // @todo Figure out this double definition stuff.
    $options = $storage[$option];
    if (is_array($options)) {
      foreach ($options as $key => $value) {
        $translation_keys = array_merge($keys, array($key));
        if (is_array($value)) {
          $this->unpack_translatable($translatable, $storage, $key, $definition, $parents, $translation_keys);
        }
        else if (!empty($definition[$key]['translatable']) && !empty($value)) {
          // Build source data and add to the array
          $translatable[] = array(
            'value' => $value,
            'keys' => $translation_keys,
            'format' => isset($options[$key . '_format']) ? $options[$key . '_format'] : NULL,
          );
        }
      }
    }
    else if (!empty($definition['translatable']) && !empty($options)) {
      $value = $options;
      // Build source data and add to the array
      $translatable[] = array(
        'value' => $value,
        'keys' => isset($translation_keys) ? $translation_keys : $parents,
      );
    }
  }
}
