<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\PermissionHandlerTest.
 */

namespace Drupal\Tests\user\Unit;

use Drupal\Core\Extension\Extension;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\PermissionHandler;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Tests the permission handler.
 *
 * @group user
 *
 * @coversDefaultClass \Drupal\user\PermissionHandler
 */
class PermissionHandlerTest extends UnitTestCase {

  /**
   * The tested permission handler.
   *
   * @var \Drupal\Tests\user\Unit\TestPermissionHandler|\Drupal\user\PermissionHandler
   */
  protected $permissionHandler;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked string translation.
   *
   * @var \Drupal\Tests\user\Unit\TestTranslationManager
   */
  protected $stringTranslation;

  /**
   * The mocked controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $controllerResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->stringTranslation = new TestTranslationManager();
    $this->controllerResolver = $this->getMock('Drupal\Core\Controller\ControllerResolverInterface');
  }

  /**
   * Provides an extension object for a given module with a human name.
   *
   * @param string $module
   *   The module machine name.
   * @param string $name
   *   The module human name.
   *
   * @return \Drupal\Core\Extension\Extension
   *   The extension object.
   */
  protected function mockModuleExtension($module, $name) {
    $extension = new Extension($this->root, $module, "modules/$module");
    $extension->info['name'] = $name;
    return $extension;
  }

  /**
   * Tests permissions provided by YML files.
   *
   * @covers ::__construct
   * @covers ::getPermissions
   * @covers ::buildPermissionsYaml
   * @covers ::moduleProvidesPermissions
   */
  public function testBuildPermissionsYaml() {
    vfsStreamWrapper::register();
    $root = new vfsStreamDirectory('modules');
    vfsStreamWrapper::setRoot($root);

    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->moduleHandler->expects($this->once())
      ->method('getModuleDirectories')
      ->willReturn(array(
        'module_a' => vfsStream::url('modules/module_a'),
        'module_b' => vfsStream::url('modules/module_b'),
        'module_c' => vfsStream::url('modules/module_c'),
      ));

    $url = vfsStream::url('modules');
    mkdir($url . '/module_a');
    file_put_contents($url . '/module_a/module_a.permissions.yml',
"access_module_a: single_description"
    );
    mkdir($url . '/module_b');
    file_put_contents($url . '/module_b/module_b.permissions.yml',
"'access module b':
  title: 'Access B'
  description: 'bla bla'
'access module a via module b':
  title: 'Access A via B'
  provider: 'module_a'
");
    mkdir($url . '/module_c');
    file_put_contents($url . '/module_c/module_c.permissions.yml',
"'access_module_c':
  title: 'Access C'
  description: 'bla bla'
  'restrict access': TRUE
");
    $modules = array('module_a', 'module_b', 'module_c');
    $extensions = array(
      'module_a' => $this->mockModuleExtension('module_a', 'Module a'),
      'module_b' => $this->mockModuleExtension('module_b', 'Module b'),
      'module_c' => $this->mockModuleExtension('module_c', 'Module c'),
    );
    $this->moduleHandler->expects($this->any())
      ->method('getImplementations')
      ->with('permission')
      ->willReturn(array());

    $this->moduleHandler->expects($this->any())
      ->method('getModuleList')
      ->willReturn(array_flip($modules));

    $this->controllerResolver->expects($this->never())
      ->method('getControllerFromDefinition');

    $this->permissionHandler = new TestPermissionHandler($this->moduleHandler, $this->stringTranslation, $this->controllerResolver);

    // Setup system_rebuild_module_data().
    $this->permissionHandler->setSystemRebuildModuleData($extensions);

    $actual_permissions = $this->permissionHandler->getPermissions();
    $this->assertPermissions($actual_permissions);

    $this->assertTrue($this->permissionHandler->moduleProvidesPermissions('module_a'));
    $this->assertTrue($this->permissionHandler->moduleProvidesPermissions('module_b'));
    $this->assertTrue($this->permissionHandler->moduleProvidesPermissions('module_c'));
    $this->assertFalse($this->permissionHandler->moduleProvidesPermissions('module_d'));
  }

  /**
   * Tests permissions sort inside a module.
   *
   * @covers ::__construct
   * @covers ::getPermissions
   * @covers ::buildPermissionsYaml
   * @covers ::sortPermissions
   */
  public function testBuildPermissionsSortPerModule() {
    vfsStreamWrapper::register();
    $root = new vfsStreamDirectory('modules');
    vfsStreamWrapper::setRoot($root);

    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->moduleHandler->expects($this->once())
      ->method('getModuleDirectories')
      ->willReturn([
        'module_a' => vfsStream::url('modules/module_a'),
        'module_b' => vfsStream::url('modules/module_b'),
        'module_c' => vfsStream::url('modules/module_c'),
      ]);
    $this->moduleHandler->expects($this->exactly(3))
      ->method('getName')
      ->will($this->returnValueMap([
        ['module_a', 'Module a'],
        ['module_b', 'Module b'],
        ['module_c', 'A Module'],
      ]));

    $url = vfsStream::url('modules');
    mkdir($url . '/module_a');
    file_put_contents($url . '/module_a/module_a.permissions.yml',
"access_module_a2: single_description2
access_module_a1: single_description1"
    );
    mkdir($url . '/module_b');
    file_put_contents($url . '/module_b/module_b.permissions.yml',
      "access_module_a3: single_description"
    );
    mkdir($url . '/module_c');
    file_put_contents($url . '/module_c/module_c.permissions.yml',
      "access_module_a4: single_description"
    );

    $modules = ['module_a', 'module_b', 'module_c'];
    $this->moduleHandler->expects($this->once())
      ->method('getModuleList')
      ->willReturn(array_flip($modules));

    $permissionHandler = new TestPermissionHandler($this->moduleHandler, $this->stringTranslation, $this->controllerResolver);
    $actual_permissions = $permissionHandler->getPermissions();
    $this->assertEquals(['access_module_a4', 'access_module_a1', 'access_module_a2', 'access_module_a3'],
      array_keys($actual_permissions));
  }

  /**
   * Tests dynamic callback permissions provided by YML files.
   *
   * @covers ::__construct
   * @covers ::getPermissions
   * @covers ::buildPermissionsYaml
   */
  public function testBuildPermissionsYamlCallback() {
    vfsStreamWrapper::register();
    $root = new vfsStreamDirectory('modules');
    vfsStreamWrapper::setRoot($root);

    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->moduleHandler->expects($this->once())
      ->method('getModuleDirectories')
      ->willReturn(array(
        'module_a' => vfsStream::url('modules/module_a'),
        'module_b' => vfsStream::url('modules/module_b'),
        'module_c' => vfsStream::url('modules/module_c'),
      ));

    $url = vfsStream::url('modules');
    mkdir($url . '/module_a');
    file_put_contents($url . '/module_a/module_a.permissions.yml',
"permission_callbacks:
  - 'Drupal\\user\\Tests\\TestPermissionCallbacks::singleDescription'
");
    mkdir($url . '/module_b');
    file_put_contents($url . '/module_b/module_b.permissions.yml',
"permission_callbacks:
  - 'Drupal\\user\\Tests\\TestPermissionCallbacks::titleDescription'
  - 'Drupal\\user\\Tests\\TestPermissionCallbacks::titleProvider'
");
    mkdir($url . '/module_c');
    file_put_contents($url . '/module_c/module_c.permissions.yml',
"permission_callbacks:
  - 'Drupal\\user\\Tests\\TestPermissionCallbacks::titleDescriptionRestrictAccess'
");

    $modules = array('module_a', 'module_b', 'module_c');
    $extensions = array(
      'module_a' => $this->mockModuleExtension('module_a', 'Module a'),
      'module_b' => $this->mockModuleExtension('module_b', 'Module b'),
      'module_c' => $this->mockModuleExtension('module_c', 'Module c'),
    );

    $this->moduleHandler->expects($this->any())
      ->method('getImplementations')
      ->with('permission')
      ->willReturn(array());

    $this->moduleHandler->expects($this->any())
      ->method('getModuleList')
      ->willReturn(array_flip($modules));

    $this->controllerResolver->expects($this->at(0))
      ->method('getControllerFromDefinition')
      ->with('Drupal\\user\\Tests\\TestPermissionCallbacks::singleDescription')
      ->willReturn(array(new TestPermissionCallbacks(), 'singleDescription'));
    $this->controllerResolver->expects($this->at(1))
      ->method('getControllerFromDefinition')
      ->with('Drupal\\user\\Tests\\TestPermissionCallbacks::titleDescription')
      ->willReturn(array(new TestPermissionCallbacks(), 'titleDescription'));
    $this->controllerResolver->expects($this->at(2))
      ->method('getControllerFromDefinition')
      ->with('Drupal\\user\\Tests\\TestPermissionCallbacks::titleProvider')
      ->willReturn(array(new TestPermissionCallbacks(), 'titleProvider'));
    $this->controllerResolver->expects($this->at(3))
      ->method('getControllerFromDefinition')
      ->with('Drupal\\user\\Tests\\TestPermissionCallbacks::titleDescriptionRestrictAccess')
      ->willReturn(array(new TestPermissionCallbacks(), 'titleDescriptionRestrictAccess'));

    $this->permissionHandler = new TestPermissionHandler($this->moduleHandler, $this->stringTranslation, $this->controllerResolver);

    // Setup system_rebuild_module_data().
    $this->permissionHandler->setSystemRebuildModuleData($extensions);

    $actual_permissions = $this->permissionHandler->getPermissions();
    $this->assertPermissions($actual_permissions);
  }

  /**
   * Tests a YAML file containing both static permissions and a callback.
   */
  public function testPermissionsYamlStaticAndCallback() {
    vfsStreamWrapper::register();
    $root = new vfsStreamDirectory('modules');
    vfsStreamWrapper::setRoot($root);

    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->moduleHandler->expects($this->once())
      ->method('getModuleDirectories')
      ->willReturn(array(
        'module_a' => vfsStream::url('modules/module_a'),
      ));

    $url = vfsStream::url('modules');
    mkdir($url . '/module_a');
    file_put_contents($url . '/module_a/module_a.permissions.yml',
"'access module a':
  title: 'Access A'
  description: 'bla bla'
permission_callbacks:
  - 'Drupal\\user\\Tests\\TestPermissionCallbacks::titleDescription'
");

    $modules = array('module_a');
    $extensions = array(
      'module_a' => $this->mockModuleExtension('module_a', 'Module a'),
    );

    $this->moduleHandler->expects($this->any())
      ->method('getImplementations')
      ->with('permission')
      ->willReturn(array());

    $this->moduleHandler->expects($this->any())
      ->method('getModuleList')
      ->willReturn(array_flip($modules));

    $this->controllerResolver->expects($this->once())
      ->method('getControllerFromDefinition')
      ->with('Drupal\\user\\Tests\\TestPermissionCallbacks::titleDescription')
      ->willReturn(array(new TestPermissionCallbacks(), 'titleDescription'));

    $this->permissionHandler = new TestPermissionHandler($this->moduleHandler, $this->stringTranslation, $this->controllerResolver);

    // Setup system_rebuild_module_data().
    $this->permissionHandler->setSystemRebuildModuleData($extensions);

    $actual_permissions = $this->permissionHandler->getPermissions();

    $this->assertCount(2, $actual_permissions);
    $this->assertEquals($actual_permissions['access module a']['title'], 'Access A');
    $this->assertEquals($actual_permissions['access module a']['provider'], 'module_a');
    $this->assertEquals($actual_permissions['access module a']['description'], 'bla bla');
    $this->assertEquals($actual_permissions['access module b']['title'], 'Access B');
    $this->assertEquals($actual_permissions['access module b']['provider'], 'module_a');
    $this->assertEquals($actual_permissions['access module b']['description'], 'bla bla');
  }

  /**
   * Checks that the permissions are like expected.
   *
   * @param array $actual_permissions
   *   The actual permissions
   */
  protected function assertPermissions(array $actual_permissions) {
    $this->assertCount(4, $actual_permissions);
    $this->assertEquals($actual_permissions['access_module_a']['title'], 'single_description');
    $this->assertEquals($actual_permissions['access_module_a']['provider'], 'module_a');
    $this->assertEquals($actual_permissions['access module b']['title'], 'Access B');
    $this->assertEquals($actual_permissions['access module b']['provider'], 'module_b');
    $this->assertEquals($actual_permissions['access_module_c']['title'], 'Access C');
    $this->assertEquals($actual_permissions['access_module_c']['provider'], 'module_c');
    $this->assertEquals($actual_permissions['access_module_c']['restrict access'], TRUE);
    $this->assertEquals($actual_permissions['access module a via module b']['provider'], 'module_a');
  }

}

class TestPermissionHandler extends PermissionHandler {

  /**
   * Test module data.
   *
   * @var array
   */
  protected $systemModuleData;

  protected function systemRebuildModuleData() {
    return $this->systemModuleData;
  }

  public function setSystemRebuildModuleData(array $extensions) {
    $this->systemModuleData = $extensions;
  }

}

class TestPermissionCallbacks {

  public function singleDescription() {
    return array(
      'access_module_a' => 'single_description'
    );
  }

  public function titleDescription() {
    return array(
      'access module b' => array(
        'title' => 'Access B',
        'description' => 'bla bla',
      ),
    );
  }

  public function titleDescriptionRestrictAccess() {
    return array(
      'access_module_c' => array(
        'title' => 'Access C',
        'description' => 'bla bla',
        'restrict access' => TRUE,
      ),
    );
  }

  public function titleProvider() {
    return array(
      'access module a via module b' => array(
        'title' => 'Access A via B',
        'provider' => 'module_a',
      ),
    );
  }
}

/**
 * Implements a translation manager in tests.
 */
class TestTranslationManager implements TranslationInterface {

  /**
   * {@inheritdoc}
   */
  public function translate($string, array $args = array(), array $options = array()) {
    return new TranslatableMarkup($string, $args, $options, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function translateString(TranslatableMarkup $translated_string) {
    return $translated_string->getUntranslatedString();
  }

  /**
   * {@inheritdoc}
   */
  public function formatPlural($count, $singular, $plural, array $args = array(), array $options = array()) {
    return new PluralTranslatableMarkup($count, $singular, $plural, $args, $options, $this);
  }

}
