<?php

/**
 * @file
 * Builds placeholder replacement tokens for taxonomy terms and vocabularies.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Implements hook_token_info().
 */
function taxonomy_token_info() {
  $types['term'] = [
    'name' => t("Taxonomy terms"),
    'description' => t("Tokens related to taxonomy terms."),
    'needs-data' => 'term',
  ];
  $types['vocabulary'] = [
    'name' => t("Vocabularies"),
    'description' => t("Tokens related to taxonomy vocabularies."),
    'needs-data' => 'vocabulary',
  ];

  // Taxonomy term related variables.
  $term['tid'] = [
    'name' => t("Term ID"),
    'description' => t("The unique ID of the taxonomy term."),
  ];
  $term['name'] = [
    'name' => t("Name"),
    'description' => t("The name of the taxonomy term."),
  ];
  $term['description'] = [
    'name' => t("Description"),
    'description' => t("The optional description of the taxonomy term."),
  ];
  $term['node-count'] = [
    'name' => t("Node count"),
    'description' => t("The number of nodes tagged with the taxonomy term."),
  ];
  $term['url'] = [
    'name' => t("URL"),
    'description' => t("The URL of the taxonomy term."),
  ];

  // Taxonomy vocabulary related variables.
  $vocabulary['vid'] = [
    'name' => t("Vocabulary ID"),
    'description' => t("The unique ID of the taxonomy vocabulary."),
  ];
  $vocabulary['name'] = [
    'name' => t("Name"),
    'description' => t("The name of the taxonomy vocabulary."),
  ];
  $vocabulary['description'] = [
    'name' => t("Description"),
    'description' => t("The optional description of the taxonomy vocabulary."),
  ];
  $vocabulary['node-count'] = [
    'name' => t("Node count"),
    'description' => t("The number of nodes tagged with terms belonging to the taxonomy vocabulary."),
  ];
  $vocabulary['term-count'] = [
    'name' => t("Term count"),
    'description' => t("The number of terms belonging to the taxonomy vocabulary."),
  ];

  // Chained tokens for taxonomies
  $term['vocabulary'] = [
    'name' => t("Vocabulary"),
    'description' => t("The vocabulary the taxonomy term belongs to."),
    'type' => 'vocabulary',
  ];
  $term['parent'] = [
    'name' => t("Parent term"),
    'description' => t("The parent term of the taxonomy term, if one exists."),
    'type' => 'term',
  ];

  return [
    'types' => $types,
    'tokens' => [
      'term' => $term,
      'vocabulary' => $vocabulary,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function taxonomy_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = [];
  $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  if ($type == 'term' && !empty($data['term'])) {
    $term = $data['term'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'tid':
          $replacements[$original] = $term->id();
          break;

        case 'name':
          $replacements[$original] = $term->getName();
          break;

        case 'description':
          // "processed" returns a \Drupal\Component\Render\MarkupInterface via
          // check_markup().
          $replacements[$original] = $term->description->processed;
          break;

        case 'url':
          $replacements[$original] = $term->toUrl('canonical', ['absolute' => TRUE])->toString();
          break;

        case 'node-count':
          $query = \Drupal::database()->select('taxonomy_index');
          $query->condition('tid', $term->id());
          $query->addTag('term_node_count');
          $count = $query->countQuery()->execute()->fetchField();
          $replacements[$original] = $count;
          break;

        case 'vocabulary':
          $vocabulary = Vocabulary::load($term->bundle());
          $bubbleable_metadata->addCacheableDependency($vocabulary);
          $replacements[$original] = $vocabulary->label();
          break;

        case 'parent':
          if ($parents = $taxonomy_storage->loadParents($term->id())) {
            $parent = array_pop($parents);
            $bubbleable_metadata->addCacheableDependency($parent);
            $replacements[$original] = $parent->getName();
          }
          break;
      }
    }

    if ($vocabulary_tokens = $token_service->findWithPrefix($tokens, 'vocabulary')) {
      $vocabulary = Vocabulary::load($term->bundle());
      $replacements += $token_service->generate('vocabulary', $vocabulary_tokens, ['vocabulary' => $vocabulary], $options, $bubbleable_metadata);
    }

    if (($vocabulary_tokens = $token_service->findWithPrefix($tokens, 'parent')) && $parents = $taxonomy_storage->loadParents($term->id())) {
      $parent = array_pop($parents);
      $replacements += $token_service->generate('term', $vocabulary_tokens, ['term' => $parent], $options, $bubbleable_metadata);
    }
  }

  elseif ($type == 'vocabulary' && !empty($data['vocabulary'])) {
    $vocabulary = $data['vocabulary'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'vid':
          $replacements[$original] = $vocabulary->id();
          break;

        case 'name':
          $replacements[$original] = $vocabulary->label();
          break;

        case 'description':
          $build = ['#markup' => $vocabulary->getDescription()];
          // @todo Fix in https://www.drupal.org/node/2577827
          $replacements[$original] = \Drupal::service('renderer')->renderPlain($build);
          break;

        case 'term-count':
          $replacements[$original] = \Drupal::entityQuery('taxonomy_term')
            ->accessCheck(TRUE)
            ->condition('vid', $vocabulary->id())
            ->addTag('vocabulary_term_count')
            ->count()
            ->execute();
          break;

        case 'node-count':
          $replacements[$original] = $taxonomy_storage->nodeCount($vocabulary->id());
          break;
      }
    }
  }

  return $replacements;
}
