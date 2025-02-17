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
 * and the keywords users have submitted for searching. The same processing
 * needs to be applied to both so that searches will find matches.
 *
 * Possible uses:
 * - Adding spaces between words of Chinese or Japanese text.
 * - Stemming words down to their root words to allow matches between, for
 *   instance, walk, walked, walking, and walks in searching.
 * - Expanding abbreviations and acronyms that occur in text.
 *
 * @param string $text
 *   The text to preprocess. This is a single piece of plain text extracted
 *   from between two HTML tags or from the search query. It will not contain
 *   any HTML entities or HTML tags.
 * @param string|null $langcode
 *   The language code for the language the text is in, if known. When this hook
 *   is invoked during search indexing, the language will most likely be known
 *   and passed in. This is left up to the search plugin;
 *   \Drupal\node\Plugin\Search\NodeSearch does pass in the node
 *   language. However, when this hook is invoked during searching, in order to
 *   let a module apply the same preprocessing to the search keywords and
 *   indexed text so they will match, $langcode will be NULL. A hook
 *   implementation can call the getCurrentLanguage() method on the
 *   'language_manager' service to determine the current language and act
 *   accordingly.
 *
 * @return string
 *   The text after preprocessing. Note that if your module decides not to
 *   alter the text, it should return the original text. Also, after
 *   preprocessing, words in the text should be separated by a space.
 *
 * @ingroup search
 */
function hook_search_preprocess($text, $langcode = NULL): string {
  // If the language is not set, get it from the language manager.
  if (!isset($langcode)) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
  }

  // If the langcode is set to 'en' then add variations of the word "testing"
  // which can also be found during English language searches.
  if ($langcode == 'en') {
    // Add the alternate verb forms for the word "testing".
    if ($text == 'we are testing') {
      $text .= ' test tested';
    }
  }

  return $text;
}

/**
 * Alter search plugin definitions.
 *
 * @param array $definitions
 *   The array of search plugin definitions, keyed by plugin ID.
 *
 * @see \Drupal\search\Annotation\SearchPlugin
 * @see \Drupal\search\SearchPluginManager
 */
function hook_search_plugin_alter(array &$definitions) {
  if (isset($definitions['node_search'])) {
    $definitions['node_search']['title'] = t('Nodes');
  }
}

/**
 * @} End of "addtogroup hooks".
 */
