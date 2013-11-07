<?php

/**
 * @file
 * Database additions for taxonomy variables. Used in TaxonomyUpgradePathTest.
 *
 * The drupal-7.bare.standard_all.php file is imported before this dump, so the
 * two form the database structure expected in tests altogether.
 */

// Add a new taxonomy_term in taxonomy_term_data table.
db_insert('taxonomy_term_data')
  ->fields(array(
    'tid',
    'vid',
    'name',
    'description',
    'format',
    'weight',
  ))
  ->values(array(
    'tid' => '2',
    'vid' => '1',
    'name' => 'Default term',
    'description' => '',
    'format' => NULL,
    'weight' => '0',
  ))
  ->execute();

// Add a new taxonomy_term in taxonomy_term_hierarchy table.
db_insert('taxonomy_term_hierarchy')
  ->fields(array(
    'tid',
    'parent',
  ))
  ->values(array(
    'tid' => '2',
    'parent' => '0',
  ))
  ->execute();

// Set the taxonomy_term added as default value for field_tags instance.
db_update('field_config_instance')
  ->fields(array(
    'data' => 'a:7:{s:5:"label";s:4:"Tags";s:11:"description";s:63:"Enter a comma-separated list of words to describe your content.";s:6:"widget";a:5:{s:6:"weight";i:-4;s:4:"type";s:21:"taxonomy_autocomplete";s:6:"module";s:8:"taxonomy";s:6:"active";i:0;s:8:"settings";a:2:{s:4:"size";i:60;s:17:"autocomplete_path";s:21:"taxonomy/autocomplete";}}s:7:"display";a:2:{s:7:"default";a:5:{s:4:"type";s:28:"taxonomy_term_reference_link";s:6:"weight";i:10;s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:6:"module";s:8:"taxonomy";}s:6:"teaser";a:5:{s:4:"type";s:28:"taxonomy_term_reference_link";s:6:"weight";i:10;s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:6:"module";s:8:"taxonomy";}}s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:8:"required";i:0;s:13:"default_value";a:1:{i:0;a:8:{s:3:"tid";s:1:"2";s:3:"vid";s:1:"1";s:4:"name";s:12:"Default term";s:11:"description";s:0:"";s:6:"format";s:13:"filtered_html";s:6:"weight";s:1:"0";s:23:"vocabulary_machine_name";s:4:"tags";s:11:"rdf_mapping";a:5:{s:7:"rdftype";a:1:{i:0;s:12:"skos:Concept";}s:4:"name";a:1:{s:10:"predicates";a:2:{i:0;s:10:"rdfs:label";i:1;s:14:"skos:prefLabel";}}s:11:"description";a:1:{s:10:"predicates";a:1:{i:0;s:15:"skos:definition";}}s:3:"vid";a:2:{s:10:"predicates";a:1:{i:0;s:13:"skos:inScheme";}s:4:"type";s:3:"rel";}s:6:"parent";a:2:{s:10:"predicates";a:1:{i:0;s:12:"skos:broader";}s:4:"type";s:3:"rel";}}}}}')
  )
  ->condition('field_name', 'field_tags')
  ->execute();
