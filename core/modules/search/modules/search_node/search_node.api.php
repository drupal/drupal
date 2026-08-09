<?php

/**
 * @file
 * Hooks specific to the Search Node module.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\node\NodeInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Act on a node being displayed as a search result.
 *
 * This hook is invoked from the node search plugin during search execution,
 * after loading and rendering the node.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node being displayed in a search result.
 *
 * @return array
 *   Extra information to be displayed with search result. This information
 *   should be presented as an associative array. It will be concatenated with
 *   the post information (last updated, author) in the default search result
 *   theming.
 *
 * @see \Drupal\search\Hook\SearchThemeHooks::preprocessSearchResult()
 * @see search-result.html.twig
 *
 * @ingroup entity_crud
 */
function hook_node_search_result(NodeInterface $node): array {
  $rating = \Drupal::database()->query('SELECT SUM([points]) FROM {my_rating} WHERE [nid] = :nid', ['nid' => $node->id()])->fetchField();
  return ['rating' => \Drupal::translation()->formatPlural($rating, '1 point', '@count points')];
}

/**
 * Act on a node being indexed for searching.
 *
 * This hook is invoked during search indexing, after loading, and after the
 * result of rendering is added as $node->rendered to the node object.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node being indexed.
 *
 * @return string|\Stringable
 *   Additional node information to be indexed.
 *
 * @ingroup entity_crud
 */
function hook_node_update_index(NodeInterface $node): string|\Stringable {
  $text = '';
  $ratings = \Drupal::database()->query('SELECT [title], [description] FROM {my_ratings} WHERE [nid] = :nid', [':nid' => $node->id()]);
  foreach ($ratings as $rating) {
    $text .= '<h2>' . Html::escape($rating->title) . '</h2>' . Xss::filter($rating->description);
  }
  return $text;
}

/**
 * Provide additional methods of scoring for core search results for nodes.
 *
 * A node's search score is used to rank it among other nodes matched by the
 * search, with the highest-ranked nodes appearing first in the search listing.
 *
 * For example, a module allowing users to vote on content could expose an
 * option to allow search results' rankings to be influenced by the average
 * voting score of a node.
 *
 * All scoring mechanisms are provided as options to site administrators, and
 * may be tweaked based on individual sites or disabled altogether if they do
 * not make sense. Individual scoring mechanisms, if enabled, are assigned a
 * weight from 1 to 10. The weight represents the factor of magnification of
 * the ranking mechanism, with higher-weighted ranking mechanisms having more
 * influence. In order for the weight system to work, each scoring mechanism
 * must return a value between 0 and 1 for every node. That value is then
 * multiplied by the administrator-assigned weight for the ranking mechanism,
 * and then the weighted scores from all ranking mechanisms are added, which
 * brings about the same result as a weighted average.
 *
 * @return array
 *   An associative array of ranking data. The keys should be strings,
 *   corresponding to the internal name of the ranking mechanism, such as
 *   'recent', or 'comments'. The values should be arrays themselves, with the
 *   following keys available:
 *   - title: (required) The human readable name of the ranking mechanism.
 *   - join: (optional) An array with information to join any additional
 *     necessary table. This is not necessary if the table required is already
 *     joined to by the base query, such as for the {node} table. Other tables
 *     should use the full table name as an alias to avoid naming collisions.
 *   - score: (required) The part of a query string to calculate the score for
 *     the ranking mechanism based on values in the database. This does not need
 *     to be wrapped in parentheses, as it will be done automatically; it also
 *     does not need to take the weighted system into account, as it will be
 *     done automatically. It does, however, need to calculate a decimal between
 *     0 and 1; be careful not to cast the entire score to an integer by
 *     inadvertently introducing a variable argument.
 *   - arguments: (optional) If any arguments are required for the score, they
 *     can be specified in an array here.
 *
 * @ingroup entity_crud
 */
function hook_node_search_ranking(): array {
  $data = [];
  // If voting is disabled, we can avoid returning the array, no hard feelings.
  if (\Drupal::config('vote.settings')->get('node_enabled')) {
    $data += [
      'vote_average' => [
        'title' => t('Average vote'),
        // Note that we use i.sid, the search index's search item id, rather
        // than n.nid.
        'join' => [
          'type' => 'LEFT',
          'table' => 'vote_node_data',
          'alias' => 'vote_node_data',
          'on' => 'vote_node_data.nid = i.sid',
        ],
        // The highest possible score should be 1, and the lowest possible
        // score, always 0, should be 0.
        'score' => 'vote_node_data.average / CAST(%f AS DECIMAL)',
        // Pass in the highest possible voting score as a decimal argument.
        'arguments' => [\Drupal::config('vote.settings')->get('score_max')],
      ],
    ];
  }
  return $data;
}

/**
 * @} End of "addtogroup hooks".
 */
