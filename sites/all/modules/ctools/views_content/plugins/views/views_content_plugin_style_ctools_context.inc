<?php
// $Id: views_content_plugin_style_ctools_context.inc,v 1.2 2010/10/11 22:18:23 sdboyer Exp $
/**
 * @file
 * Contains the default style plugin.
 */

/**
 * Default style plugin to render rows one after another with no
 * decorations.
 *
 * @ingroup views_style_plugins
 */
class views_content_plugin_style_ctools_context extends views_plugin_style {
  var $rows = array();

  /**
   * Set default options
   */
  function options(&$options) {
    parent::options($options);
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
  }

  /**
   * Render the display in this style.
   */
  function render() {
    if (!empty($this->view->display_handler->previewing)) {
      return parent::render();
    }

    $this->rows = array();
    if ($this->uses_row_plugin() && empty($this->row_plugin)) {
      vpr('views_plugin_style_default: Missing row plugin');
      return;
    }

    // Group the rows according to the grouping field, if specified.
    $sets = $this->render_grouping($this->view->result, $this->options['grouping']);

    // Render each group separately and concatenate.  Plugins may override this
    // method if they wish some other way of handling grouping.
    $output = '';
    foreach ($sets as $title => $records) {
      foreach ($records as $row_index => $row) {
        $this->view->row_index = $row_index;
        $this->rows[$row_index] = $this->row_plugin->render($row);
      }
    }
    unset($this->view->row_index);
    return $this->rows;
  }

}
