<?php

namespace Drupal\Tests\content_translation\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests content translation local tasks.
 *
 * @group content_translation
 */
class ContentTranslationLocalTasksTest extends LocalTaskIntegrationTestBase {

  protected function setUp() {
    $this->directoryList = [
      'content_translation' => 'core/modules/content_translation',
      'node' => 'core/modules/node',
    ];
    parent::setUp();

    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getLinkTemplate')
      ->will($this->returnValueMap([
        ['canonical', 'entity.node.canonical'],
        ['drupal:content-translation-overview', 'entity.node.content_translation_overview'],
      ]));
    $content_translation_manager = $this->getMock('Drupal\content_translation\ContentTranslationManagerInterface');
    $content_translation_manager->expects($this->any())
      ->method('getSupportedEntityTypes')
      ->will($this->returnValue([
        'node' => $entity_type,
      ]));
    \Drupal::getContainer()->set('content_translation.manager', $content_translation_manager);
    \Drupal::getContainer()->set('string_translation', $this->getStringTranslationStub());
  }

  /**
   * Tests the block admin display local tasks.
   *
   * @dataProvider providerTestBlockAdminDisplay
   */
  public function testBlockAdminDisplay($route, $expected) {
    $this->assertLocalTasks($route, $expected);
  }

  /**
   * Provides a list of routes to test.
   */
  public function providerTestBlockAdminDisplay() {
    return [
      [
        'entity.node.canonical',
        [
          [
            'content_translation.local_tasks:entity.node.content_translation_overview',
            'entity.node.canonical',
            'entity.node.edit_form',
            'entity.node.delete_form',
            'entity.node.version_history',
          ],
        ],
      ],
      [
        'entity.node.content_translation_overview',
        [
          [
            'content_translation.local_tasks:entity.node.content_translation_overview',
            'entity.node.canonical',
            'entity.node.edit_form',
            'entity.node.delete_form',
            'entity.node.version_history',
          ],
        ],
      ],
    ];
  }

}
