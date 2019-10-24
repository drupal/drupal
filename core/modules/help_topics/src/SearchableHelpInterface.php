<?php

namespace Drupal\help_topics;

use Drupal\Core\Language\LanguageInterface;

/**
 * Provides an interface for a HelpSection plugin that also supports search.
 *
 * @see \Drupal\help\HelpSectionPluginInterface
 *
 * @internal
 *   Help Topics is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
interface SearchableHelpInterface {

  /**
   * Returns the IDs of topics that should be indexed for searching.
   *
   * @return string[]
   *   An array of topic IDs that should be searchable. IDs need to be
   *   unique within this HelpSection plugin.
   */
  public function listSearchableTopics();

  /**
   * Renders one topic for search indexing or search results.
   *
   * @param string $topic_id
   *   The ID of the topic to be indexed.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language to render the topic in.
   *
   * @return array
   *   An array of information about the topic, with elements:
   *   - title: The title of the topic in this language.
   *   - text: The text of the topic in this language.
   *   - url: The URL of the topic as a \Drupal\Core\Url object.
   *   - cacheable_metadata: (optional) An object to add as a cache dependency
   *     if this topic is shown in search results.
   */
  public function renderTopicForSearch($topic_id, LanguageInterface $language);

}
