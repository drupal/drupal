<?php

namespace Drupal\Tests\Core\Session;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\MetadataBag;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\Core\Session\SessionManager;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\Proxy\AbstractProxy;

/**
 * @coversDefaultClass \Drupal\Core\Session\SessionManager
 * @group Session
 */
class SessionManagerTest extends UnitTestCase {

  /**
   * @group legacy
   */
  public function testGetIdWithoutSession() {
    $connection = $this->createMock(Connection::class);
    $session_configuration = $this->createMock(SessionConfigurationInterface::class);
    $session_manager = new SessionManager(
      new RequestStack(),
      $connection,
      new MetadataBag(new Settings([])),
      $session_configuration,
      new FakeAbstractProxy()
    );

    $this->expectDeprecation('Calling Drupal\Core\Session\SessionManager::getId() outside of an actual existing session is deprecated in drupal:9.2.0 and will be removed in drupal:10.0.0. This is often used for anonymous users. See https://www.drupal.org/node/3006306');
    $this->assertNotEmpty($session_manager->getId());
  }

}

class FakeAbstractProxy extends AbstractProxy {

  /**
   * Stores the fake session ID.
   *
   * @var string
   */
  protected $id;

  public function setId($id) {
    $this->id = $id;
  }

  public function getId() {
    return $this->id;
  }

}
