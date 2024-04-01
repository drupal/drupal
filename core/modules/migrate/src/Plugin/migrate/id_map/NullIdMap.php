<?php

namespace Drupal\migrate\Plugin\migrate\id_map;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Defines the null ID map implementation.
 *
 * This serves as a dummy in order to not store anything.
 *
 * @PluginID("null")
 */
class NullIdMap extends PluginBase implements MigrateIdMapInterface {

  /**
   * {@inheritdoc}
   */
  public function setMessage(MigrateMessageInterface $message) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function getRowBySource(array $source_id_values) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRowByDestination(array $destination_id_values) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRowsNeedingUpdate($count) {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function lookupSourceId(array $destination_id_values) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function lookupDestinationIds(array $source_id_values) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function saveIdMapping(Row $row, array $destination_id_values, $source_row_status = MigrateIdMapInterface::STATUS_IMPORTED, $rollback_action = MigrateIdMapInterface::ROLLBACK_DELETE) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function saveMessage(array $source_id_values, $message, $level = MigrationInterface::MESSAGE_ERROR) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages(array $source_id_values = [], $level = NULL) {
    return new \ArrayIterator([]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareUpdate() {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function processedCount() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function importedCount() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function updateCount() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function errorCount() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function messageCount() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $source_id_values, $messages_only = FALSE) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDestination(array $destination_id_values) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function setUpdate(array $source_id_values) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function clearMessages() {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function destroy() {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function currentDestination() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function currentSource() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getQualifiedMapTableName() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function rewind() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function current() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function key() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function next() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function valid() {
    return FALSE;
  }

}
