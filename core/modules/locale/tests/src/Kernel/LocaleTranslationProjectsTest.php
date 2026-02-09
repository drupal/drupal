<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests locale translation project handling.
 */
#[Group('locale')]
#[RunTestsInSeparateProcesses]
class LocaleTranslationProjectsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['locale', 'locale_test', 'system'];

  /**
   * The module handler used in this test.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The locale project storage used in this test.
   *
   * @var \Drupal\locale\LocaleProjectStorageInterface
   */
  protected $projectStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->moduleHandler = $this->container->get('module_handler');
    $this->projectStorage = $this->container->get('locale.project');
    \Drupal::state()->set('locale.remove_core_project', TRUE);
  }

  /**
   * Tests \Drupal\locale\LocaleProjectStorageInterface::resetCache().
   */
  public function testLocaleTranslationClearCacheProjects(): void {
    $expected = [];
    $this->assertSame($expected, \Drupal::service('locale.project')->getProjects());

    $this->projectStorage->set('foo', []);
    $expected['foo'] = new \stdClass();
    $this->assertEquals($expected, \Drupal::service('locale.project')->getProjects());

    $this->projectStorage->set('bar', []);
    \Drupal::service('locale.project')->resetCache();
    $expected['bar'] = new \stdClass();
    $this->assertEquals($expected, \Drupal::service('locale.project')->getProjects());
  }

}
