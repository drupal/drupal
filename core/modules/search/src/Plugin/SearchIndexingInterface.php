<?php

/**
 * @file
 * Contains \Drupal\search\Plugin\SearchIndexingInterface.
 */

namespace Drupal\search\Plugin;

/**
 * Defines an optional interface for SearchPlugin objects using an index.
 *
 * Plugins implementing this interface will have these methods invoked during
 * search_cron() and via the search module administration form. Plugins not
 * implementing this interface are assumed to be using their own methods for
 * searching, not involving separate index tables.
 *
 * The user interface for managing search pages displays the indexing status for
 * search pages implementing this interface. It also allows users to configure
 * default settings for indexing, and refers to the "default search index". If
 * your search page plugin uses its own indexing mechanism instead of the
 * default search index, or overrides the default indexing settings, you should
 * make this clear on the settings page or other documentation for your plugin.
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
   * were modified or added since the last run. There is a time limit for cron,
   * so it is advisable to limit how many items you index per run using
   * config('search.settings')->get('index.cron_limit') or with your own
   * setting. And since the cron run could time out and abort in the middle of
   * your run, you should update any needed internal bookkeeping on when items
   * have last been indexed as you go rather than waiting to the end of
   * indexing.
   */
  public function updateIndex();

  /**
   * Clears the search index for this plugin.
   *
   * When a request is made to clear all items from the search index related to
   * this plugin, this method will be called. If this plugin uses the default
   * search index, this method can call search_index_clear($type) to remove
   * indexed items from the search database.
   *
   * @see search_index_clear()
   */
  public function indexClear();

  /**
   * Marks the search index for reindexing for this plugin.
   *
   * When a request is made to mark all items from the search index related to
   * this plugin for reindexing, this method will be called. If this plugin uses
   * the default search index, this method can call
   * search_mark_for_reindex($type) to mark the items in the search database for
   * reindexing.
   *
   * @see search_mark_for_reindex()
   */
  public function markForReindex();

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
