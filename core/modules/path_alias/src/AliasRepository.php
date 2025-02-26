<?php

namespace Drupal\path_alias;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Statement\FetchAs;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides the default path alias lookup operations.
 */
class AliasRepository implements AliasRepositoryInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs an AliasRepository object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection for reading and writing path aliases.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function preloadPathAlias($preloaded, $langcode) {
    $select = $this->getBaseQuery()
      ->fields('base_table', ['path', 'alias']);

    if (!empty($preloaded)) {
      $conditions = $this->connection->condition('OR');
      foreach ($preloaded as $preloaded_item) {
        $conditions->condition('base_table.path', $this->connection->escapeLike($preloaded_item), 'LIKE');
      }
      $select->condition($conditions);
    }

    $this->addLanguageFallback($select, $langcode);

    $select->orderBy('base_table.id', 'DESC');

    // We want the most recently created alias for each source, however that
    // will be at the start of the result-set, so fetch everything and reverse
    // it. Note that it would not be sufficient to reverse the ordering of the
    // 'base_table.id' column, as that would not guarantee other conditions
    // added to the query, such as those in ::addLanguageFallback, would be
    // reversed.
    $results = $select->execute()->fetchAll(FetchAs::Associative);
    $aliases = [];
    foreach (array_reverse($results) as $result) {
      $aliases[$result['path']] = $result['alias'];
    }

    return $aliases;
  }

  /**
   * {@inheritdoc}
   */
  public function lookupBySystemPath($path, $langcode) {
    // See the queries above. Use LIKE for case-insensitive matching.
    $select = $this->getBaseQuery()
      ->fields('base_table', ['id', 'path', 'alias', 'langcode'])
      ->condition('base_table.path', $this->connection->escapeLike($path), 'LIKE');

    $this->addLanguageFallback($select, $langcode);

    $select->orderBy('base_table.id', 'DESC');

    return $select->execute()->fetchAssoc() ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function lookupByAlias($alias, $langcode) {
    // See the queries above. Use LIKE for case-insensitive matching.
    $select = $this->getBaseQuery()
      ->fields('base_table', ['id', 'path', 'alias', 'langcode'])
      ->condition('base_table.alias', $this->connection->escapeLike($alias), 'LIKE');

    $this->addLanguageFallback($select, $langcode);

    $select->orderBy('base_table.id', 'DESC');

    return $select->execute()->fetchAssoc() ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function pathHasMatchingAlias($initial_substring) {
    $query = $this->getBaseQuery();
    $query->addExpression(1);

    return (bool) $query
      ->condition('base_table.path', $this->connection->escapeLike($initial_substring) . '%', 'LIKE')
      ->range(0, 1)
      ->execute()
      ->fetchField();
  }

  /**
   * Returns a SELECT query for the path_alias base table.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A Select query object.
   */
  protected function getBaseQuery() {
    $query = $this->connection->select('path_alias', 'base_table');
    $query->condition('base_table.status', 1);

    return $query;
  }

  /**
   * Adds path alias language fallback conditions to a select query object.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   A Select query object.
   * @param string $langcode
   *   Language code to search the path with. If there's no path defined for
   *   that language it will search paths without language.
   */
  protected function addLanguageFallback(SelectInterface $query, $langcode) {
    // Always get the language-specific alias before the language-neutral one.
    // For example 'de' is less than 'und' so the order needs to be ASC, while
    // 'xx-lolspeak' is more than 'und' so the order needs to be DESC.
    $langcode_list = [$langcode, LanguageInterface::LANGCODE_NOT_SPECIFIED];
    if ($langcode === LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      array_pop($langcode_list);
    }
    elseif ($langcode > LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      $query->orderBy('base_table.langcode', 'DESC');
    }
    else {
      $query->orderBy('base_table.langcode', 'ASC');
    }
    $query->condition('base_table.langcode', $langcode_list, 'IN');
  }

}
