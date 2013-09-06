<?php

/**
 * @file
 * Hooks provided by the Search module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Preprocess text for search.
 *
 * This hook is called to preprocess both the text added to the search index
 * and the keywords users have submitted for searching.
 *
 * Possible uses:
 * - Adding spaces between words of Chinese or Japanese text.
 * - Stemming words down to their root words to allow matches between, for
 *   instance, walk, walked, walking, and walks in searching.
 * - Expanding abbreviations and acronymns that occur in text.
 *
 * @param $text
 *   The text to preprocess. This is a single piece of plain text extracted
 *   from between two HTML tags or from the search query. It will not contain
 *   any HTML entities or HTML tags.
 * @param $langcode
 *   The language code of the entity that has been found.
 *
 * @return
 *   The text after preprocessing. Note that if your module decides not to
 *   alter the text, it should return the original text. Also, after
 *   preprocessing, words in the text should be separated by a space.
 *
 * @ingroup search
 */
function hook_search_preprocess($text, $langcode = NULL) {
  // If the langcode is set to 'en' then add variations of the word "testing"
  // which can also be found during English language searches.
  if (isset($langcode) && $langcode == 'en') {
    // Add the alternate verb forms for the word "testing".
    if ($text == 'we are testing') {
      $text .= ' test tested';
    }
  }

  return $text;
}
