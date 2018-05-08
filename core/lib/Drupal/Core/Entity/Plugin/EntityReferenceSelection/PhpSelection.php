<?php

namespace Drupal\Core\Entity\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;

/**
 * Defines an alternative to the default Entity Reference Selection plugin.
 *
 * This selection plugin uses PHP for more advanced cases when the entity query
 * cannot filter properly, for example when the target entity type has no
 * 'label' key provided in the entity type plugin definition.
 *
 * @see \Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver
 */
class PhpSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    // No input, return everything from the entity query.
    if ($match === NULL || $match === '') {
      return parent::getReferenceableEntities($match, $match_operator, $limit);
    }

    // Start with the selection results returned by the entity query. Don't use
    // any limit because we have to apply a limit after filtering the items.
    $options = parent::getReferenceableEntities($match, $match_operator);

    // Always use a case-insensitive, escaped match. Entity labels returned by
    // SelectionInterface::getReferenceableEntities() are already escaped, so
    // the incoming $match needs to be escaped as well, making the comparison
    // possible.
    // @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface::getReferenceableEntities()
    if (is_string($match)) {
      $match = Html::escape(mb_strtolower($match));
    }
    elseif (is_array($match)) {
      array_walk($match, function (&$item) {
        $item = Html::escape(mb_strtolower($item));
      });
    }

    $filtered = [];
    $count = 0;
    // Filter target entities by the output of their label() method.
    foreach ($options as $bundle => &$items) {
      foreach ($items as $entity_id => $label) {
        if ($this->matchLabel($match, $match_operator, $label)) {
          $filtered[$bundle][$entity_id] = $label;
          $count++;

          if ($limit && $count >= $limit) {
            break 2;
          }
        }
      }
    }

    return $filtered;
  }

  /**
   * {@inheritdoc}
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    $count = 0;
    foreach ($this->getReferenceableEntities($match, $match_operator) as &$items) {
      $count += count($items);
    }

    return $count;
  }

  /**
   * Matches an entity label to an input string.
   *
   * @param mixed $match
   *   The value to compare. This can be any valid entity query condition value.
   * @param string $match_operator
   *   The comparison operator.
   * @param string $label
   *   The entity label to match against.
   *
   * @return bool
   *   TRUE when matches, FALSE otherwise.
   */
  protected function matchLabel($match, $match_operator, $label) {
    // Always use a case-insensitive value.
    $label = mb_strtolower($label);

    switch ($match_operator) {
      case '=':
        return $label == $match;
      case '>':
        return $label > $match;
      case '<':
        return $label < $match;
      case '>=':
        return $label >= $match;
      case '<=':
        return $label <= $match;
      case '<>':
        return $label != $match;
      case 'IN':
        return array_search($label, $match) !== FALSE;
      case 'NOT IN':
        return array_search($label, $match) === FALSE;
      case 'STARTS_WITH':
        return strpos($label, $match) === 0;
      case 'CONTAINS':
        return strpos($label, $match) !== FALSE;
      case 'ENDS_WITH':
        return mb_substr($label, -mb_strlen($match)) === (string) $match;
      case 'IS NOT NULL':
        return TRUE;
      case 'IS NULL':
        return FALSE;
      default:
        // Invalid match operator.
        return FALSE;
    }
  }

}
