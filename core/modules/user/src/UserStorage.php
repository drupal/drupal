<?php

namespace Drupal\user;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;

/**
 * Controller class for users.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class,
 * adding required special handling for user objects.
 */
class UserStorage extends SqlContentEntityStorage implements UserStorageInterface {

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
    // The anonymous user account is saved with the fixed user ID of 0. MySQL
    // does not support inserting an ID of 0 into serial field unless the SQL
    // mode is set to NO_AUTO_VALUE_ON_ZERO.
    // @todo https://drupal.org/i/3222123 implement a generic fix for all entity
    //   types.
    if ($entity->id() === 0) {
      $database = \Drupal::database();
      if ($database->databaseType() === 'mysql') {
        $sql_mode = $database->query("SELECT @@sql_mode;")->fetchField();
        $database->query("SET sql_mode = '$sql_mode,NO_AUTO_VALUE_ON_ZERO'");
      }
    }
    parent::doSaveFieldItems($entity, $names);

    // Reset the SQL mode if we've changed it.
    if (isset($sql_mode, $database)) {
      $database->query("SET sql_mode = '$sql_mode'");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateLastLoginTimestamp(UserInterface $account) {
    $this->database->update($this->getDataTable())
      ->fields(['login' => $account->getLastLoginTime()])
      ->condition('uid', $account->id())
      ->execute();
    // Ensure that the entity cache is cleared.
    $this->resetCache([$account->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateLastAccessTimestamp(AccountInterface $account, $timestamp) {
    $this->database->update($this->getDataTable())
      ->fields([
        'access' => $timestamp,
      ])
      ->condition('uid', $account->id())
      ->execute();
    // Ensure that the entity cache is cleared.
    $this->resetCache([$account->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRoleReferences(array $rids) {
    // Remove the role from all users.
    $this->database->delete('user__roles')
      ->condition('roles_target_id', $rids)
      ->execute();

    $this->resetCache();
  }

}
