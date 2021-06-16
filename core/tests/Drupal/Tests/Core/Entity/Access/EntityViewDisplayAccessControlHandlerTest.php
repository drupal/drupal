<?php

namespace Drupal\Tests\Core\Entity\Access;

use Drupal\Core\Entity\Entity\Access\EntityViewDisplayAccessControlHandler;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Session\AccountInterface;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Entity\Access\EntityViewDisplayAccessControlHandler
 * @group Entity
 */
class EntityViewDisplayAccessControlHandlerTest extends EntityFormDisplayAccessControlHandlerTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->member = $this->createMock(AccountInterface::class);
    $this->member
      ->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValueMap([
        ['administer foobar display', TRUE],
      ]));
    $this->member
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(2));

    $this->entity = new EntityViewDisplay([
      'targetEntityType' => 'foobar',
      'bundle' => 'bazqux',
      'mode' => 'default',
      'id' => 'foobar.bazqux.default',
      'uuid' => '6f2f259a-f3c7-42ea-bdd5-111ad1f85ed1',
    ], 'entity_display');
    $this->accessControlHandler = new EntityViewDisplayAccessControlHandler($this->entity->getEntityType());
    $this->accessControlHandler->setModuleHandler($this->moduleHandler);
  }

}
