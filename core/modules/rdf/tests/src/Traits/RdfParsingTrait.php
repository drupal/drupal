<?php

namespace Drupal\Tests\rdf\Traits;

use Drupal\Core\Url;

/**
 * Override \EasyRdf_ParsedUri for PHP 7.4 compatibility.
 *
 * @todo https://www.drupal.org/project/drupal/issues/3110972 Remove this work
 *   around.
 */
class_alias('\Drupal\Tests\rdf\Traits\EasyRdf_ParsedUri', '\EasyRdf_ParsedUri');

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
   */
  protected function hasRdfProperty($html, $base_uri, $resource, $property, array $value) {
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
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
   */
  protected function hasRdfChildProperty($html, $base_uri, $resource, $parent_property, string $child_property, array $value) {
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
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
   */
  protected function getElementByRdfTypeCount(Url $url, $base_uri, $type) {
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
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
   */
  protected function getElementRdfType(Url $url, $base_uri, $resource_uri) {
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
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
   */
  protected function rdfElementIsBlankNode($html, $base_uri, $resource_uri, $property) {
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
    $parser->parse($graph, $html, 'rdfa', $base_uri);
    return $graph->get($resource_uri, $property)->isBnode();
  }

}
