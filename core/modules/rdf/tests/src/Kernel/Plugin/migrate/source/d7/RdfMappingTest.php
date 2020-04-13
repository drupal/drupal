<?php

namespace Drupal\Tests\rdf\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests Drupal 7 RDF mappings source plugin.
 *
 * @covers \Drupal\rdf\Plugin\migrate\source\d7\RdfMapping
 *
 * @group rdf
 */
class RdfMappingTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'migrate_drupal',
    'node',
    'rdf',
    'taxonomy',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['rdf_mapping'] = [
      [
        'type' => 'comment',
        'bundle' => 'comment_node_article',
        'mapping' => 'a:8:{s:7:"rdftype";a:2:{i:0;s:9:"sioc:Post";i:1;s:13:"sioct:Comment";}s:5:"title";a:1:{s:10:"predicates";a:1:{i:0;s:8:"dc:title";}}s:7:"created";a:3:{s:10:"predicates";a:2:{i:0;s:7:"dc:date";i:1;s:10:"dc:created";}s:8:"datatype";s:12:"xsd:dateTime";s:8:"callback";s:12:"date_iso8601";}s:7:"changed";a:3:{s:10:"predicates";a:1:{i:0;s:11:"dc:modified";}s:8:"datatype";s:12:"xsd:dateTime";s:8:"callback";s:12:"date_iso8601";}s:12:"comment_body";a:1:{s:10:"predicates";a:1:{i:0;s:15:"content:encoded";}}s:3:"pid";a:2:{s:10:"predicates";a:1:{i:0;s:13:"sioc:reply_of";}s:4:"type";s:3:"rel";}s:3:"uid";a:2:{s:10:"predicates";a:1:{i:0;s:16:"sioc:has_creator";}s:4:"type";s:3:"rel";}s:4:"name";a:1:{s:10:"predicates";a:1:{i:0;s:9:"foaf:name";}}}',
      ],
      [
        'type' => 'node',
        'bundle' => 'article',
        'mapping' => 'a:9:{s:7:"rdftype";a:2:{i:0;s:9:"sioc:Item";i:1;s:13:"foaf:Document";}s:5:"title";a:1:{s:10:"predicates";a:1:{i:0;s:8:"dc:title";}}s:7:"created";a:3:{s:10:"predicates";a:2:{i:0;s:7:"dc:date";i:1;s:10:"dc:created";}s:8:"datatype";s:12:"xsd:dateTime";s:8:"callback";s:12:"date_iso8601";}s:7:"changed";a:3:{s:10:"predicates";a:1:{i:0;s:11:"dc:modified";}s:8:"datatype";s:12:"xsd:dateTime";s:8:"callback";s:12:"date_iso8601";}s:4:"body";a:1:{s:10:"predicates";a:1:{i:0;s:15:"content:encoded";}}s:3:"uid";a:2:{s:10:"predicates";a:1:{i:0;s:16:"sioc:has_creator";}s:4:"type";s:3:"rel";}s:4:"name";a:1:{s:10:"predicates";a:1:{i:0;s:9:"foaf:name";}}s:13:"comment_count";a:2:{s:10:"predicates";a:1:{i:0;s:16:"sioc:num_replies";}s:8:"datatype";s:11:"xsd:integer";}s:13:"last_activity";a:3:{s:10:"predicates";a:1:{i:0;s:23:"sioc:last_activity_date";}s:8:"datatype";s:12:"xsd:dateTime";s:8:"callback";s:12:"date_iso8601";}}',
      ],
      [
        'type' => 'taxonomy_term',
        'bundle' => 'tags',
        'mapping' => 'a:5:{s:7:"rdftype";a:1:{i:0;s:12:"skos:Concept";}s:4:"name";a:1:{s:10:"predicates";a:2:{i:0;s:10:"rdfs:label";i:1;s:14:"skos:prefLabel";}}s:11:"description";a:1:{s:10:"predicates";a:1:{i:0;s:15:"skos:definition";}}s:3:"vid";a:2:{s:10:"predicates";a:1:{i:0;s:13:"skos:inScheme";}s:4:"type";s:3:"rel";}s:6:"parent";a:2:{s:10:"predicates";a:1:{i:0;s:12:"skos:broader";}s:4:"type";s:3:"rel";}}',
      ],
      [
        'type' => 'user',
        'bundle' => 'user',
        'mapping' => 'a:3:{s:7:"rdftype";a:1:{i:0;s:16:"sioc:UserAccount";}s:4:"name";a:1:{s:10:"predicates";a:1:{i:0;s:9:"foaf:name";}}s:8:"homepage";a:2:{s:10:"predicates";a:1:{i:0;s:9:"foaf:page";}s:4:"type";s:3:"rel";}}',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'type' => 'comment',
        'bundle' => 'comment_node_article',
        'types' => [
          'sioc:Post',
          'sioct:Comment',
        ],
        'fieldMappings' => [
          'changed' => [
            'predicates' => [
              'dc:modified',
            ],
            'datatype' => 'xsd:dateTime',
            'callback' => 'date_iso8601',
          ],
          'comment_body' => [
            'predicates' => [
              'content:encoded',
            ],
          ],
          'created' => [
            'predicates' => [
              'dc:date',
              'dc:created',
            ],
            'datatype' => 'xsd:dateTime',
            'callback' => 'date_iso8601',
          ],
          'name' => [
            'predicates' => [
              'foaf:name',
            ],
          ],
          'pid' => [
            'predicates' => [
              'sioc:reply_of',
            ],
            'type' => 'rel',
          ],
          'title' => [
            'predicates' => [
              'dc:title',
            ],
          ],
          'uid' => [
            'predicates' => [
              'sioc:has_creator',
            ],
            'type' => 'rel',
          ],
        ],
      ],
      [
        'type' => 'node',
        'bundle' => 'article',
        'types' => [
          'sioc:Item',
          'foaf:Document',
        ],
        'fieldMappings' => [
          'body' => [
            'predicates' => [
              'content:encoded',
            ],
          ],
          'changed' => [
            'predicates' => [
              'dc:modified',
            ],
            'datatype' => 'xsd:dateTime',
            'callback' => 'date_iso8601',
          ],
          'comment_count' => [
            'predicates' => [
              'sioc:num_replies',
            ],
            'datatype' => 'xsd:integer',
          ],
          'created' => [
            'predicates' => [
              'dc:date',
              'dc:created',
            ],
            'datatype' => 'xsd:dateTime',
            'callback' => 'date_iso8601',
          ],
          'last_activity' => [
            'predicates' => [
              'sioc:last_activity_date',
            ],
            'datatype' => 'xsd:dateTime',
            'callback' => 'date_iso8601',
          ],
          'name' => [
            'predicates' => [
              'foaf:name',
            ],
          ],
          'title' => [
            'predicates' => [
              'dc:title',
            ],
          ],
          'uid' => [
            'predicates' => [
              'sioc:has_creator',
            ],
            'type' => 'rel',
          ],
        ],
      ],
      [
        'type' => 'taxonomy_term',
        'bundle' => 'tags',
        'types' => [
          'skos:Concept',
        ],
        'fieldMappings' => [
          'description' => [
            'predicates' => [
              'skos:definition',
            ],
          ],
          'name' => [
            'predicates' => [
              'rdfs:label',
              'skos:prefLabel',
            ],
          ],
          'parent' => [
            'predicates' => [
              'skos:broader',
            ],
            'type' => 'rel',
          ],
          'vid' => [
            'predicates' => [
              'skos:inScheme',
            ],
            'type' => 'rel',
          ],
        ],
      ],
      [
        'type' => 'user',
        'bundle' => 'user',
        'types' => [
          'sioc:UserAccount',
        ],
        'fieldMappings' => [
          'homepage' => [
            'predicates' => [
              'foaf:page',
            ],
            'type' => 'rel',
          ],
          'name' => [
            'predicates' => [
              'foaf:name',
            ],
          ],
        ],
      ],
    ];

    return $tests;
  }

}
