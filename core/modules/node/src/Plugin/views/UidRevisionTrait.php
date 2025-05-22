<?php

declare(strict_types=1);

namespace Drupal\node\Plugin\views;

/**
 * Checks for nodes that a user posted or created a revision on.
 */
trait UidRevisionTrait {

  /**
   * Checks for nodes that a user posted or created a revision on.
   *
   * @param array $uids
   *   A list of user ids.
   * @param int $group
   *   See \Drupal\views\Plugin\views\query\Sql::addWhereExpression() $group.
   */
  public function uidRevisionQuery(array $uids, int $group = 0): void {
    $this->ensureMyTable();

    // As per https://www.php.net/manual/en/pdo.prepare.php "you cannot use a
    // named parameter marker of the same name more than once in a prepared
    // statement".
    $placeholder_1 = $this->placeholder() . '[]';
    $placeholder_2 = $this->placeholder() . '[]';

    $args = array_values($uids);

    $this->query->addWhereExpression($group, "$this->tableAlias.uid IN ($placeholder_1) OR
      EXISTS (SELECT 1 FROM {node_revision} nr WHERE nr.revision_uid IN ($placeholder_2) AND nr.nid = $this->tableAlias.nid)", [
        $placeholder_1 => $args,
        $placeholder_2 => $args,
      ]);
  }

}
