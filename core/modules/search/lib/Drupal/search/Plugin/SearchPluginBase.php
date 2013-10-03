<?php

/**
 * @file
 * Contains \Drupal\search\Plugin\SearchPluginBase
 */

namespace Drupal\search\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines a base class for plugins wishing to support search.
 */
abstract class SearchPluginBase extends PluginBase implements ContainerFactoryPluginInterface, SearchInterface {

  /**
   * The keywords to use in a search.
   *
   * @var string
   */
  protected $keywords;

  /**
   * Array of parameters from the query string from the request.
   *
   * @var array
   */
  protected $searchParameters;

  /**
   * Array of attributes - usually from the request object.
   *
   * @var array
   */
  protected $searchAttributes;

  /**
   * {@inheritdoc}
   */
  public function setSearch($keywords, array $parameters, array $attributes) {
    $this->keywords = (string) $keywords;
    $this->searchParameters = $parameters;
    $this->searchAttributes = $attributes;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeywords() {
    return $this->keywords;
  }

  /**
   * {@inheritdoc}
   */
  public function getParameters() {
    return $this->searchParameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes() {
    return $this->searchAttributes;
  }

  /**
   * {@inheritdoc}
   */
  public function isSearchExecutable() {
    // Default implementation suitable for plugins that only use keywords.
    return !empty($this->keywords);
  }

  /**
   * {@inheritdoc}
   */
  public function buildResults() {
    $results = $this->execute();
    return array(
      '#theme' => 'search_results',
      '#results' => $results,
      '#plugin_id' => $this->getPluginId(),
    );
  }

 /**
   * {@inheritdoc}
   */
  public function searchFormAlter(array &$form, array &$form_state) {
    // Empty default implementation.
  }

}
