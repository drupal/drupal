<?php

namespace Drupal\search\Plugin;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base class for plugins wishing to support search.
 */
abstract class SearchPluginBase extends PluginBase implements ContainerFactoryPluginInterface, SearchInterface, RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

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
  public function getType() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildResults() {
    $results = $this->execute();

    $built = [];
    foreach ($results as $result) {
      $built[] = [
        '#theme' => 'search_result',
        '#result' => $result,
        '#plugin_id' => $this->getPluginId(),
      ];
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
      return $this->t('Search for @keywords', ['@keywords' => Unicode::truncate($this->keywords, 60, TRUE, TRUE)]);
    }
    // Use the default 'Search' title.
    return $this->t('Search');
  }

  /**
   * {@inheritdoc}
   */
  public function buildSearchUrlQuery(FormStateInterface $form_state) {
    // Grab the keywords entered in the form and put them as 'keys' in the GET.
    $keys = trim($form_state->getValue('keys'));
    $query = ['keys' => $keys];

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    // This default search help is appropriate for plugins like NodeSearch
    // that use the SearchQuery class.
    $help = [
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Search looks for exact, case-insensitive keywords; keywords shorter than a minimum length are ignored.'),
          $this->t('Use upper-case OR to get more results. Example: cat OR dog (content contains either "cat" or "dog").'),
          $this->t('You can use upper-case AND to require all words, but this is the same as the default behavior. Example: cat AND dog (same as cat dog, content must contain both "cat" and "dog").'),
          $this->t('Use quotes to search for a phrase. Example: "the cat eats mice".'),
          $this->t('You can precede keywords by - to exclude them; you must still have at least one "positive" keyword. Example: cat -dog (content must contain cat and cannot contain dog).'),
        ],
      ],
    ];

    return $help;
  }

}
