<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Controller\TermAutocompleteController.
 */

namespace Drupal\taxonomy\Controller;

use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\String;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns autocomplete responses for taxonomy terms.
 */
class TermAutocompleteController implements ContainerInjectionInterface {

  /**
   * Taxonomy term entity query interface.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $termEntityQuery;

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new \Drupal\taxonomy\Controller\TermAutocompleteController object.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $term_entity_query
   *   The entity query service.
   * @param \Drupal\Core\Entity\EntityManagerInterface
   *   The entity manager.
   */
  public function __construct(QueryInterface $term_entity_query, EntityManagerInterface $entity_manager) {
    $this->termEntityQuery = $term_entity_query;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')->get('taxonomy_term'),
      $container->get('entity.manager')
    );
  }

  /**
   * Retrieves suggestions for taxonomy term autocompletion.
   *
   * This function outputs term name suggestions in response to Ajax requests
   * made by the taxonomy autocomplete widget for taxonomy term reference
   * fields. The output is a JSON object of plain-text term suggestions, keyed
   * by the user-entered value with the completed term name appended.
   * Term names containing commas are wrapped in quotes.
   *
   * For example, suppose the user has entered the string 'red fish, blue' in
   * the field, and there are two taxonomy terms, 'blue fish' and 'blue moon'.
   * The JSON output would have the following structure:
   * @code
   *   {
   *     "red fish, blue fish": "blue fish",
   *     "red fish, blue moon": "blue moon",
   *   };
   * @endcode
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $entity_type
   *   The entity_type.
   * @param string $field_name
   *   The name of the term reference field.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
   *   When valid field name is specified, a JSON response containing the
   *   autocomplete suggestions for taxonomy terms. Otherwise a normal response
   *   containing an error message.
   */
  public function autocomplete(Request $request, $entity_type, $field_name) {
    // A comma-separated list of term names entered in the autocomplete form
    // element. Only the last term is used for autocompletion.
    $tags_typed = $request->query->get('q');

    // Make sure the field exists and is a taxonomy field.
    $field_storage_definitions = $this->entityManager->getFieldStorageDefinitions($entity_type);

    if (!isset($field_storage_definitions[$field_name]) || $field_storage_definitions[$field_name]->getType() !== 'taxonomy_term_reference') {
      // Error string. The JavaScript handler will realize this is not JSON and
      // will display it as debugging information.
      return new Response(t('Taxonomy field @field_name not found.', array('@field_name' => $field_name)), 403);
    }
    $field = $field_storage_definitions[$field_name];

    // The user enters a comma-separated list of tags. We only autocomplete the
    // last tag.
    $tags_typed = Tags::explode($tags_typed);
    $tag_last = Unicode::strtolower(array_pop($tags_typed));

    $matches = array();
    if ($tag_last != '') {

      // Part of the criteria for the query come from the field's own settings.
      $vids = array();
      foreach ($field->getSetting('allowed_values') as $tree) {
        $vids[] = $tree['vocabulary'];
      }

      $matches = $this->getMatchingTerms($tags_typed, $vids, $tag_last);
    }

    return new JsonResponse($matches);
  }

  /**
   * Retrieves suggestions for taxonomy term autocompletion by vocabulary ID.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\taxonomy\VocabularyInterface $taxonomy_vocabulary
   *   The vocabulary to filter by.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for taxonomy
   *   terms.
   */
  public function autocompletePerVid(Request $request, VocabularyInterface $taxonomy_vocabulary) {
    // A comma-separated list of term names entered in the autocomplete form
    // element. Only the last term is used for autocompletion.
    $tags_typed = $request->query->get('q');
    $tags_typed = Tags::explode($tags_typed);
    $tag_last = Unicode::strtolower(array_pop($tags_typed));

    $matches = array();
    if ($tag_last != '') {
      $vids = array($taxonomy_vocabulary->id());
      $matches = $this->getMatchingTerms($tags_typed, $vids, $tag_last);
    }
    return new JsonResponse($matches);
  }

  /**
   * Gets terms which matches some typed terms.
   *
   * @param string $tags_typed
   *   The full typed tags string.
   * @param array $vids
   *   An array of vocabulary IDs which
   * @param $tag_last
   *   The lasted typed tag.
   *
   * @return array
   *   Returns an array of matching terms.
   */
  protected function getMatchingTerms($tags_typed, array $vids, $tag_last) {
    $matches = array();
    $this->termEntityQuery->addTag('term_access');

    // Do not select already entered terms.
    if (!empty($tags_typed)) {
      $this->termEntityQuery->condition('name', $tags_typed, 'NOT IN');
    }
    // Select rows that match by term name.
    $tids = $this->termEntityQuery
      ->condition('vid', $vids, 'IN')
      ->condition('name', $tag_last, 'CONTAINS')
      ->range(0, 10)
      ->execute();

    $prefix = count($tags_typed) ? Tags::implode($tags_typed) . ', ' : '';
    if (!empty($tids)) {
      $terms = $this->entityManager->getStorage('taxonomy_term')->loadMultiple(array_keys($tids));
      foreach ($terms as $term) {
        // Term names containing commas or quotes must be wrapped in quotes.
        $name = Tags::encode($term->getName());
        $matches[] = array('value' => $prefix . $name, 'label' => String::checkPlain($term->getName()));
      }
      return $matches;
    }
    return $matches;
  }

}
