<?php

namespace Drupal\locale;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;

/**
 * Defines a class to store localized strings in the database.
 */
class StringDatabaseStorage implements StringStorageInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Additional database connection options to use in queries.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Constructs a new StringDatabaseStorage class.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A Database connection to use for reading and writing configuration data.
   * @param array $options
   *   (optional) Any additional database connection options to use in queries.
   */
  public function __construct(Connection $connection, array $options = []) {
    $this->connection = $connection;
    $this->options = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getStrings(array $conditions = [], array $options = []) {
    return $this->dbStringLoad($conditions, $options, 'Drupal\locale\SourceString');
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslations(array $conditions = [], array $options = []) {
    return $this->dbStringLoad($conditions, ['translation' => TRUE] + $options, 'Drupal\locale\TranslationString');
  }

  /**
   * {@inheritdoc}
   */
  public function findString(array $conditions) {
    $values = $this->dbStringSelect($conditions)
      ->execute()
      ->fetchAssoc();

    if (!empty($values)) {
      $string = new SourceString($values);
      $string->setStorage($this);
      return $string;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function findTranslation(array $conditions) {
    $values = $this->dbStringSelect($conditions, ['translation' => TRUE])
      ->execute()
      ->fetchAssoc();

    if (!empty($values)) {
      $string = new TranslationString($values);
      $this->checkVersion($string, \Drupal::VERSION);
      $string->setStorage($this);
      return $string;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLocations(array $conditions = []) {
    $query = $this->connection->select('locales_location', 'l', $this->options)
      ->fields('l');
    foreach ($conditions as $field => $value) {
      // Cast scalars to array so we can consistently use an IN condition.
      $query->condition('l.' . $field, (array) $value, 'IN');
    }
    return $query->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function countStrings() {
    return $this->dbExecute("SELECT COUNT(*) FROM {locales_source}")->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function countTranslations() {
    return $this->dbExecute("SELECT t.language, COUNT(*) AS translated FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid GROUP BY t.language")->fetchAllKeyed();
  }

  /**
   * {@inheritdoc}
   */
  public function save($string) {
    if ($string->isNew()) {
      $result = $this->dbStringInsert($string);
      if ($string->isSource() && $result) {
        // Only for source strings, we set the locale identifier.
        $string->setId($result);
      }
      $string->setStorage($this);
    }
    else {
      $this->dbStringUpdate($string);
    }
    // Update locations if they come with the string.
    $this->updateLocation($string);
    return $this;
  }

  /**
   * Update locations for string.
   *
   * @param \Drupal\locale\StringInterface $string
   *   The string object.
   */
  protected function updateLocation($string) {
    if ($locations = $string->getLocations(TRUE)) {
      $created = FALSE;
      foreach ($locations as $type => $location) {
        foreach ($location as $name => $lid) {
          // Make sure that the name isn't longer than 255 characters.
          $name = substr($name, 0, 255);
          if (!$lid) {
            $this->dbDelete('locales_location', ['sid' => $string->getId(), 'type' => $type, 'name' => $name])
              ->execute();
          }
          elseif ($lid === TRUE) {
            // This is a new location to add, take care not to duplicate.
            $this->connection->merge('locales_location', $this->options)
              ->keys(['sid' => $string->getId(), 'type' => $type, 'name' => $name])
              ->fields(['version' => \Drupal::VERSION])
              ->execute();
            $created = TRUE;
          }
          // Loaded locations have 'lid' integer value, nor FALSE, nor TRUE.
        }
      }
      if ($created) {
        // As we've set a new location, check string version too.
        $this->checkVersion($string, \Drupal::VERSION);
      }
    }
  }

  /**
   * Checks whether the string version matches a given version, fix it if not.
   *
   * @param \Drupal\locale\StringInterface $string
   *   The string object.
   * @param string $version
   *   Drupal version to check against.
   */
  protected function checkVersion($string, $version) {
    if ($string->getId() && $string->getVersion() != $version) {
      $string->setVersion($version);
      $this->connection->update('locales_source', $this->options)
        ->condition('lid', $string->getId())
        ->fields(['version' => $version])
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($string) {
    if ($keys = $this->dbStringKeys($string)) {
      $this->dbDelete('locales_target', $keys)->execute();
      if ($string->isSource()) {
        $this->dbDelete('locales_source', $keys)->execute();
        $this->dbDelete('locales_location', $keys)->execute();
        $string->setId(NULL);
      }
    }
    else {
      throw new StringStorageException('The string cannot be deleted because it lacks some key fields: ' . $string->getString());
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteStrings($conditions) {
    $lids = $this->dbStringSelect($conditions, ['fields' => ['lid']])->execute()->fetchCol();
    if ($lids) {
      $this->dbDelete('locales_target', ['lid' => $lids])->execute();
      $this->dbDelete('locales_source', ['lid' => $lids])->execute();
      $this->dbDelete('locales_location', ['sid' => $lids])->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTranslations($conditions) {
    $this->dbDelete('locales_target', $conditions)->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function createString($values = []) {
    return new SourceString($values + ['storage' => $this]);
  }

  /**
   * {@inheritdoc}
   */
  public function createTranslation($values = []) {
    return new TranslationString($values + [
      'storage' => $this,
      'is_new' => TRUE,
    ]);
  }

  /**
   * Gets table alias for field.
   *
   * @param string $field
   *   One of the field names of the locales_source, locates_location,
   *   locales_target tables to find the table alias for.
   *
   * @return string
   *   One of the following values:
   *   - 's' for "source", "context", "version" (locales_source table fields).
   *   - 'l' for "type", "name" (locales_location table fields)
   *   - 't' for "language", "translation", "customized" (locales_target
   *     table fields)
   */
  protected function dbFieldTable($field) {
    if (in_array($field, ['language', 'translation', 'customized'])) {
      return 't';
    }
    elseif (in_array($field, ['type', 'name'])) {
      return 'l';
    }
    else {
      return 's';
    }
  }

  /**
   * Gets table name for storing string object.
   *
   * @param \Drupal\locale\StringInterface $string
   *   The string object.
   *
   * @return string
   *   The table name.
   */
  protected function dbStringTable($string) {
    if ($string->isSource()) {
      return 'locales_source';
    }
    elseif ($string->isTranslation()) {
      return 'locales_target';
    }
  }

  /**
   * Gets keys values that are in a database table.
   *
   * @param \Drupal\locale\StringInterface $string
   *   The string object.
   *
   * @return array
   *   Array with key fields if the string has all keys, or empty array if not.
   */
  protected function dbStringKeys($string) {
    if ($string->isSource()) {
      $keys = ['lid'];
    }
    elseif ($string->isTranslation()) {
      $keys = ['lid', 'language'];
    }
    if (!empty($keys) && ($values = $string->getValues($keys)) && count($keys) == count($values)) {
      return $values;
    }
    else {
      return [];
    }
  }

  /**
   * Loads multiple string objects.
   *
   * @param array $conditions
   *   Any of the conditions used by dbStringSelect().
   * @param array $options
   *   Any of the options used by dbStringSelect().
   * @param string $class
   *   Class name to use for fetching returned objects.
   *
   * @return \Drupal\locale\StringInterface[]
   *   Array of objects of the class requested.
   */
  protected function dbStringLoad(array $conditions, array $options, $class) {
    $strings = [];
    $result = $this->dbStringSelect($conditions, $options)->execute();
    foreach ($result as $item) {
      /** @var \Drupal\locale\StringInterface $string */
      $string = new $class($item);
      $string->setStorage($this);
      $strings[] = $string;
    }
    return $strings;
  }

  /**
   * Builds a SELECT query with multiple conditions and fields.
   *
   * The query uses both 'locales_source' and 'locales_target' tables.
   * Note that by default, as we are selecting both translated and untranslated
   * strings target field's conditions will be modified to match NULL rows too.
   *
   * @param array $conditions
   *   An associative array with field => value conditions that may include
   *   NULL values. If a language condition is included it will be used for
   *   joining the 'locales_target' table.
   * @param array $options
   *   An associative array of additional options. It may contain any of the
   *   options used by Drupal\locale\StringStorageInterface::getStrings() and
   *   these additional ones:
   *   - 'translation', Whether to include translation fields too. Defaults to
   *     FALSE.
   *
   * @return \Drupal\Core\Database\Query\Select
   *   Query object with all the tables, fields and conditions.
   */
  protected function dbStringSelect(array $conditions, array $options = []) {
    // Start building the query with source table and check whether we need to
    // join the target table too.
    $query = $this->connection->select('locales_source', 's', $this->options)
      ->fields('s');

    // Figure out how to join and translate some options into conditions.
    if (isset($conditions['translated'])) {
      // This is a meta-condition we need to translate into simple ones.
      if ($conditions['translated']) {
        // Select only translated strings.
        $join = 'innerJoin';
      }
      else {
        // Select only untranslated strings.
        $join = 'leftJoin';
        $conditions['translation'] = NULL;
      }
      unset($conditions['translated']);
    }
    else {
      $join = !empty($options['translation']) ? 'leftJoin' : FALSE;
    }

    if ($join) {
      if (isset($conditions['language'])) {
        // If we've got a language condition, we use it for the join.
        $query->$join('locales_target', 't', "t.lid = s.lid AND t.language = :langcode", [
          ':langcode' => $conditions['language'],
        ]);
        unset($conditions['language']);
      }
      else {
        // Since we don't have a language, join with locale id only.
        $query->$join('locales_target', 't', "t.lid = s.lid");
      }
      if (!empty($options['translation'])) {
        // We cannot just add all fields because 'lid' may get null values.
        $query->fields('t', ['language', 'translation', 'customized']);
      }
    }

    // If we have conditions for location's type or name, then we need the
    // location table, for which we add a subquery. We cast any scalar value to
    // array so we can consistently use IN conditions.
    if (isset($conditions['type']) || isset($conditions['name'])) {
      $subquery = $this->connection->select('locales_location', 'l', $this->options)
        ->fields('l', ['sid']);
      foreach (['type', 'name'] as $field) {
        if (isset($conditions[$field])) {
          $subquery->condition('l.' . $field, (array) $conditions[$field], 'IN');
          unset($conditions[$field]);
        }
      }
      $query->condition('s.lid', $subquery, 'IN');
    }

    // Add conditions for both tables.
    foreach ($conditions as $field => $value) {
      $table_alias = $this->dbFieldTable($field);
      $field_alias = $table_alias . '.' . $field;
      if (is_null($value)) {
        $query->isNull($field_alias);
      }
      elseif ($table_alias == 't' && $join === 'leftJoin') {
        // Conditions for target fields when doing an outer join only make
        // sense if we add also OR field IS NULL.
        $query->condition(($this->connection->condition('OR'))
          ->condition($field_alias, (array) $value, 'IN')
          ->isNull($field_alias)
        );
      }
      else {
        $query->condition($field_alias, (array) $value, 'IN');
      }
    }

    // Process other options, string filter, query limit, etc.
    if (!empty($options['filters'])) {
      if (count($options['filters']) > 1) {
        $filter = $this->connection->condition('OR');
        $query->condition($filter);
      }
      else {
        // If we have a single filter, just add it to the query.
        $filter = $query;
      }
      foreach ($options['filters'] as $field => $string) {
        $filter->condition($this->dbFieldTable($field) . '.' . $field, '%' . $this->connection->escapeLike($string) . '%', 'LIKE');
      }
    }

    if (!empty($options['pager limit'])) {
      $query = $query->extend(PagerSelectExtender::class)->limit($options['pager limit']);
    }

    return $query;
  }

  /**
   * Creates a database record for a string object.
   *
   * @param \Drupal\locale\StringInterface $string
   *   The string object.
   *
   * @return bool|int
   *   If the operation failed, returns FALSE.
   *   If it succeeded returns the last insert ID of the query, if one exists.
   *
   * @throws \Drupal\locale\StringStorageException
   *   If the string is not suitable for this storage, an exception is thrown.
   */
  protected function dbStringInsert($string) {
    if ($string->isSource()) {
      $string->setValues(['context' => '', 'version' => 'none'], FALSE);
      $fields = $string->getValues(['source', 'context', 'version']);
    }
    elseif ($string->isTranslation()) {
      $string->setValues(['customized' => 0], FALSE);
      $fields = $string->getValues(['lid', 'language', 'translation', 'customized']);
    }
    if (!empty($fields)) {
      return $this->connection->insert($this->dbStringTable($string), $this->options)
        ->fields($fields)
        ->execute();
    }
    else {
      throw new StringStorageException('The string cannot be saved: ' . $string->getString());
    }
  }

  /**
   * Updates string object in the database.
   *
   * @param \Drupal\locale\StringInterface $string
   *   The string object.
   *
   * @return bool|int
   *   If the record update failed, returns FALSE. If it succeeded, returns
   *   SAVED_NEW or SAVED_UPDATED.
   *
   * @throws \Drupal\locale\StringStorageException
   *   If the string is not suitable for this storage, an exception is thrown.
   */
  protected function dbStringUpdate($string) {
    if ($string->isSource()) {
      $values = $string->getValues(['source', 'context', 'version']);
    }
    elseif ($string->isTranslation()) {
      $values = $string->getValues(['translation', 'customized']);
    }
    if (!empty($values) && $keys = $this->dbStringKeys($string)) {
      return $this->connection->merge($this->dbStringTable($string), $this->options)
        ->keys($keys)
        ->fields($values)
        ->execute();
    }
    else {
      throw new StringStorageException('The string cannot be updated: ' . $string->getString());
    }
  }

  /**
   * Creates delete query.
   *
   * @param string $table
   *   The table name.
   * @param array $keys
   *   Array with object keys indexed by field name.
   *
   * @return \Drupal\Core\Database\Query\Delete
   *   Returns a new Delete object for the injected database connection.
   */
  protected function dbDelete($table, $keys) {
    $query = $this->connection->delete($table, $this->options);
    foreach ($keys as $field => $value) {
      if (!is_array($value)) {
        $value = [$value];
      }
      $query->condition($field, $value, 'IN');
    }
    return $query;
  }

  /**
   * Executes an arbitrary SELECT query string with the injected options.
   */
  protected function dbExecute($query, array $args = []) {
    return $this->connection->query($query, $args, $this->options);
  }

}
