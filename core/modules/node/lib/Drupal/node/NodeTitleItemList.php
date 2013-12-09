<?php

/**
 * @file
 * Contains \Drupal\node\NodeTitleItemList.
 */

namespace Drupal\node;

use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Field\FieldItemList;

/**
 * @todo This class is a temporary hack for allowing the label of the node title
 *   field to vary by node type. Remove it when https://drupal.org/node/2114707
 *   is solved.
 */
class NodeTitleItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   *
   * The typehint for $definition is a class rather than an interface, because
   * there is no interface for setLabel().
   */
  public function __construct(FieldDefinition $definition, $name, NodeInterface $node) {
    $node_type = node_type_load($node->getType());
    if (isset($node_type->title_label)) {
      $definition->setLabel($node_type->title_label);
    }
    parent::__construct($definition, $name, $node);
  }

}
