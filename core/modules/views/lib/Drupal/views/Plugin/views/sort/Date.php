<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\sort\Date.
 */

namespace Drupal\views\Plugin\views\sort;

/**
 * Basic sort handler for dates.
 *
 * This handler enables granularity, which is the ability to make dates
 * equivalent based upon nearness.
 *
 * @PluginID("date")
 */
class Date extends SortPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['granularity'] = array('default' => 'second');

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['granularity'] = array(
      '#type' => 'radios',
      '#title' => t('Granularity'),
      '#options' => array(
        'second' => t('Second'),
        'minute' => t('Minute'),
        'hour'   => t('Hour'),
        'day'    => t('Day'),
        'month'  => t('Month'),
        'year'   => t('Year'),
      ),
      '#description' => t('The granularity is the smallest unit to use when determining whether two dates are the same; for example, if the granularity is "Year" then all dates in 1999, regardless of when they fall in 1999, will be considered the same date.'),
      '#default_value' => $this->options['granularity'],
    );
  }

  /**
   * Called to add the sort to a query.
   */
  public function query() {
    $this->ensureMyTable();
    switch ($this->options['granularity']) {
      case 'second':
      default:
        $this->query->addOrderBy($this->tableAlias, $this->realField, $this->options['order']);
        return;
      case 'minute':
        $formula = $this->getDateFormat('YmdHi');
        break;
      case 'hour':
        $formula = $this->getDateFormat('YmdH');
        break;
      case 'day':
        $formula = $this->getDateFormat('Ymd');
        break;
      case 'month':
        $formula = $this->getDateFormat('Ym');
        break;
      case 'year':
        $formula = $this->getDateFormat('Y');
        break;
    }

    // Add the field.
    $this->query->addOrderBy(NULL, $formula, $this->options['order'], $this->tableAlias . '_' . $this->field . '_' . $this->options['granularity']);
  }

}
