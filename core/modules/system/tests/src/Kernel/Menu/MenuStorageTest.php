<?php

namespace Drupal\Tests\system\Kernel\Menu;

use Drupal\Core\Config\Entity\Exception\ConfigEntityIdLengthException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Entity\Menu;
use Drupal\system\MenuStorage;

/**
 * Tests MenuStorage.
 *
 * @group Menu
 *
 * @see \Drupal\system\MenuStorage
 */
class MenuStorageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests MenuStorage::MAX_ID_LENGTH is enforced.
   */
  public function testMaxIdLengthException() {
    $id = $this->randomMachineName(MenuStorage::MAX_ID_LENGTH + 1);
    $this->expectException(ConfigEntityIdLengthException::class);
    $this->expectExceptionMessage(
      sprintf('Configuration entity ID %s exceeds maximum allowed length of %s characters.', $id, MenuStorage::MAX_ID_LENGTH)
    );
    Menu::create(['id' => $id])->save();
  }

}
