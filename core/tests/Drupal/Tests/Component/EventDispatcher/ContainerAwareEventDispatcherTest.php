<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\EventDispatcher\ContainerAwareEventDispatcherTest
 */

namespace Drupal\Tests\Component\EventDispatcher;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Tests\AbstractEventDispatcherTest;
use Symfony\Component\EventDispatcher\Tests\CallableClass;
use Symfony\Component\EventDispatcher\Tests\TestEventListener;

/**
 * Unit tests for the ContainerAwareEventDispatcher.
 *
 * NOTE: 98% of this code is a literal copy of Symfony's emerging
 * CompiledEventDispatcherTest.
 *
 * This file does NOT follow Drupal coding standards, so as to simplify future
 * synchronizations.
 *
 * @see https://github.com/symfony/symfony/pull/12521
 *
 * @group Symfony
 */
class ContainerAwareEventDispatcherTest extends AbstractEventDispatcherTest
{
    protected function createEventDispatcher()
    {
        $container = new Container();

        return new ContainerAwareEventDispatcher($container);
    }

    public function testGetListenersWithCallables()
    {
        // When passing in callables exclusively as listeners into the event
        // dispatcher constructor, the event dispatcher must not attempt to
        // resolve any services.
        $container = $this->getMock('Symfony\Component\DependencyInjection\IntrospectableContainerInterface');
        $container->expects($this->never())->method($this->anything());

        $firstListener = new CallableClass();
        $secondListener = function () {};
        $thirdListener = array(new TestEventListener(), 'preFoo');
        $listeners = array(
            'test_event' => array(
                0 => array(
                    array('callable' => $firstListener),
                    array('callable' => $secondListener),
                    array('callable' => $thirdListener),
                ),
            ),
        );

        $dispatcher = new ContainerAwareEventDispatcher($container, $listeners);
        $actualListeners = $dispatcher->getListeners();

        $expectedListeners = array(
            'test_event' => array(
                $firstListener,
                $secondListener,
                $thirdListener,
            ),
        );

        $this->assertSame($expectedListeners, $actualListeners);
    }

    public function testDispatchWithCallables()
    {
        // When passing in callables exclusively as listeners into the event
        // dispatcher constructor, the event dispatcher must not attempt to
        // resolve any services.
        $container = $this->getMock('Symfony\Component\DependencyInjection\IntrospectableContainerInterface');
        $container->expects($this->never())->method($this->anything());

        $firstListener = new CallableClass();
        $secondListener = function () {};
        $thirdListener = array(new TestEventListener(), 'preFoo');
        $listeners = array(
            'test_event' => array(
                0 => array(
                    array('callable' => $firstListener),
                    array('callable' => $secondListener),
                    array('callable' => $thirdListener),
                ),
            ),
        );

        $dispatcher = new ContainerAwareEventDispatcher($container, $listeners);
        $dispatcher->dispatch('test_event');

        $this->assertTrue($thirdListener[0]->preFooInvoked);
    }

    public function testGetListenersWithServices()
    {
        $container = new ContainerBuilder();
        $container->register('listener_service', 'Symfony\Component\EventDispatcher\Tests\TestEventListener');

        $listeners = array(
            'test_event' => array(
                0 => array(
                    array('service' => array('listener_service', 'preFoo')),
                ),
            ),
        );

        $dispatcher = new ContainerAwareEventDispatcher($container, $listeners);
        $actualListeners = $dispatcher->getListeners();

        $listenerService = $container->get('listener_service');
        $expectedListeners = array(
            'test_event' => array(
                array($listenerService, 'preFoo'),
            ),
        );

        $this->assertSame($expectedListeners, $actualListeners);
    }

    public function testDispatchWithServices()
    {
        $container = new ContainerBuilder();
        $container->register('listener_service', 'Symfony\Component\EventDispatcher\Tests\TestEventListener');

        $listeners = array(
            'test_event' => array(
                0 => array(
                    array('service' => array('listener_service', 'preFoo')),
                ),
            ),
        );

        $dispatcher = new ContainerAwareEventDispatcher($container, $listeners);

        $dispatcher->dispatch('test_event');

        $listenerService = $container->get('listener_service');
        $this->assertTrue($listenerService->preFooInvoked);
    }

    public function testRemoveService()
    {
        $container = new ContainerBuilder();
        $container->register('listener_service', 'Symfony\Component\EventDispatcher\Tests\TestEventListener');
        $container->register('other_listener_service', 'Symfony\Component\EventDispatcher\Tests\TestEventListener');

        $listeners = array(
            'test_event' => array(
                0 => array(
                    array('service' => array('listener_service', 'preFoo')),
                    array('service' => array('other_listener_service', 'preFoo')),
                ),
            ),
        );

        $dispatcher = new ContainerAwareEventDispatcher($container, $listeners);

        $listenerService = $container->get('listener_service');
        $dispatcher->removeListener('test_event', array($listenerService, 'preFoo'));

        // Ensure that other service was not initialized during removal of the
        // listener service.
        $this->assertFalse($container->initialized('other_listener_service'));

        $dispatcher->dispatch('test_event');

        $this->assertFalse($listenerService->preFooInvoked);
        $otherService = $container->get('other_listener_service');
        $this->assertTrue($otherService->preFooInvoked);
    }
}
