<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\MetadataBag;
use Drupal\Core\Session\SessionConfiguration;
use Drupal\Core\Session\SessionManager;
use Drupal\Core\Session\UserSessionRepositoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Proxy\SessionHandlerProxy;

/**
 * Tests Drupal\Core\Session\SessionManager.
 */
#[CoversClass(SessionManager::class)]
#[Group('Session')]
class SessionManagerTest extends UnitTestCase {

  /**
   * Tests that session manager is constructed with deprecated argument list.
   */
  #[IgnoreDeprecations]
  public function testDeprecatedMiddlewaresArgument(): void {
    $connection = $this->createStub(Connection::class);
    $handler = new NullSessionHandler();
    $sessionRepository = $this->createStub(UserSessionRepositoryInterface::class);

    $container = new ContainerBuilder();
    $container->set(UserSessionRepositoryInterface::class, $sessionRepository);
    \Drupal::setContainer($container);

    $this->expectUserDeprecationMessage('Calling Drupal\Core\Session\SessionManager::__construct() with a database $connection as the second argument is deprecated in drupal:11.4.0 and it will throw an error in drupal:12.0.0. See https://www.drupal.org/node/3570851');
    $sessionManager = new SessionManager(
      new RequestStack(),
      $connection,
      new MetadataBag(new Settings([])),
      new SessionConfiguration(),
      new Time(),
      $handler,
    );
    $abstractProxy = $sessionManager->getSaveHandler();
    assert($abstractProxy instanceof SessionHandlerProxy);
    $this->assertSame($handler, $abstractProxy->getHandler());
  }

}
