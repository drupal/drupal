<?php

/**
 * @file
 * Contains \Drupal\search\Plugin\SearchIndexingInterface.
 */

namespace Drupal\search\Plugin;

/**
 * Defines an optional interface for SearchPlugin objects using the index.
 *
 * Plugins implementing this interface will have these methods invoked during
 * search_cron() and via the search module administration form. Plugins not
 * implementing this interface are assumed to use alternate mechanisms for
 * indexing the data used to provide search results.
 *
 * Multiple search pages can be created for each search plugin, so you will need
 * to choose whether these search pages should share an index (in which case
 * they must not use any search page-specific configuration while indexing) or
 * they will have separate indexes (which will use additional server resources).
 */
interface SearchIndexingInterface {

  /**
   * Updates the search index for this plugin.
   *
   * This method is called every cron run if the plugin has been set as
   * an active search module on the Search settings page
   * (admin/config/search/pages). It allows your module to add items to the
   * built-in search index using search_index(), or to add them to your module's
   * own indexing mechanism.
   *
   * When implementing this method, your module should index content items that
   * were modified or added since the last run. PHP has a time limit
   * for cron, though, so it is advisable to limit how many items you index
   * per run using config('search.settings')->get('index.cron_limit'). Also,
   * since the cron run could time out and abort in the middle of your run, you
   * should update any needed internal bookkeeping on when items have last
   * been indexed as you go rather than waiting to the end of indexing.
   */
  public function updateIndex();

  /**
   * Takes action when the search index is going to be rebuilt.
   *
   * Modules that use updateIndex() should update their indexing bookkeeping so
   * that it starts from scratch the next time updateIndex() is called.
   */
  public function resetIndex();

  /**
   * Reports the status of indexing.
   *
   * The core search module only invokes this method on active module plugins.
   * Implementing modules do not need to check whether they are active when
   * calculating their return values.
   *
   * @return array
   *   An associative array with the key-value pairs:
   *   - remaining: The number of items left to index.
   *   - total: The total number of items to index.
   */
  public function indexStatus();

}
