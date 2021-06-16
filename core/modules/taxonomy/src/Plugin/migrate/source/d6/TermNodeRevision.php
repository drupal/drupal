<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d6;

/**
 * Source returning tids from the term_node table for the non-current revision.
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
