<?php

/**
 * @file
 * Database additions for Drupal\rdf\Tests\RdfMappingUpgradePathTest.
 *
 * This dump only contains data for the RDF module. The
 * drupal-7.bare.standard_all.database.php file is imported before this dump,
 * so the combination of the two forms the database structure expected in tests.
 */

// Create a new mapping to test reverse relations.
$rev_mapping = array(
  'field_rev' => array(
    'predicates' => array('foo:rev'),
    'type' => 'rev',
  ),
  'field_notrev' => array(
    'predicates' => array('foo:rel'),
    'type' => 'rel',
  ),
);
db_insert('rdf_mapping')
  ->fields(array(
    'type',
    'bundle',
    'mapping',
  ))
  ->values(array(
    'type' => 'node',
    'bundle'=> 'rev_test',
    'mapping' => serialize($rev_mapping),
  ))
  ->execute();

// Alter the stored mapping for articles.
$altered_mapping = array(
  'rdftype' => array('foo:Type'),
  'field_image' => array(
    'predicates' => array('foo:image'),
  ),
);
db_update('rdf_mapping')
  ->fields(array(
    'type' => 'node',
    'bundle'=> 'article',
    'mapping' => serialize($altered_mapping),
  ))
  ->condition('type', 'node')
  ->condition('bundle', 'article')
  ->execute();
