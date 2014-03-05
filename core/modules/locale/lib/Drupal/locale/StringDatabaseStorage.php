<?php

/**
 * @file
 * Definition of Drupal\locale\StringDatabaseStorage.
 */

namespace Drupal\locale;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;

/**
 * Defines the locale string class.
 *
 * This is the base class for SourceString and TranslationString.
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
  protected $options = array();

  /**
   * Constructs a new StringStorage controller.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A Database connection to use for reading and writing configuration data.
   * @param array $options
   *   (optional) Any additional database connection options to use in queries.
   */
  public function __construct(Connection $connection, array $options = array()) {
    $this->connection = $connection;
    $this->options = $options;
  }

  /**
   * Implements Drupal\locale\StringStorageInterface::getStrings().
   */
  public function getStrings(array $conditions = array(), array $options = array()) {
    return $this->dbStringLoad($conditions, $options, 'Drupal\locale\SourceString');
  }

  /**
   * Implements Drupal\locale\StringStorageInterface::getTranslations().
   */
  public function getTranslations(array $conditions = array(), array $options = array()) {
    return $this->dbStringLoad($conditions, array('translation' => TRUE) + $options, 'Drupal\locale\TranslationString');
  }

  /**
   * Implements Drupal\locale\StringStorageInterface::findString().
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
   * Implements Drupal\locale\StringStorageInterface::findTranslation().
   */
  public function findTranslation(array $conditions) {
    $values = $this->dbStringSelect($conditions, array('translation' => TRUE))
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
   * Implements Drupal\locale\StringStorageInterface::getLocations().
   */
  function getLocations(array $conditions = array()) {
    $query = $this->connection->select('locales_location', 'l', $this->options)
      ->fields('l');
    foreach ($conditions as $field => $value) {
      $query->condition('l.' . $field, $value);
    }
    return $query->execute()->fetchAll();
  }

  /**
   * Implements Drupal\locale\StringStorageInterface::countStrings().
   */
  public function countStrings() {
    return $this->dbExecute("SELECT COUNT(*) FROM {locales_source}")->fetchField();
  }

  /**
   * Implements Drupal\locale\StringStorageInterface::countTranslations().
   */
  public function countTranslations() {
    return $this->dbExecute("SELECT t.language, COUNT(*) AS translated FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid GROUP BY t.language")->fetchAllKeyed();
  }

  /**
   * Implements Drupal\locale\StringStorageInterface::save().
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
            $this->dbDelete('locales_location', array('sid' => $string->getId(), 'type' => $type, 'name' => $name))
              ->execute();
          }
          elseif ($lid === TRUE) {
            // This is a new location to add, take care not to duplicate.
            $this->connection->merge('locales_location', $this->options)
              ->keys(array('sid' => $string->getId(), 'type' => $type, 'name' => $name))
              ->fields(array('version' => \Drupal::VERSION))
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
      ->fields(array('version' => $version))
      ->execute();
    }
  }

  /**
   * Implements Drupal\locale\StringStorageInterface::delete().
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
      throw new StringStorageException(format_string('The string cannot be deleted because it lacks some key fields: @string', array(
        '@string' => $string->getString()
      )));
    }
    return $this;
  }

  /**
   * Implements Drupal\locale\StringStorageInterface::deleteLanguage().
   */
  public function deleteStrings($conditions) {
    $lids = $this->dbStringSelect($conditions, array('fields' => array('lid')))->execute()->fetchCol();
    if ($lids) {
      $this->dbDelete('locales_target', array('lid' => $lids))->execute();
      $this->dbDelete('locales_source',  array('lid' => $lids))->execute();
      $this->dbDelete('locales_location',  array('sid' => $lids))->execute();
    }
  }

  /**
   * Implements Drupal\locale\StringStorageInterface::deleteLanguage().
   */
  public function deleteTranslations($conditions) {
    $this->dbDelete('locales_target', $conditions)->execute();
  }

  /**
   * Implements Drupal\locale\StringStorageInterface::createString().
   */
  public function createString($values = array()) {
    return new SourceString($values + array('storage' => $this));
  }

  /**
   * Implements Drupal\locale\StringStorageInterface::createTranslation().
   */
  public function createTranslation($values = array()) {
    return new TranslationString($values + array(
      'storage' => $this,
      'is_new' => TRUE
    ));
  }

  /**
   * Gets table alias for field.
   *
   * @param string $field
   *   Field name to find the table alias for.
   *
   * @return string
   *   Either 's', 't' or 'l' depending on whether the field belongs to source,
   *   target or location table table.
   */
  protected function dbFieldTable($field) {
    if (in_array($field, array('language', 'translation', 'customized'))) {
      return 't';
    }
    elseif (in_array($field, array('type', 'name'))) {
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
      $keys = array('lid');
    }
    elseif ($string->isTranslation()) {
      $keys = array('lid', 'language');
    }
    if (!empty($keys) && ($values = $string->getValues($keys)) && count($keys) == count($values)) {
      return $values;
    }
    else {
      return array();
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
   * @return array
   *   Array of objects of the class requested.
   */
  protected function dbStringLoad(array $conditions, array $options, $class) {
    $strings = array();
    $result = $this->dbStringSelect($conditions, $options)->execute();
    foreach ($result as $item) {
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
   * @return SelectQuery
   *   Query object with all the tables, fields and conditions.
   */
  protected function dbStringSelect(array $conditions, array $options = array()) {
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
        $query->$join('locales_target', 't', "t.lid = s.lid AND t.language = :langcode", array(
          ':langcode' => $conditions['language']
        ));
        unset($conditions['language']);
      }
      else {
        // Since we don't have a language, join with locale id only.
        $query->$join('locales_target', 't', "t.lid = s.lid");
      }
      if (!empty($options['translation'])) {
        // We cannot just add all fields because 'lid' may get null values.
        $query->fields('t', array('language', 'translation', 'customized'));
      }
    }

    // If we have conditions for location's type or name, then we need the
    // location table, for which we add a subquery.
    if (isset($conditions['type']) || isset($conditions['name'])) {
      $subquery = $this->connection->select('locales_location', 'l', $this->options)
        ->fields('l', array('sid'));
      foreach (array('type', 'name') as $field) {
        if (isset($conditions[$field])) {
          $subquery->condition('l.' . $field, $conditions[$field]);
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
        $query->condition(db_or()
            ->condition($field_alias, $value)
            ->isNull($field_alias)
        );
      }
      else {
        $query->condition($field_alias, $value);
      }
    }

    // Process other options, string filter, query limit, etc...
    if (!empty($options['filters'])) {
      if (count($options['filters']) > 1) {
        $filter = db_or();
        $query->condition($filter);
      }
      else {
        // If we have a single filter, just add it to the query.
        $filter = $query;
      }
      foreach ($options['filters'] as $field => $string) {
        $filter->condition($this->dbFieldTable($field) . '.' . $field, '%' . db_like($string) . '%', 'LIKE');
      }
    }

    if (!empty($options['pager limit'])) {
      $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($options['pager limit']);
    }

    return $query;
  }

  /**
   * Createds a database record for a string object.
   *
   * @param \Drupal\locale\StringInterface $string
   *   The string object.
   *
   * @return bool|int
   *   If the operation failed, returns FALSE.
   *   If it succeeded returns the last insert ID of the query, if one exists.
   *
   * @throws \Drupal\locale\StringStorageException
   *   If the string is not suitable for this storage, an exception ithrown.
   */
  protected function dbStringInsert($string) {
    if ($string->isSource()) {
      $string->setValues(array('context' => '', 'version' => 'none'), FALSE);
      $fields = $string->getValues(array('source', 'context', 'version'));
    }
    elseif ($string->isTranslation()) {
      $string->setValues(array('customized' => 0), FALSE);
      $fields = $string->getValues(array('lid', 'language', 'translation', 'customized'));
    }
    if (!empty($fields)) {
      return $this->connection->insert($this->dbStringTable($string), $this->options)
        ->fields($fields)
        ->execute();
    }
    else {
      throw new StringStorageException(format_string('The string cannot be saved: @string', array(
          '@string' => $string->getString()
      )));
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
      $values = $string->getValues(array('source', 'context', 'version'));
    }
    elseif ($string->isTranslation()) {
      $values = $string->getValues(array('translation', 'customized'));
    }
    if (!empty($values) && $keys = $this->dbStringKeys($string)) {
      return $this->connection->merge($this->dbStringTable($string), $this->options)
        ->keys($keys)
        ->fields($values)
        ->execute();
    }
    else {
      throw new StringStorageException(format_string('The string cannot be updated: @string', array(
          '@string' => $string->getString()
      )));
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
   * @return DeleteQuery
   *   Returns a new DeleteQuery object for the active database.
   */
  protected function dbDelete($table, $keys) {
    $query = $this->connection->delete($table, $this->options);
    foreach ($keys as $field => $value) {
      $query->condition($field, $value);
    }
    return $query;
  }

  /**
   * Executes an arbitrary SELECT query string.
   */
  protected function dbExecute($query, array $args = array()) {
    return $this->connection->query($query, $args, $this->options);
  }
}
