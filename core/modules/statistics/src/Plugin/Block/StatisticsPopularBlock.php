<?php

namespace Drupal\statistics\Plugin\Block;

use Drupal\Core\Access\AccessResult;
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
    return AccessResult::allowedIfHasPermission($account, 'access content');
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

    if ($this->configuration['top_day_num'] > 0) {
      $result = statistics_title_list('daycount', $this->configuration['top_day_num']);
      if ($result) {
        $content['top_day'] = node_title_list($result, $this->t("Today's:"));
        $content['top_day']['#suffix'] = '<br />';
      }
    }

    if ($this->configuration['top_all_num'] > 0) {
      $result = statistics_title_list('totalcount', $this->configuration['top_all_num']);
      if ($result) {
        $content['top_all'] = node_title_list($result, $this->t('All time:'));
        $content['top_all']['#suffix'] = '<br />';
      }
    }

    if ($this->configuration['top_last_num'] > 0) {
      $result = statistics_title_list('timestamp', $this->configuration['top_last_num']);
      $content['top_last'] = node_title_list($result, $this->t('Last viewed:'));
      $content['top_last']['#suffix'] = '<br />';
    }

    return $content;
  }

}
