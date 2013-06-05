<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\argument\IndexTid.
 */

namespace Drupal\taxonomy\Plugin\views\argument;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\argument\ManyToOne;

/**
 * Allow taxonomy term ID(s) as argument.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("taxonomy_index_tid")
 */
class IndexTid extends ManyToOne {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['set_breadcrumb'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['set_breadcrumb'] = array(
      '#type' => 'checkbox',
      '#title' => t("Set the breadcrumb for the term parents"),
      '#description' => t('If selected, the breadcrumb trail will include all parent terms, each one linking to this view. Note that this only works if just one term was received.'),
      '#default_value' => !empty($this->options['set_breadcrumb']),
    );
  }

  public function setBreadcrumb(&$breadcrumb) {
    if (empty($this->options['set_breadcrumb']) || !is_numeric($this->argument)) {
      return;
    }

    return views_taxonomy_set_breadcrumb($breadcrumb, $this);
  }

  public function titleQuery() {
    $titles = array();
    $result = db_select('taxonomy_term_data', 'td')
      ->fields('td', array('name'))
      ->condition('td.tid', $this->value)
      ->execute();
    foreach ($result as $term) {
      $titles[] = check_plain($term->name);
    }
    return $titles;
  }

}
