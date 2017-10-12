<?php

namespace Drupal\migrate\Plugin\migrate\source;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Event\MigrateRollbackEvent;
use Drupal\migrate\Event\RollbackAwareInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Row;

/**
 * The base class for source plugins.
 *
 * Available configuration keys:
 * - cache_counts: (optional) If set, cache the source count.
 * - skip_count: (optional) If set, do not attempt to count the source.
 * - track_changes: (optional) If set, track changes to incoming data.
 * - high_water_property: (optional) It is an array of name & alias values
 *   (optional table alias). This high_water_property is typically a timestamp
 *   or serial id showing what was the last imported record. Only content with a
 *   higher value will be imported.
 *
 * The high_water_property and track_changes are mutually exclusive.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: some_source_plugin_name
 *   cache_counts: true
 *   track_changes: true
 * @endcode
 *
 * This example uses the plugin "some_source_plugin_name" and caches the count
 * of available source records to save calculating it every time count() is
 * called. Changes to incoming data are watched (because track_changes is true),
 * which can affect the result of prepareRow().
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: some_source_plugin_name
 *   skip_count: true
 *   high_water_property:
 *     name: changed
 *     alias: n
 * @endcode
 *
 * In this example, skip_count is true which means count() will not attempt to
 * count the available source records, but just always return -1 instead. The
 * high_water_property defines which field marks the last imported row of the
 * migration. This will get converted into a SQL condition that looks like
 * 'n.changed' or 'changed' if no alias.
 *
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate\Annotation\MigrateSource
 * @see \Drupal\migrate\Plugin\MigrateSourceInterface
 * @see plugin_api
 *
 * @ingroup migration
 */
abstract class SourcePluginBase extends PluginBase implements MigrateSourceInterface, RollbackAwareInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity migration object.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The current row from the query.
   *
   * @var \Drupal\Migrate\Row
   */
  protected $currentRow;

  /**
   * The primary key of the current row.
   *
   * @var array
   */
  protected $currentSourceIds;

  /**
   * Information on the property used as the high-water mark.
   *
   * Array of 'name' and (optional) db 'alias' properties used for high-water
   * mark.
   *
   * @var array
   */
  protected $highWaterProperty = [];

  /**
   * The key-value storage for the high-water value.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $highWaterStorage;

  /**
   * The high water mark at the beginning of the import operation.
   *
   * If the source has a property for tracking changes (like Drupal has
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
   * Flags whether to track changes to incoming data.
   *
   * If TRUE, we will maintain hashed source rows to determine whether incoming
   * data has changed.
   *
   * @var bool
   */
  protected $trackChanges = FALSE;

  /**
   * Flags whether source plugin will read the map row and add to data row.
   *
   * By default, next() will directly read the map row and add it to the data
   * row. A source plugin implementation may do this itself (in particular, the
   * SQL source can incorporate the map table into the query) - if so, it should
   * set this TRUE so we don't duplicate the effort.
   *
   * @var bool
   */
  protected $mapRowAdded = FALSE;

  /**
   * The backend cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The migration ID map.
   *
   * @var \Drupal\migrate\Plugin\MigrateIdMapInterface
   */
  protected $idMap;

  /**
   * The iterator to iterate over the source rows.
   *
   * @var \Iterator
   */
  protected $iterator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;

    // Set up some defaults based on the source configuration.
    foreach (['cacheCounts' => 'cache_counts', 'skipCount' => 'skip_count', 'trackChanges' => 'track_changes'] as $property => $config_key) {
      if (isset($configuration[$config_key])) {
        $this->$property = (bool) $configuration[$config_key];
      }
    }
    $this->cacheKey = !empty($configuration['cache_key']) ? $configuration['cache_key'] : NULL;
    $this->idMap = $this->migration->getIdMap();
    $this->highWaterProperty = !empty($configuration['high_water_property']) ? $configuration['high_water_property'] : FALSE;

    // Pull out the current highwater mark if we have a highwater property.
    if ($this->highWaterProperty) {
      $this->originalHighWater = $this->getHighWater();
    }

    // Don't allow the use of both highwater and track changes together.
    if ($this->highWaterProperty && $this->trackChanges) {
      throw new MigrateException('You should either use a highwater mark or track changes not both. They are both designed to solve the same problem');
    }
  }

  /**
   * Initializes the iterator with the source data.
   *
   * @return array
   *   An array of the data for this source.
   */
  abstract protected function initializeIterator();

  /**
   * Gets the module handler.
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
      $result_hook = $this->getModuleHandler()->invokeAll('migrate_prepare_row', [$row, $this, $this->migration]);
      $result_named_hook = $this->getModuleHandler()->invokeAll('migrate_' . $this->migration->id() . '_prepare_row', [$row, $this, $this->migration]);
      // We will skip if any hook returned FALSE.
      $skip = ($result_hook && in_array(FALSE, $result_hook)) || ($result_named_hook && in_array(FALSE, $result_named_hook));
      $save_to_map = TRUE;
    }
    catch (MigrateSkipRowException $e) {
      $skip = TRUE;
      $save_to_map = $e->getSaveToMap();
      if ($message = trim($e->getMessage())) {
        $this->idMap->saveMessage($row->getSourceIdValues(), $message, MigrationInterface::MESSAGE_INFORMATIONAL);
      }
    }

    // We're explicitly skipping this row - keep track in the map table.
    if ($skip) {
      // Make sure we replace any previous messages for this item with any
      // new ones.
      if ($save_to_map) {
        $this->idMap->saveIdMapping($row, [], MigrateIdMapInterface::STATUS_IGNORED);
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
   *   The iterator that will yield the row arrays to be processed.
   */
  protected function getIterator() {
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
   * Gets the iterator key.
   *
   * Implementation of \Iterator::key() - called when entering a loop iteration,
   * returning the key of the current row. It must be a scalar - we will
   * serialize to fulfill the requirement, but using getCurrentIds() is
   * preferable.
   */
  public function key() {
    return serialize($this->currentSourceIds);
  }

  /**
   * Checks whether the iterator is currently valid.
   *
   * Implementation of \Iterator::valid() - called at the top of the loop,
   * returning TRUE to process the loop and FALSE to terminate it.
   */
  public function valid() {
    return isset($this->currentRow);
  }

  /**
   * Rewinds the iterator.
   *
   * Implementation of \Iterator::rewind() - subclasses of SourcePluginBase
   * should implement initializeIterator() to do any class-specific setup for
   * iterating source records.
   */
  public function rewind() {
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
      $this->fetchNextRow();
      $row = new Row($row_data, $this->migration->getSourcePlugin()->getIds(), $this->migration->getDestinationIds());

      // Populate the source key for this row.
      $this->currentSourceIds = $row->getSourceIdValues();

      // Pick up the existing map row, if any, unless fetchNextRow() did it.
      if (!$this->mapRowAdded && ($id_map = $this->idMap->getRowBySource($this->currentSourceIds))) {
        $row->setIdMap($id_map);
      }

      // Clear any previous messages for this row before potentially adding
      // new ones.
      if (!empty($this->currentSourceIds)) {
        $this->idMap->delete($this->currentSourceIds, TRUE);
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
      if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
        $this->currentRow = $row->freezeSource();
      }

      if ($this->getHighWaterProperty()) {
        $this->saveHighWater($row->getSourceProperty($this->highWaterProperty['name']));
      }
    }
  }

  /**
   * Position the iterator to the following row.
   */
  protected function fetchNextRow() {
    $this->getIterator()->next();
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
    return $this->getHighWaterProperty() && $row->getSourceProperty($this->highWaterProperty['name']) > $this->originalHighWater;
  }

  /**
   * Checks if the incoming row has changed since our last import.
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
   * Gets the currentSourceIds data member.
   */
  public function getCurrentIds() {
    return $this->currentSourceIds;
  }

  /**
   * Gets the source count.
   *
   * Return a count of available source records, from the cache if appropriate.
   * Returns -1 if the source is not countable.
   *
   * @param bool $refresh
   *   (optional) Whether or not to refresh the count. Defaults to FALSE.
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
      $count = $this->doCount();
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
        $count = $this->doCount();
        $this->getCache()->set($this->cacheKey, $count);
      }
    }
    return $count;
  }

  /**
   * Gets the cache object.
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

  /**
   * Gets the source count checking if the source is countable or using the
   * iterator_count function.
   *
   * @return int
   */
  protected function doCount() {
    $iterator = $this->getIterator();
    return $iterator instanceof \Countable ? $iterator->count() : iterator_count($this->initializeIterator());
  }

  /**
   * Get the high water storage object.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   *   The storage object.
   */
  protected function getHighWaterStorage() {
    if (!isset($this->highWaterStorage)) {
      $this->highWaterStorage = \Drupal::keyValue('migrate:high_water');
    }
    return $this->highWaterStorage;
  }

  /**
   * The current value of the high water mark.
   *
   * The high water mark defines a timestamp stating the time the import was last
   * run. If the mark is set, only content with a higher timestamp will be
   * imported.
   *
   * @return int|null
   *   A Unix timestamp representing the high water mark, or NULL if no high
   *   water mark has been stored.
   */
  protected function getHighWater() {
    return $this->getHighWaterStorage()->get($this->migration->id());
  }

  /**
   * Save the new high water mark.
   *
   * @param int $high_water
   *   The high water timestamp.
   */
  protected function saveHighWater($high_water) {
    $this->getHighWaterStorage()->set($this->migration->id(), $high_water);
  }

  /**
   * Get information on the property used as the high watermark.
   *
   * Array of 'name' & (optional) db 'alias' properties used for high watermark.
   *
   * @see \Drupal\migrate\Plugin\migrate\source\SqlBase::initializeIterator()
   *
   * @return array
   *   The property used as the high watermark.
   */
  protected function getHighWaterProperty() {
    return $this->highWaterProperty;
  }

  /**
   * Get the name of the field used as the high watermark.
   *
   * The name of the field qualified with an alias if available.
   *
   * @see \Drupal\migrate\Plugin\migrate\source\SqlBase::initializeIterator()
   *
   * @return string|null
   *   The name of the field for the high water mark, or NULL if not set.
   */
  protected function getHighWaterField() {
    if (!empty($this->highWaterProperty['name'])) {
      return !empty($this->highWaterProperty['alias']) ?
        $this->highWaterProperty['alias'] . '.' . $this->highWaterProperty['name'] :
        $this->highWaterProperty['name'];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function preRollback(MigrateRollbackEvent $event) {
    // Nothing to do in this implementation.
  }

  /**
   * {@inheritdoc}
   */
  public function postRollback(MigrateRollbackEvent $event) {
    // Reset the high-water mark.
    $this->saveHighWater(NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceModule() {
    if (!empty($this->configuration['source_module'])) {
      return $this->configuration['source_module'];
    }
    elseif (!empty($this->pluginDefinition['source_module'])) {
      return $this->pluginDefinition['source_module'];
    }
    return NULL;
  }

}
