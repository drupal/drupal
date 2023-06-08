<?php

namespace Drupal\Tests\system\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\system\TimeZoneResolver
 * @group system
 */
class TimezoneResolverTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
  ];

  /**
   * Tests time zone resolution.
   */
  public function testGetTimeZone() {
    $this->installEntitySchema('user');
    $this->installConfig(['system']);

    // Check the default test timezone.
    $this->assertEquals('Australia/Sydney', date_default_timezone_get());

    // Test the configured system timezone.
    $configFactory = $this->container->get('config.factory');
    $timeZoneConfig = $configFactory->getEditable('system.date');
    $timeZoneConfig->set('timezone.default', 'Australia/Adelaide');
    $timeZoneConfig->save();

    $eventDispatcher = $this->container->get('event_dispatcher');
    $kernel = $this->container->get('kernel');

    $eventDispatcher->dispatch(new RequestEvent($kernel, Request::create('http://www.example.com'), HttpKernelInterface::MAIN_REQUEST));

    $this->assertEquals('Australia/Adelaide', date_default_timezone_get());

    $user = $this->createUser([]);
    $user->set('timezone', 'Australia/Lord_Howe');
    $user->save();

    $this->setCurrentUser($user);

    $this->assertEquals('Australia/Lord_Howe', date_default_timezone_get());

  }

}
