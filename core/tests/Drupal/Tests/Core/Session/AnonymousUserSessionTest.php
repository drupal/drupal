<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Session\AnonymousUserSessionTest.
 */

namespace Drupal\Tests\Core\Session;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Session\AnonymousUserSession;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Scope;

/**
 * Tests the AnonymousUserSession class.
 *
 * @group Drupal
 *
 * @coversDefaultClass \Drupal\Core\Session\AnonymousUserSession
 */
class AnonymousUserSessionTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Anonymous user session object',
      'description' => 'Tests the anonymous user session object.',
      'group' => 'Session',
    );
  }

  /**
   * Tests creating an AnonymousUserSession when the request is available.
   *
   * @covers ::__construct()
   */
  public function testAnonymousUserSessionWithRequest() {
    $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
    $request->expects($this->once())
      ->method('getClientIp')
      ->will($this->returnValue('test'));
    $container = new ContainerBuilder();
    $container->addScope(new Scope('request'));
    $container->enterScope('request');
    $container->set('request', $request, 'request');
    \Drupal::setContainer($container);

    $anonymous_user = new AnonymousUserSession();

    $this->assertSame('test', $anonymous_user->getHostname());
  }

  /**
   * Tests creating an AnonymousUserSession when the request is not available.
   *
   * @covers ::__construct()
   */
  public function testAnonymousUserSessionWithNoRequest() {
    $container = new ContainerBuilder();

    // Set a synthetic 'request' definition on the container.
    $definition = new Definition();
    $definition->setSynthetic(TRUE);

    $container->setDefinition('request', $definition);
    \Drupal::setContainer($container);

    $anonymous_user = new AnonymousUserSession();

    $this->assertSame('', $anonymous_user->getHostname());
  }

}
