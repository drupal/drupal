<?php

namespace Drupal\Tests\Core\ClassLoader;

use Composer\Autoload\ClassLoader;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @group ClassLoader
 * @group legacy
 * @runTestsInSeparateProcesses
 */
class ClassLoaderTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('app.root', $this->root);
    $class_loader = $this->prophesize(ClassLoader::class);
    $class_loader->addPsr4('Drupal\\foo\\', $this->root . '/modules/bar/src')->shouldBeCalled();
    $container->set('class_loader', $class_loader->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @expectedDeprecation drupal_classloader_register() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Use the method ::addPsr4() of the class_loader service to register the namespace. See https://www.drupal.org/node/3035275.
   * @see drupal_classloader_register()
   */
  public function testDrupalClassloadeRegisterDeprecation() {
    include_once $this->root . '/core/includes/bootstrap.inc';
    drupal_classloader_register('foo', 'modules/bar');
  }

  /**
   * @expectedDeprecation system_register() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. There is no replacement for this function. To achieve the same functionality use this snippet: $path = \Drupal::service("extension.list.$type")->getPath($name); \Drupal::service('class_loader')->addPsr4('Drupal\\' . $name . '\\', \Drupal::root() . '/' . $path . '/src'); See https://www.drupal.org/node/3035275.
   * @see system_register()
   */
  public function testSystemRegisterDeprecation() {
    include_once $this->root . '/core/includes/module.inc';
    system_register('module', 'foo', 'modules/bar/foo.module');
  }

}
