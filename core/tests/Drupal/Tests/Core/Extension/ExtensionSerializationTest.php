<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Extension\Extension;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use org\bovigo\vfs\vfsStream;

/**
 * Tests Extension serialization.
 *
 * @coversDefaultClass \Drupal\Core\Extension\Extension
 * @group Extension
 */
class ExtensionSerializationTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    vfsStream::setup('dummy_app_root');
    vfsStream::create([
      'core' => [
        'modules' => [
          'system' => [
            'system.info.yml' => file_get_contents($this->root . '/core/modules/system/system.info.yml'),
          ],
        ],
      ],
    ]);
  }

  /**
   * Tests that the Extension class unserialize method uses the preferred root.
   *
   * When the Extension unserialize method is called on serialized Extension
   * object data, test that the Extension object's root property is set to the
   * container's app.root and not the DRUPAL_ROOT constant if the service
   * container app.root is available.
   *
   * @covers ::__sleep
   * @covers ::__wakeup
   */
  public function testServiceAppRouteUsage() {
    $container = new ContainerBuilder();
    // Set a dummy container app.root to test against.
    $container->setParameter('app.root', 'vfs://dummy_app_root');
    \Drupal::setContainer($container);
    // Instantiate an Extension object for testing unserialization.
    $extension = new Extension($container->getParameter('app.root'), 'module', 'core/modules/system/system.info.yml', 'system.module');
    $extension = unserialize(serialize($extension));
    $reflected_root = new \ReflectionProperty($extension, 'root');
    $this->assertEquals('vfs://dummy_app_root', $reflected_root->getValue($extension));

    // Change the app root and test serializing and unserializing again.
    $container->setParameter('app.root', 'vfs://dummy_app_root2');
    \Drupal::setContainer($container);
    $extension = unserialize(serialize($extension));
    $reflected_root = new \ReflectionProperty($extension, 'root');
    $this->assertEquals('vfs://dummy_app_root2', $reflected_root->getValue($extension));
  }

  /**
   * Tests dynamically assigned public properties kept when serialized.
   *
   * @covers ::__sleep
   * @covers ::__wakeup
   */
  public function testPublicProperties() {
    $container = new ContainerBuilder();
    // Set a dummy container app.root to test against.
    $container->setParameter('app.root', 'vfs://dummy_app_root');
    \Drupal::setContainer($container);
    $extension = new Extension($container->getParameter('app.root'), 'module', 'core/modules/system/system.info.yml', 'system.module');
    // Assign a public property dynamically.
    $extension->test = 'foo';
    $extension = unserialize(serialize($extension));
    $this->assertSame('foo', $extension->test);
  }

}
