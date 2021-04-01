<?php

namespace Drupal\Tests\Traits\Core;

use Drupal\Core\Language\LanguageInterface;

/**
 * Provides methods to create and assert path_alias entities.
 *
 * This trait is meant to be used only by test classes.
 */
trait PathAliasTestTrait {

  /**
   * Creates a new path alias.
   *
   * @param string $path
   *   The system path.
   * @param string $alias
   *   The alias for the system path.
   * @param string $langcode
   *   (optional) A language code for the path alias. Defaults to
   *   \Drupal\Core\Language\LanguageInterface::LANGCODE_NOT_SPECIFIED.
   *
   * @return \Drupal\path_alias\PathAliasInterface
   *   A path alias entity.
   */
  protected function createPathAlias($path, $alias, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    /** @var \Drupal\path_alias\PathAliasInterface $path_alias */
    $path_alias = \Drupal::entityTypeManager()->getStorage('path_alias')->create([
      'path' => $path,
      'alias' => $alias,
      'langcode' => $langcode,
    ]);
    $path_alias->save();

    return $path_alias;
  }

  /**
   * Gets the first result from a 'load by properties' storage call.
   *
   * @param array $conditions
   *   An array of query conditions.
   *
   * @return \Drupal\path_alias\PathAliasInterface|null
   *   A path alias entity or NULL.
   */
  protected function loadPathAliasByConditions($conditions) {
    $storage = \Drupal::entityTypeManager()->getStorage('path_alias');
    $query = $storage->getQuery()->accessCheck(FALSE);
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }
    $entities = $storage->loadMultiple($query->execute());

    return $entities ? reset($entities) : NULL;
  }

  /**
   * Asserts that a path alias exists in the storage.
   *
   * @param string $alias
   *   The path alias.
   * @param string|null $langcode
   *   (optional) The language code of the path alias.
   * @param string|null $path
   *   (optional) The system path of the path alias.
   * @param string|null $message
   *   (optional) A message to display with the assertion.
   */
  protected function assertPathAliasExists($alias, $langcode = NULL, $path = NULL, $message = NULL) {
    $query = \Drupal::entityTypeManager()
      ->getStorage('path_alias')
      ->getQuery()
      ->accessCheck(FALSE);
    $query->condition('alias', $alias, '=');
    if ($langcode) {
      $query->condition('langcode', $langcode, '=');
    }
    if ($path) {
      $query->condition('path', $path, '=');
    }
    $query->count();

    $this->assertTrue((bool) $query->execute(), $message);
  }

  /**
   * Asserts that a path alias does not exist in the storage.
   *
   * @param string $alias
   *   The path alias.
   * @param string|null $langcode
   *   (optional) The language code of the path alias.
   * @param string|null $path
   *   (optional) The system path of the path alias.
   * @param string|null $message
   *   (optional) A message to display with the assertion.
   */
  protected function assertPathAliasNotExists($alias, $langcode = NULL, $path = NULL, $message = NULL) {
    $query = \Drupal::entityTypeManager()
      ->getStorage('path_alias')
      ->getQuery()
      ->accessCheck(FALSE);
    $query->condition('alias', $alias, '=');
    if ($langcode) {
      $query->condition('langcode', $langcode, '=');
    }
    if ($path) {
      $query->condition('path', $path, '=');
    }
    $query->count();

    $this->assertFalse((bool) $query->execute(), $message);
  }

}
