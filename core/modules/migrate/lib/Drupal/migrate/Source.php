<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\source\SourceBase.
 */

namespace Drupal\migrate;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Source is a caching / decision making wrapper around the source plugin.
 *
 * Derived classes are expected to define __toString(), returning a string
 * describing the source and significant options, i.e. the query.
 *
 * @see \Drupal\migrate\MigrateSourceInterface
 */
class Source implements \Iterator, \Countable {

  /**
   * The current row from the quey
   *
   * @var \Drupal\Migrate\Row
   */
  protected $currentRow;

  /**
   * The primary key of the current row
   *
   * @var array
   */
  protected $currentIds;

  /**
   * Number of rows intentionally ignored (prepareRow() returned FALSE)
   *
   * @var int
   */
  protected $numIgnored = 0;

  /**
   * Number of rows we've at least looked at.
   *
   * @var int
   */
  protected $numProcessed = 0;

  /**
   * The highwater mark at the beginning of the import operation.
   *
   * @var
   */
  protected $originalHighwater = '';

  /**
   * List of source IDs to process.
   *
   * @var array
   */
  protected $idList = array();

  /**
   * Whether this instance should cache the source count.
   *
   * @var boolean
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
   * @var boolean
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
   * @var array
   */
  protected $sourceIds;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * @var \Drupal\migrate\Plugin\MigrateIdMapInterface
   */
  protected $idMap;

  /**
   * @var array
   */
  protected $highwaterProperty;

  public function getCurrentIds() {
    return $this->currentIds;
  }

  public function getIgnored() {
    return $this->numIgnored;
  }

  public function getProcessed() {
    return $this->numProcessed;
  }

  /**
   * Reset numIgnored back to 0.
   */
  public function resetStats() {
    $this->numIgnored = 0;
  }

  /**
   * Return a count of available source records, from the cache if appropriate.
   * Returns -1 if the source is not countable.
   *
   * @param boolean $refresh
   * @return int
   */
  public function count($refresh = FALSE) {
    if ($this->skipCount) {
      return -1;
    }
    $source = $this->migration->getSourcePlugin();

    if (!isset($this->cacheKey)) {
      $this->cacheKey = hash('sha256', (string) $source);
    }

    // If a refresh is requested, or we're not caching counts, ask the derived
    // class to get the count from the source.
    if ($refresh || !$this->cacheCounts) {
      $count = $source->count();
      $this->cache->set($this->cacheKey, $count, 'cache');
    }
    else {
      // Caching is in play, first try to retrieve a cached count.
      $cache_object = $this->cache->get($this->cacheKey, 'cache');
      if (is_object($cache_object)) {
        // Success
        $count = $cache_object->data;
      }
      else {
        // No cached count, ask the derived class to count 'em up, and cache
        // the result
        $count = $source->count();
        $this->cache->set($this->cacheKey, $count, 'cache');
      }
    }
    return $count;
  }

  /**
   * Class constructor.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   * @param \Drupal\migrate\MigrateExecutable $migrate_executable
   */
  function __construct(MigrationInterface $migration, MigrateExecutable $migrate_executable) {
    $this->migration = $migration;
    $this->migrateExecutable = $migrate_executable;
    $configuration = $migration->get('source');
    if (!empty($configuration['cache_counts'])) {
      $this->cacheCounts = TRUE;
    }
    if (!empty($configuration['skip_count'])) {
      $this->skipCount = TRUE;
    }
    if (!empty($configuration['cache_key'])) {
      $this->cacheKey = $configuration['cache_key'];
    }
    if (!empty($configuration['track_changes'])) {
      $this->trackChanges = $configuration['track_changes'];
    }
  }

  /**
   * @return \Drupal\Core\Cache\CacheBackendInterface
   */
  protected function getCache() {
    if (!isset($this->cache)) {
      $this->cache = \Drupal::cache('migrate');
    }
    return $this->cache;
  }

  /**
   * @return \Iterator
   */
  protected function getIterator() {
    if (!isset($this->iterator)) {
      $this->iterator = $this->migration->getSourcePlugin()->getIterator();
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
   * Implementation of Iterator::key - called when entering a loop iteration, returning
   * the key of the current row. It must be a scalar - we will serialize
   * to fulfill the requirement, but using getCurrentIds() is preferable.
   */
  public function key() {
    return serialize($this->currentIds);
  }

  /**
   * Implementation of Iterator::valid() - called at the top of the loop, returning
   * TRUE to process the loop and FALSE to terminate it
   */
  public function valid() {
    return isset($this->currentRow);
  }

  /**
   * Implementation of Iterator::rewind() - subclasses of MigrateSource should
   * implement performRewind() to do any class-specific setup for iterating
   * source records.
   */
  public function rewind() {
    $this->idMap = $this->migration->getIdMap();
    $this->numProcessed = 0;
    $this->numIgnored = 0;
    $this->originalHighwater = $this->migration->getHighwater();
    $this->highwaterProperty = $this->migration->get('highwaterProperty');
    if ($id_list = $this->migration->get('idlist')) {
      $this->idList = $id_list;
    }
    $this->getIterator()->rewind();
    $this->next();
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    $this->currentIds = NULL;
    $this->currentRow = NULL;

    while ($this->getIterator()->valid()) {
      $row_data = $this->getIterator()->current();
      $this->getIterator()->next();
      $row = new Row($row_data, $this->migration->get('sourceIds'), $this->migration->get('destinationIds'));

      // Populate the source key for this row
      $this->currentIds = $row->getSourceIdValues();

      // Pick up the existing map row, if any, unless getNextRow() did it.
      if (!$this->mapRowAdded && ($id_map = $this->idMap->getRowBySource($this->currentIds))) {
        $row->setIdMap($id_map);
      }

      // First, determine if this row should be passed to prepareRow(), or
      // skipped entirely. The rules are:
      // 1. If there's an explicit idlist, that's all we care about (ignore
      //    highwaters and map rows).
      $prepared = FALSE;
      if (!empty($this->idList)) {
        if (in_array(reset($this->currentIds), $this->idList)) {
          // In the list, fall through.
        }
        else {
          // Not in the list, skip it
          continue;
        }
      }
      // 2. If the row is not in the map (we have never tried to import it
      //    before), we always want to try it.
      elseif (!$row->getIdMap()) {
        // Fall through
      }
      // 3. If the row is marked as needing update, pass it.
      elseif ($row->needsUpdate()) {
        // Fall through
      }
      // 4. At this point, we have a row which has previously been imported and
      //    not marked for update. If we're not using highwater marks, then we
      //    will not take this row. Except, if we're looking for changes in the
      //    data, we need to go through prepareRow() before we can decide to
      //    skip it.
      elseif (!empty($highwater['field'])) {
        if ($this->trackChanges) {
          if ($this->prepareRow($row) !== FALSE) {
            if ($row->changed()) {
              // This is a keeper
              $this->currentRow = $row;
              break;
            }
            else {
              // No change, skip it.
              continue;
            }
          }
          else {
            // prepareRow() told us to skip it.
            continue;
          }
        }
        else {
          // No highwater and not tracking changes, skip.
          continue;
        }
      }
      // 5. The initial highwater mark, before anything is migrated, is ''. We
      //    want to make sure we don't mistakenly skip rows with a highwater
      //    field value of 0, so explicitly handle '' here.
      elseif ($this->originalHighwater === '') {
        // Fall through
      }
      // 6. So, we are using highwater marks. Take the row if its highwater
      //    field value is greater than the saved mark, otherwise skip it.
      else {
        // Call prepareRow() here, in case the highwaterField needs preparation
        if ($this->prepareRow($row) !== FALSE) {
          if ($row->getSourceProperty($this->highwaterProperty['name']) > $this->originalHighwater) {
            $this->currentRow = $row;
            break;
          }
          else {
            // Skip
            continue;
          }
        }
        $prepared = TRUE;
      }

      // Allow the Migration to prepare this row. prepareRow() can return boolean
      // FALSE to ignore this row.
      if (!$prepared) {
        if ($this->prepareRow($row) !== FALSE) {
          // Finally, we've got a keeper.
          $this->currentRow = $row;
          break;
        }
        else {
          $this->currentRow = NULL;
        }
      }
    }
    if ($this->currentRow) {
      $this->currentRow->freezeSource();
    }
    else {
      $this->currentIds = NULL;
    }
  }

  /**
   * Source classes should override this as necessary and manipulate $keep.
   *
   * @param \Drupal\migrate\Row $row
   */
  protected function prepareRow(Row $row) {
    // We're explicitly skipping this row - keep track in the map table
    if ($this->migration->getSourcePlugin()->prepareRow($row) === FALSE) {
      // Make sure we replace any previous messages for this item with any
      // new ones.
      $id_map = $this->migration->getIdMap();
      $id_map->delete($this->currentIds, TRUE);
      $this->migrateExecutable->saveQueuedMessages();
      $id_map->saveIdMapping($row, array(), MigrateIdMapInterface::STATUS_IGNORED, $this->migrateExecutable->rollbackAction);
      $this->numIgnored++;
      $this->currentRow = NULL;
      $this->currentIds = NULL;
    }
    else {
      // When tracking changed data, We want to quietly skip (rather than
      // "ignore") rows with changes. The caller needs to make that decision,
      // so we need to provide them with the necessary information (before and
      // after hashes).
      if ($this->trackChanges) {
        $row->rehash();
      }
    }
    $this->numProcessed++;
  }
}
