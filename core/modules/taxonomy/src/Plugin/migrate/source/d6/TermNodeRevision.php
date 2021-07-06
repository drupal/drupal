<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d6;

/**
 * Drupal 6 term/node relationships (non-current revision) source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\taxonomy\Plugin\migrate\source\d6\TermNode
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d6_term_node_revision",
 *   source_module = "taxonomy"
 * )
 */
class TermNodeRevision extends TermNode {

  /**
   * {@inheritdoc}
   */
  const JOIN = 'tn.nid = n.nid AND tn.vid != n.vid';

}
