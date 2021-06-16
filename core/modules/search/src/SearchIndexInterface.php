<?php

namespace Drupal\search;

/**
 * Provides search index management functions.
 *
 * @ingroup search
 */
interface SearchIndexInterface {

  /**
   * Updates the full-text search index for a particular item.
   *
   * @param string $type
   *   The plugin ID or other machine-readable type of this item,
   *   which should be less than 64 bytes.
   * @param int $sid
   *   An ID number identifying this particular item (e.g., node ID).
   * @param string $langcode
   *   Language code for the language of the text being indexed.
   * @param string $text
   *   The content of this item. Must be a piece of HTML or plain text.
   * @param bool $update_weights
   *   (optional) TRUE if word weights should be updated. FALSE otherwise;
   *   defaults to TRUE. If you pass in FALSE, then you need to have your
   *   calls to this method in a try/finally block, and at the end of your
   *   index run in the finally clause, you will need to call
   *   self::updateWordWeights(), passing in all of the returned words, to
   *   update the word weights.
   *
   * @return string[]
   *   The words to be updated.
   *
   * @throws \Drupal\search\Exception\SearchIndexException
   *   If there is an error indexing the text.
   */
  public function index($type, $sid, $langcode, $text, $update_weights = TRUE);

  /**
   * Clears either a part of, or the entire search index.
   *
   * This function is meant for use by search page plugins, or for building a
   * user interface that lets users clear all or parts of the search index.
   *
   * @param string|null $type
   *   (optional) The plugin ID or other machine-readable type for the items to
   *   remove from the search index. If omitted, $sid and $langcode are ignored
   *   and the entire search index is cleared.
   * @param int|array|null $sid
   *   (optional) The ID or array of IDs of the items to remove from the search
   *   index. If omitted, all items matching $type are cleared, and $langcode
   *   is ignored.
   * @param string|null $langcode
   *   (optional) Language code of the item to remove from the search index. If
   *   omitted, all items matching $sid and $type are cleared.
   *
   * @throws \Drupal\search\Exception\SearchIndexException
   *   If there is an error clearing the index.
   */
  public function clear($type = NULL, $sid = NULL, $langcode = NULL);

  /**
   * Changes the timestamp on indexed items to 'now' to force reindexing.
   *
   * This function is meant for use by search page plugins, or for building a
   * user interface that lets users mark all or parts of the search index for
   * reindexing.
   *
   * @param string $type
   *   (optional) The plugin ID or other machine-readable type of this item. If
   *   omitted, the entire search index is marked for reindexing, and $sid and
   *   $langcode are ignored.
   * @param int $sid
   *   (optional) An ID number identifying this particular item (e.g., node ID).
   *   If omitted, everything matching $type is marked, and $langcode is
   *   ignored.
   * @param string $langcode
   *   (optional) The language code to mark. If omitted, everything matching
   *   $type and $sid is marked.
   *
   * @throws \Drupal\search\Exception\SearchIndexException
   *   If there is an error marking the index for re-indexing.
   */
  public function markForReindex($type = NULL, $sid = NULL, $langcode = NULL);

  /**
   * Updates the {search_total} database table.
   *
   * @param array $words
   *   An array whose keys are words from self::index() whose total weights
   *   need to be updated.
   *
   * @throws \Drupal\search\Exception\SearchIndexException
   *   If there is an error updating the totals.
   */
  public function updateWordWeights(array $words);

}
