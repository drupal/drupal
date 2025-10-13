<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionStorage\SectionStorageDefinition;
use Drupal\layout_builder_test\Plugin\SectionStorage\SimpleConfigSectionStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the test implementation of section storage.
 */
#[CoversClass(SimpleConfigSectionStorage::class)]
#[Group('layout_builder')]
#[RunTestsInSeparateProcesses]
class SimpleConfigSectionListTest extends SectionListTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getSectionList(array $section_data) {
    $config = $this->container->get('config.factory')->getEditable('layout_builder_test.test_simple_config.foobar');
    $section_data = array_map(function (Section $section) {
      return $section->toArray();
    }, $section_data);
    $config->set('sections', $section_data)->save();

    $definition = new SectionStorageDefinition(['id' => 'test_simple_config']);
    $plugin = SimpleConfigSectionStorage::create($this->container, [], 'test_simple_config', $definition);
    $plugin->setContext('config_id', new Context(new ContextDefinition('string'), 'foobar'));
    return $plugin;
  }

}
