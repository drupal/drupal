<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\source\SourcePluginBase.
 */

namespace Drupal\migrate\Plugin\migrate\source;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Row;

/**
 * The base class for all source plugins.
 *
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate\Annotation\MigrateSource
 * @see \Drupal\migrate\Plugin\MigrateSourceInterface
 * @see plugin_api
 *
 * @ingroup migration
 */
abstract class SourcePluginBase extends PluginBase implements MigrateSourceInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\migrate\Entity\MigrationInterface
   */
  protected $migration;

  /**
   * The name and type of the highwater property in the source.
   *
   * @var array
   *
   * @see $originalHighwater
   */
  protected $highWaterProperty;

  /**
   * The current row from the query
   *
   * @var \Drupal\Migrate\Row
   */
  protected $currentRow;

  /**
   * The primary key of the current row
   *
   * @var array
   */
  protected $currentSourceIds;

  /**
   * The high water mark at the beginning of the import operation.
   *
   * If the source has a property for tracking changes (like Drupal ha
   * node.changed) then this is the highest value of those imported so far.
   *
   * @var int
   */
  protected $originalHighWater;

  /**
   * Whether this instance should cache the source count.
   *
   * @var bool
   */
  protected $cacheCounts = FALSE;

  /**
   * Key to use for caching counts.
   *
   * @var string
   */
  protected $cacheKey;

  /**
   * Whether this instance should not attempt to count the source.
   *
   * @var bool
   */
  protected $skipCount = FALSE;

  /**
   * If TRUE, we will maintain hashed source rows to determine whether incoming
   * data has changed.
   *
   * @var bool
   */
  protected $trackChanges = FALSE;

  /**
   * By default, next() will directly read the map row and add it to the data
   * row. A source plugin implementation may do this itself (in particular, the
   * SQL source can incorporate the map table into the query) - if so, it should
   * set this TRUE so we don't duplicate the effort.
   *
   * @var bool
   */
  protected $mapRowAdded = FALSE;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * @var \Drupal\migrate\Plugin\MigrateIdMapInterface
   */
  protected $idMap;

  /**
   * @var \Iterator
   */
  protected $iterator;

  /**
   * @TODO, find out how to remove this.
   * @see https://www.drupal.org/node/2443617
   *
   * @var MigrateExecutableInterface
   */
  public $migrateExecutable;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;

    // Set up some defaults based on the source configuration.
    $this->cacheCounts = !empty($configuration['cache_counts']);
    $this->skipCount = !empty($configuration['skip_count']);
    $this->cacheKey = !empty($configuration['cache_key']) ? !empty($configuration['cache_key']) : NULL;
    $this->trackChanges = !empty($configuration['track_changes']) ? $configuration['track_changes'] : FALSE;

    // Pull out the current highwater mark if we have a highwater property.
    if ($this->highWaterProperty = $this->migration->get('highWaterProperty')) {
      $this->originalHighWater = $this->migration->getHighWater();
    }

    // Don't allow the use of both highwater and track changes together.
    if ($this->highWaterProperty && $this->trackChanges) {
      throw new MigrateException('You should either use a highwater mark or track changes not both. They are both designed to solve the same problem');
    }
  }

  /**
   * Initialize the iterator with the source data.
   *
   * @return array
   *   An array of the data for this source.
   */
  protected abstract function initializeIterator();

  /**
   * Get the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  protected function getModuleHandler() {
    if (!isset($this->moduleHandler)) {
      $this->moduleHandler = \Drupal::moduleHandler();
    }
    return $this->moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $result = TRUE;
    try {
      $result_hook = $this->getModuleHandler()->invokeAll('migrate_prepare_row', array($row, $this, $this->migration));
      $result_named_hook = $this->getModuleHandler()->invokeAll('migrate_' . $this->migration->id() . '_prepare_row', array($row, $this, $this->migration));
      // We will skip if any hook returned FALSE.
      $skip = ($result_hook && in_array(FALSE, $result_hook)) || ($result_named_hook && in_array(FALSE, $result_named_hook));
      $save_to_map = TRUE;
    }
    catch (MigrateSkipRowException $e) {
      $skip = TRUE;
      $save_to_map = $e->getSaveToMap();
    }

    // We're explicitly skipping this row - keep track in the map table.
    if ($skip) {
      // Make sure we replace any previous messages for this item with any
      // new ones.
      $id_map = $this->migration->getIdMap();
      $id_map->delete($this->currentSourceIds, TRUE);
      $this->migrateExecutable->saveQueuedMessages();
      if ($save_to_map) {
        $id_map->saveIdMapping($row, array(), MigrateIdMapInterface::STATUS_IGNORED, $this->migrateExecutable->rollbackAction);
        $this->currentRow = NULL;
        $this->currentSourceIds = NULL;
      }
      $result = FALSE;
    }
    elseif ($this->trackChanges) {
      // When tracking changed data, We want to quietly skip (rather than
      // "ignore") rows with changes. The caller needs to make that decision,
      // so we need to provide them with the necessary information (before and
      // after hashes).
      $row->rehash();
    }
    return $result;
  }

  /**
   * Returns the iterator that will yield the row arrays to be processed.
   *
   * @return \Iterator
   */
  public function getIterator() {
    if (!isset($this->iterator)) {
      $this->iterator = $this->initializeIterator();
    }
    return $this->iterator;
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    return $this->currentRow;
  }

  /**
   * Get the iterator key.
   *
   * Implementation of Iterator::key - called when entering a loop iteration,
   * returning the key of the current row. It must be a scalar - we will
   * serialize to fulfill the requirement, but using getCurrentIds() is
   * preferable.
   */
  public function key() {
    return serialize($this->currentSourceIds);
  }

  /**
   * Whether the iterator is currently valid.
   *
   * Implementation of Iterator::valid() - called at the top of the loop,
   * returning TRUE to process the loop and FALSE to terminate it
   */
  public function valid() {
    return isset($this->currentRow);
  }

  /**
   * Rewind the iterator.
   *
   * Implementation of Iterator::rewind() - subclasses of MigrateSource should
   * implement performRewind() to do any class-specific setup for iterating
   * source records.
   */
  public function rewind() {
    $this->idMap = $this->migration->getIdMap();
    $this->getIterator()->rewind();
    $this->next();
  }

  /**
   * {@inheritdoc}
   *
   * The migration iterates over rows returned by the source plugin. This
   * method determines the next row which will be processed and imported into
   * the system.
   *
   * The method tracks the source and destination IDs using the ID map plugin.
   *
   * This also takes care about highwater support. Highwater allows to reimport
   * rows from a previous migration run, which got changed in the meantime.
   * This is done by specifying a highwater field, which is compared with the
   * last time, the migration got executed (originalHighWater).
   */
  public function next() {
    $this->currentSourceIds = NULL;
    $this->currentRow = NULL;

    // In order to find the next row we want to process, we ask the source
    // plugin for the next possible row.
    while (!isset($this->currentRow) && $this->getIterator()->valid()) {

      $row_data = $this->getIterator()->current() + $this->configuration;
      $this->getIterator()->next();
      $row = new Row($row_data, $this->migration->getSourcePlugin()->getIds(), $this->migration->get('destinationIds'));

      // Populate the source key for this row.
      $this->currentSourceIds = $row->getSourceIdValues();

      // Pick up the existing map row, if any, unless getNextRow() did it.
      if (!$this->mapRowAdded && ($id_map = $this->idMap->getRowBySource($this->currentSourceIds))) {
        $row->setIdMap($id_map);
      }

      // Preparing the row gives source plugins the chance to skip.
      if ($this->prepareRow($row) === FALSE) {
        continue;
      }

      // Check whether the row needs processing.
      // 1. This row has not been imported yet.
      // 2. Explicitly set to update.
      // 3. The row is newer than the current highwater mark.
      // 4. If no such property exists then try by checking the hash of the row.
      if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row) ) {
        $this->currentRow = $row->freezeSource();
      }
    }
  }

  /**
   * Check if the incoming data is newer than what we've previously imported.
   *
   * @param \Drupal\migrate\Row $row
   *   The row we're importing.
   *
   * @return bool
   *   TRUE if the highwater value in the row is greater than our current value.
   */
  protected function aboveHighwater(Row $row) {
    return $this->highWaterProperty && $row->getSourceProperty($this->highWaterProperty['name']) > $this->originalHighWater;
  }

  /**
   * Check if the incoming row has changed since our last import.
   *
   * @param \Drupal\migrate\Row $row
   *   The row we're importing.
   *
   * @return bool
   *   TRUE if the row has changed otherwise FALSE.
   */
  protected function rowChanged(Row $row) {
    return $this->trackChanges && $row->changed();
  }

  /**
   * Getter for currentSourceIds data member.
   */
  public function getCurrentIds() {
    return $this->currentSourceIds;
  }

  /**
   * Get the source count.
   *
   * Return a count of available source records, from the cache if appropriate.
   * Returns -1 if the source is not countable.
   *
   * @param bool $refresh
   *   Whether or not to refresh the count.
   *
   * @return int
   *   The count.
   */
  public function count($refresh = FALSE) {
    if ($this->skipCount) {
      return -1;
    }

    if (!isset($this->cacheKey)) {
      $this->cacheKey = hash('sha256', $this->getPluginId());
    }

    // If a refresh is requested, or we're not caching counts, ask the derived
    // class to get the count from the source.
    if ($refresh || !$this->cacheCounts) {
      $count = $this->getIterator()->count();
      $this->getCache()->set($this->cacheKey, $count);
    }
    else {
      // Caching is in play, first try to retrieve a cached count.
      $cache_object = $this->getCache()->get($this->cacheKey, 'cache');
      if (is_object($cache_object)) {
        // Success.
        $count = $cache_object->data;
      }
      else {
        // No cached count, ask the derived class to count 'em up, and cache
        // the result.
        $count = $this->getIterator()->count();
        $this->getCache()->set($this->cacheKey, $count);
      }
    }
    return $count;
  }

  /**
   * Get the cache object.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The cache object.
   */
  protected function getCache() {
    if (!isset($this->cache)) {
      $this->cache = \Drupal::cache('migrate');
    }
    return $this->cache;
  }

}
