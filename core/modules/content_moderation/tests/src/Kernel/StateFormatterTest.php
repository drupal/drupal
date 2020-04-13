<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\Core\Render\RenderContext;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Test the state field formatter.
 *
 * @group content_moderation
 */
class StateFormatterTest extends KernelTestBase {

  use ContentModerationTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'workflows',
    'content_moderation',
    'entity_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig('content_moderation');

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_rev', 'entity_test_rev');
    $workflow->save();
  }

  /**
   * Test the embed field.
   *
   * @dataProvider formatterTestCases
   */
  public function testStateFieldFormatter($field_value, $formatter_settings, $expected_output) {
    $entity = EntityTestRev::create([
      'moderation_state' => $field_value,
    ]);
    $entity->save();

    $field_output = $this->container->get('renderer')->executeInRenderContext(new RenderContext(), function () use ($entity, $formatter_settings) {
      return $entity->moderation_state->view($formatter_settings);
    });

    $this->assertEquals($expected_output, $field_output[0]);
  }

  /**
   * Test cases for ::
   */
  public function formatterTestCases() {
    return [
      'Draft State' => [
        'draft',
        [
          'type' => 'content_moderation_state',
          'settings' => [],
        ],
        [
          '#markup' => 'Draft',
        ],
      ],
      'Published State' => [
        'published',
        [
          'type' => 'content_moderation_state',
          'settings' => [],
        ],
        [
          '#markup' => 'Published',
        ],
      ],
    ];
  }

}
