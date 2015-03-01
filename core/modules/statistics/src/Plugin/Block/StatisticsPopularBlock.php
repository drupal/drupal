<?php

/**
 * @file
 * Contains \Drupal\statistics\Plugin\Block\StatisticsPopularBlock.
 */

namespace Drupal\statistics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Popular content' block.
 *
 * @Block(
 *   id = "statistics_popular_block",
 *   admin_label = @Translation("Popular content")
 * )
 */
class StatisticsPopularBlock extends BlockBase {

  /**
   * Number of day's top views to display.
   *
   * @var int
   */
  protected $day_list;

  /**
   * Number of all time views to display.
   *
   * @var int
   */
  protected $all_time_list;

  /**
   * Number of most recent views to display.
   *
   * @var int
   */
  protected $last_list;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'top_day_num' => 0,
      'top_all_num' => 0,
      'top_last_num' => 0
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->hasPermission('access content')) {
      $daytop = $this->configuration['top_day_num'];
      if (!$daytop || !($result = statistics_title_list('daycount', $daytop)) || !($this->day_list = node_title_list($result, $this->t("Today's:")))) {
        return FALSE;
      }
      $alltimetop = $this->configuration['top_all_num'];
      if (!$alltimetop || !($result = statistics_title_list('totalcount', $alltimetop)) || !($this->all_time_list = node_title_list($result, $this->t('All time:')))) {
        return FALSE;
      }
      $lasttop = $this->configuration['top_last_num'];
      if (!$lasttop || !($result = statistics_title_list('timestamp', $lasttop)) || !($this->last_list = node_title_list($result, $this->t('Last viewed:')))) {
        return FALSE;
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Popular content block settings.
    $numbers = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 30, 40);
    $numbers = array('0' => $this->t('Disabled')) + array_combine($numbers, $numbers);
    $form['statistics_block_top_day_num'] = array(
     '#type' => 'select',
     '#title' => $this->t("Number of day's top views to display"),
     '#default_value' => $this->configuration['top_day_num'],
     '#options' => $numbers,
     '#description' => $this->t('How many content items to display in "day" list.'),
    );
    $form['statistics_block_top_all_num'] = array(
      '#type' => 'select',
      '#title' => $this->t('Number of all time views to display'),
      '#default_value' => $this->configuration['top_all_num'],
      '#options' => $numbers,
      '#description' => $this->t('How many content items to display in "all time" list.'),
    );
    $form['statistics_block_top_last_num'] = array(
      '#type' => 'select',
      '#title' => $this->t('Number of most recent views to display'),
      '#default_value' => $this->configuration['top_last_num'],
      '#options' => $numbers,
      '#description' => $this->t('How many content items to display in "recently viewed" list.'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['top_day_num'] = $form_state->getValue('statistics_block_top_day_num');
    $this->configuration['top_all_num'] = $form_state->getValue('statistics_block_top_all_num');
    $this->configuration['top_last_num'] = $form_state->getValue('statistics_block_top_last_num');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = array();

    if ($this->day_list) {
      $content['top_day'] = $this->day_list;
      $content['top_day']['#suffix'] = '<br />';
    }

    if ($this->all_time_list) {
      $content['top_all'] = $this->all_time_list;
      $content['top_all']['#suffix'] = '<br />';
    }

    if ($this->last_list) {
      $content['top_last'] = $this->last_list;
      $content['top_last']['#suffix'] = '<br />';
    }

    return $content;
  }

}
