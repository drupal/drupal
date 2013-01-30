<?php

/**
 * @file
 * Contains JsonldRdfSchemaNormalizer.
 */

namespace Drupal\jsonld;

use Drupal\jsonld\JsonldNormalizerBase;
use Drupal\rdf\RdfConstants;

/**
 * Converts the Drupal entity object structure to JSONLD array structure.
 */
class JsonldRdfSchemaNormalizer extends JsonldNormalizerBase {

  /**
     * The interface or class that this Normalizer supports.
     *
     * @var string
     */
  protected static $supportedInterfaceOrClass = 'Drupal\rdf\SiteSchema\SchemaTermBase';

  /**
    * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
    */
  public function normalize($data, $format = NULL, array $context = array()) {
    $normalized = array();
    $graph = $data->getGraph();

    foreach ($graph as $term_uri => $properties) {
      // JSON-LD uses the @type keyword as a stand-in for rdf:type. Replace any
      // use of rdf:type and move the type to the front of the property array.
      if (isset($properties[RdfConstants::RDF_TYPE])) {
        $properties = array(
          '@type' => $properties[RdfConstants::RDF_TYPE],
        ) + $properties;
      }
      unset($properties[RdfConstants::RDF_TYPE]);

      // Add the @id keyword to the front of the array.
      $normalized[] = array(
        '@id' => $term_uri,
      ) + $properties;
    }

    return $normalized;
  }

}
