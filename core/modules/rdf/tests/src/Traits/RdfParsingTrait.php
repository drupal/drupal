<?php

namespace Drupal\Tests\rdf\Traits;

use Drupal\Core\Url;
use EasyRdf\Graph;
use EasyRdf\Parser\Rdfa;

/**
 * Defines a trait for parsing RDF properties from HTML.
 */
trait RdfParsingTrait {

  /**
   * Checks if a html document contains a resource with a given property value.
   *
   * @param string $html
   *   The HTML to parse.
   * @param string $base_uri
   *   The base URI for the html being parsed.
   * @param string $resource
   *   The URI of the resource which should have the given property.
   * @param string $property
   *   The property being tested.
   * @param array $value
   *   The expected value. This should include the following keys:
   *   - type: one of literal, uri and bnode
   *   - value: the expected value
   *   - datatype: the expected datatype in URI format - e.g.
   *     - http://www.w3.org/2001/XMLSchema#integer
   *     - http://www.w3.org/2001/XMLSchema#dateTime
   *   - lang: language code of the property.
   *
   * @return bool
   *   TRUE if the property exists with the given value.
   *
   * @throws \EasyRdf\Exception
   */
  protected function hasRdfProperty($html, $base_uri, $resource, $property, array $value) {
    $parser = $this->getInstanceParser();
    $graph = $this->getInstanceGraph();
    $parser->parse($graph, $html, 'rdfa', $base_uri);

    return $graph->hasProperty($resource, $property, $value);
  }

  /**
   * Checks if a html document contains a resource with a given property value.
   *
   * @param string $html
   *   The HTML to parse.
   * @param string $base_uri
   *   The base URI for the html being parsed.
   * @param string $resource
   *   The URI of the resource which should have the given property.
   * @param string $parent_property
   *   The parent property being tested.
   * @param string $child_property
   *   The child property being tested.
   * @param array $value
   *   The expected value. This should include the following keys:
   *   - type: one of literal, uri and bnode
   *   - value: the expected value
   *   - datatype: the expected datatype in URI format - e.g.
   *     - http://www.w3.org/2001/XMLSchema#integer
   *     - http://www.w3.org/2001/XMLSchema#dateTime
   *   - lang: language code of the property.
   *
   * @return bool
   *   TRUE if the property exists with the given value.
   *
   * @throws \EasyRdf\Exception
   */
  protected function hasRdfChildProperty($html, $base_uri, $resource, $parent_property, string $child_property, array $value) {
    $parser = $this->getInstanceParser();
    $graph = $this->getInstanceGraph();
    $parser->parse($graph, $html, 'rdfa', $base_uri);
    $node = $graph->get($resource, $parent_property);
    return $graph->hasProperty($node, $child_property, $value);
  }

  /**
   * Counts the number of resources of the provided type.
   *
   * @param \Drupal\Core\Url $url
   *   URL of the document.
   * @param string $base_uri
   *   The base URI for the html being parsed.
   * @param string $type
   *   Type of resource to count.
   *
   * @return int
   *   The number of resources of the provided type.
   *
   * @throws \EasyRdf\Exception
   */
  protected function getElementByRdfTypeCount(Url $url, $base_uri, $type) {
    $parser = $this->getInstanceParser();
    $graph = $this->getInstanceGraph();
    $parser->parse($graph, $this->drupalGet($url), 'rdfa', $base_uri);
    return count($graph->allOfType($type));
  }

  /**
   * Gets type of RDF Element.
   *
   * @param \Drupal\Core\Url $url
   *   URL of the document.
   * @param string $base_uri
   *   The base URI for the html being parsed.
   * @param string $resource_uri
   *   The URI of the resource from where to get element.
   *
   * @return string|null
   *   The type of resource or NULL if the resource has no type.
   *
   * @throws \EasyRdf\Exception
   */
  protected function getElementRdfType(Url $url, $base_uri, $resource_uri) {
    $parser = $this->getInstanceParser();
    $graph = $this->getInstanceGraph();
    $parser->parse($graph, $this->drupalGet($url), 'rdfa', $base_uri);
    return $graph->type($resource_uri);
  }

  /**
   * Checks if RDF Node property is blank.
   *
   * @param string $html
   *   The HTML to parse.
   * @param string $base_uri
   *   The base URI for the html being parsed.
   * @param string $resource_uri
   *   The URI of the resource which should have the given property.
   * @param string $property
   *   The property being tested.
   *
   * @return bool
   *   TRUE if the given property is blank.
   *
   * @throws \EasyRdf\Exception
   */
  protected function rdfElementIsBlankNode($html, $base_uri, $resource_uri, $property) {
    $parser = $this->getInstanceParser();
    $graph = $this->getInstanceGraph();
    $parser->parse($graph, $html, 'rdfa', $base_uri);
    return $graph->get($resource_uri, $property)->isBnode();
  }

  /**
   * Gets a new instance of EasyRdf\Parser\Rdfa or EasyRdf_Parser_Rdfa.
   *
   * @return \EasyRdf\Parser\Rdfa|\EasyRdf_Parser_Rdfa
   *   The instance.
   *
   * @todo Clean this up in drupal:10.0.0.
   * @see https://www.drupal.org/node/3176468
   */
  private function getInstanceParser() {
    if (class_exists('EasyRdf\Parser\Rdfa')) {
      return new Rdfa();
    }
    return new \EasyRdf_Parser_Rdfa();
  }

  /**
   * Gets a new instance of EasyRdf\Graph or EasyRdf_Graph.
   *
   * @return \EasyRdf\Graph|\EasyRdf_Graph
   *   The instance.
   *
   * @todo Clean this up in drupal:10.0.0.
   * @see https://www.drupal.org/node/3176468
   */
  private function getInstanceGraph() {
    if (class_exists('EasyRdf\Graph')) {
      return new Graph();
    }
    return new \EasyRdf_Graph();
  }

}
