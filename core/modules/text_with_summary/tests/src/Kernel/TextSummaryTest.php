<?php

declare(strict_types=1);

namespace Drupal\Tests\text_with_summary\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests text_summary() with different strings and lengths.
 */
#[Group('text_with_summary')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class TextSummaryTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'filter',
    'text_with_summary',
    'text',
    'field',
    'entity_test',
  ];

  /**
   * Tests required summary.
   */
  public function testRequiredSummary(): void {
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->setUpCurrentUser();
    $field_definition = FieldStorageConfig::create([
      'field_name' => 'test_text_with_summary',
      'type' => 'text_with_summary',
      'entity_type' => 'entity_test',
      'cardinality' => 1,
      'settings' => [
        'max_length' => 200,
      ],
    ]);
    $field_definition->save();

    $instance = FieldConfig::create([
      'field_name' => 'test_text_with_summary',
      'label' => 'A text field',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' => [
        'text_processing' => TRUE,
        'display_summary' => TRUE,
        'required_summary' => TRUE,
      ],
    ]);
    $instance->save();

    EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('test_text_with_summary', [
      'type' => 'text_textarea_with_summary',
      'settings' => [
        'summary_rows' => 2,
        'show_summary' => TRUE,
      ],
    ])
      ->save();

    // Check the required summary.
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      'type' => 'entity_test',
      'test_text_with_summary' => ['value' => $this->randomMachineName()],
    ]);
    $form = \Drupal::service('entity.form_builder')->getForm($entity);
    $this->assertNotEmpty($form['test_text_with_summary']['widget'][0]['summary'], 'Summary field is shown');
    $this->assertNotEmpty($form['test_text_with_summary']['widget'][0]['summary']['#required'], 'Summary field is required');

    // Test validation.
    /** @var \Symfony\Component\Validator\ConstraintViolation[] $violations */
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('test_text_with_summary.0.summary', $violations[0]->getPropertyPath());
    $this->assertEquals('The summary field is required for A text field', $violations[0]->getMessage());
  }

}
