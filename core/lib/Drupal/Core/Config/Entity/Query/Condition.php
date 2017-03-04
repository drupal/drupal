<?php

namespace Drupal\Core\Config\Entity\Query;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Query\ConditionBase;
use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\Core\Entity\Query\QueryException;

/**
 * Defines the condition class for the config entity query.
 *
 * @see \Drupal\Core\Config\Entity\Query\Query
 */
class Condition extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function compile($configs) {
    $and = strtoupper($this->conjunction) == 'AND';
    $single_conditions = [];
    $condition_groups = [];
    foreach ($this->conditions as $condition) {
      if ($condition['field'] instanceof ConditionInterface) {
        $condition_groups[] = $condition;
      }
      else {
        if (!isset($condition['operator'])) {
          $condition['operator'] = is_array($condition['value']) ? 'IN' : '=';
        }

        // Lowercase condition value(s) for case-insensitive matches.
        if (is_array($condition['value'])) {
          $condition['value'] = array_map('Drupal\Component\Utility\Unicode::strtolower', $condition['value']);
        }
        elseif (!is_bool($condition['value'])) {
          $condition['value'] = Unicode::strtolower($condition['value']);
        }

        $single_conditions[] = $condition;
      }
    }
    $return = [];
    if ($single_conditions) {
      foreach ($configs as $config_name => $config) {
        foreach ($single_conditions as $condition) {
          $match = $this->matchArray($condition, $config, explode('.', $condition['field']));
          // If AND and it's not matching, then the rest of conditions do not
          // matter and this config object does not match.
          // If OR and it is matching, then the rest of conditions do not
          // matter and this config object does match.
          if ($and != $match ) {
            break;
          }
        }
        if ($match) {
          $return[$config_name] = $config;
        }
      }
    }
    elseif (!$condition_groups || $and) {
      // If there were no single conditions then either:
      // - Complex conditions, OR: need to start from no entities.
      // - Complex conditions, AND: need to start from all entities.
      // - No complex conditions (AND/OR doesn't matter): need to return all
      //   entities.
      $return = $configs;
    }
    foreach ($condition_groups as $condition) {
      $group_entities = $condition['field']->compile($configs);
      if ($and) {
        $return = array_intersect_key($return, $group_entities);
      }
      else {
        $return = $return + $group_entities;
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($field, $langcode = NULL) {
    return $this->condition($field, NULL, 'IS NOT NULL', $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function notExists($field, $langcode = NULL) {
    return $this->condition($field, NULL, 'IS NULL', $langcode);
  }

  /**
   * Matches for an array representing one or more config paths.
   *
   * @param array $condition
   *   The condition array as created by the condition() method.
   * @param array $data
   *   The config array or part of it.
   * @param array $needs_matching
   *   The list of config array keys needing a match. Can contain config keys
   *   and the * wildcard.
   * @param array $parents
   *   The current list of parents.
   *
   * @return bool
   *   TRUE when the condition matched to the data else FALSE.
   */
  protected function matchArray(array $condition, array $data, array $needs_matching, array $parents = []) {
    $parent = array_shift($needs_matching);
    if ($parent === '*') {
      $candidates = array_keys($data);
    }
    else {
      // Avoid a notice when calling match() later.
      if (!isset($data[$parent])) {
        $data[$parent] = NULL;
      }
      $candidates = [$parent];
    }
    foreach ($candidates as $key) {
      if ($needs_matching) {
        if (is_array($data[$key])) {
          $new_parents = $parents;
          $new_parents[] = $key;
          if ($this->matchArray($condition, $data[$key], $needs_matching, $new_parents)) {
            return TRUE;
          }
        }
      }
      // Only try to match a scalar if there are no remaining keys in
      // $needs_matching as this indicates that we are looking for a specific
      // subkey and a scalar can never match that.
      elseif ($this->match($condition, $data[$key])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Perform the actual matching.
   *
   * @param array $condition
   *   The condition array as created by the condition() method.
   * @param string $value
   *   The value to match against.
   *
   * @return bool
   *   TRUE when matches else FALSE.
   */
  protected function match(array $condition, $value) {
    if (isset($value)) {
      // We always want a case-insensitive match.
      if (!is_bool($value)) {
        $value = Unicode::strtolower($value);
      }

      switch ($condition['operator']) {
        case '=':
          return $value == $condition['value'];
        case '>':
          return $value > $condition['value'];
        case '<':
          return $value < $condition['value'];
        case '>=':
          return $value >= $condition['value'];
        case '<=':
          return $value <= $condition['value'];
        case '<>':
          return $value != $condition['value'];
        case 'IN':
          return array_search($value, $condition['value']) !== FALSE;
        case 'NOT IN':
          return array_search($value, $condition['value']) === FALSE;
        case 'STARTS_WITH':
          return strpos($value, $condition['value']) === 0;
        case 'CONTAINS':
          return strpos($value, $condition['value']) !== FALSE;
        case 'ENDS_WITH':
          return substr($value, -strlen($condition['value'])) === (string) $condition['value'];
        case 'IS NOT NULL':
          return TRUE;
        case 'IS NULL':
          return FALSE;
        default:
          throw new QueryException('Invalid condition operator.');
      }
    }
    return $condition['operator'] === 'IS NULL';
  }

}
