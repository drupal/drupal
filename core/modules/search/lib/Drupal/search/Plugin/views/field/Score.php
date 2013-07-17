<?php

/**
 * @file
 * Definition of Drupal\search\Plugin\views\field\Score.
 */

namespace Drupal\search\Plugin\views\field;

use Drupal\views\Plugin\views\field\Numeric;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to provide simple renderer that allows linking to a node.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("search_score")
 */
class Score extends Numeric {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['alternate_sort'] = array('default' => '');
    $options['alternate_order'] = array('default' => 'asc');

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $style_options = $this->view->display_handler->getOption('style_options');
    if (isset($style_options['default']) && $style_options['default'] == $this->options['id']) {
      $handlers = $this->view->display_handler->getHandlers('field');
      $options = array('' => t('No alternate'));
      foreach ($handlers as $id => $handler) {
        $options[$id] = $handler->adminLabel();
      }

      $form['alternate_sort'] = array(
        '#type' => 'select',
        '#title' => t('Alternative sort'),
        '#description' => t('Pick an alternative default table sort field to use when the search score field is unavailable.'),
        '#options' => $options,
        '#default_value' => $this->options['alternate_sort'],
      );

      $form['alternate_order'] = array(
        '#type' => 'select',
        '#title' => t('Alternate sort order'),
        '#options' => array('asc' => t('Ascending'), 'desc' => t('Descending')),
        '#default_value' => $this->options['alternate_order'],
      );
    }

    parent::buildOptionsForm($form, $form_state);
  }

  public function query() {
    // Check to see if the search filter added 'score' to the table.
    // Our filter stores it as $handler->search_score -- and we also
    // need to check its relationship to make sure that we're using the same
    // one or obviously this won't work.
    foreach ($this->view->filter as $handler) {
      if (isset($handler->search_score) && $handler->relationship == $this->relationship) {
        $this->field_alias = $handler->search_score;
        $this->tableAlias = $handler->tableAlias;
        return;
      }
    }

    // Hide this field if no search filter is in place.
    $this->options['exclude'] = TRUE;
    if (!empty($this->options['alternate_sort'])) {
      if (isset($this->view->style_plugin->options['default']) && $this->view->style_plugin->options['default'] == $this->options['id']) {
        // Since the style handler initiates fields, we plug these values right into the active handler.
        $this->view->style_plugin->options['default'] = $this->options['alternate_sort'];
        $this->view->style_plugin->options['order'] = $this->options['alternate_order'];
      }
    }
  }

  public function render($values) {
    // Only render if we exist.
    if (isset($this->tableAlias)) {
      return parent::render($values);
    }
  }

}
