<?php

namespace Drupal\views\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsSort;

/**
 * Basic sort handler for dates.
 *
 * This handler enables granularity, which is the ability to make dates
 * equivalent based upon nearness.
 */
#[ViewsSort("date")]
class Date extends SortPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['granularity'] = ['default' => 'second'];

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['granularity'] = [
      '#type' => 'radios',
      '#title' => $this->t('Granularity'),
      '#options' => [
        'second' => $this->t('Second'),
        'minute' => $this->t('Minute'),
        'hour'   => $this->t('Hour'),
        'day'    => $this->t('Day'),
        'month'  => $this->t('Month'),
        'year'   => $this->t('Year'),
      ],
      '#description' => $this->t('The granularity is the smallest unit to use when determining whether two dates are the same; for example, if the granularity is "Year" then all dates in 1999, regardless of when they fall in 1999, will be considered the same date.'),
      '#default_value' => $this->options['granularity'],
    ];
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
