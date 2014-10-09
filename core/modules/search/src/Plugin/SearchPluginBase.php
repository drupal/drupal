<?php

/**
 * @file
 * Contains \Drupal\search\Plugin\SearchPluginBase
 */

namespace Drupal\search\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

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

    $built = array();
    foreach ($results as $result) {
      $built[] = array(
        '#theme' => 'search_result',
        '#result' => $result,
        '#plugin_id' => $this->getPluginId(),
      );
    }

    return $built;
  }

  /**
   * {@inheritdoc}
   */
  public function searchFormAlter(array &$form, FormStateInterface $form_state) {
    // Empty default implementation.
  }

  /**
   * {@inheritdoc}
   */
  public function suggestedTitle() {
    // If the user entered a search string, truncate it and append it to the
    // title.
    if (!empty($this->keywords)) {
      return $this->t('Search for @keywords', array('@keywords' => Unicode::truncate($this->keywords, 60, TRUE, TRUE)));
    }
    // Use the default 'Search' title.
    return $this->t('Search');
  }

  /*
   * {@inheritdoc}
   */
  public function buildSearchUrlQuery(FormStateInterface $form_state) {
    // Grab the keywords entered in the form and put them as 'keys' in the GET.
    $keys = trim($form_state->getValue('keys'));
    $query = array('keys' => $keys);

    return $query;
  }
}
