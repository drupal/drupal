<?php

declare(strict_types=1);

namespace Drupal\block_content_test\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Test EntityReferenceSelection with conditions on the 'reusable' field.
 */
class TestSelection extends DefaultSelection {

  /**
   * The condition type.
   *
   * @var string
   */
  protected $conditionType;

  /**
   * Whether to set the condition for reusable or non-reusable blocks.
   *
   * @var bool
   */
  protected $isReusable;

  /**
   * Sets the test mode.
   *
   * @param string $condition_type
   *   The condition type.
   * @param bool $is_reusable
   *   Whether to set the condition for reusable or non-reusable blocks.
   */
  public function setTestMode($condition_type = NULL, $is_reusable = NULL) {
    $this->conditionType = $condition_type;
    $this->isReusable = $is_reusable;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    if ($this->conditionType) {
      /** @var \Drupal\Core\Database\Query\ConditionInterface $add_condition */
      $add_condition = NULL;
      switch ($this->conditionType) {
        case 'base':
          $add_condition = $query;
          break;

        case 'group':
          $group = $query->andConditionGroup()
            ->exists('type');
          $add_condition = $group;
          $query->condition($group);
          break;

        case "nested_group":
          $query->exists('type');
          $sub_group = $query->andConditionGroup()
            ->exists('type');
          $add_condition = $sub_group;
          $group = $query->andConditionGroup()
            ->exists('type')
            ->condition($sub_group);
          $query->condition($group);
          break;
      }
      if ($this->isReusable) {
        $add_condition->condition('reusable', 1);
      }
      else {
        $add_condition->condition('reusable', 0);
      }
    }
    return $query;
  }

}
