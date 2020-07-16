<?php

namespace Drupal\Tests\field\Kernel\String;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the creation of text fields.
 *
 * @group field
 */
class StringFormatterTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'text',
    'entity_test',
    'system',
    'filter',
    'user',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var string
   */
  protected $entityType;

  /**
   * @var string
   */
  protected $bundle;

  /**
   * @var string
   */
  protected $fieldName;

  /**
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Configure the theme system.
    $this->installConfig(['system', 'field']);
    $this->installEntitySchema('entity_test_rev');

    $this->entityType = 'entity_test_rev';
    $this->bundle = $this->entityType;
    $this->fieldName = mb_strtolower($this->randomMachineName());

    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityType,
      'type' => 'string',
    ]);
    $field_storage->save();

    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->bundle,
      'label' => $this->randomMachineName(),
    ]);
    $instance->save();

    $this->display = \Drupal::service('entity_display.repository')
      ->getViewDisplay($this->entityType, $this->bundle)
      ->setComponent($this->fieldName, [
        'type' => 'string',
        'settings' => [],
      ]);
    $this->display->save();

    $this->entityTypeManager = \Drupal::entityTypeManager();
  }

  /**
   * Renders fields of a given entity with a given display.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity object with attached fields to render.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display to render the fields in.
   *
   * @return string
   *   The rendered entity fields.
   */
  protected function renderEntityFields(FieldableEntityInterface $entity, EntityViewDisplayInterface $display) {
    $content = $display->build($entity);
    $content = $this->render($content);
    return $content;
  }

  /**
   * Tests string formatter output.
   */
  public function testStringFormatter() {
    $value = $this->randomString();
    $value .= "\n\n<strong>" . $this->randomString() . '</strong>';
    $value .= "\n\n" . $this->randomString();

    $entity = EntityTestRev::create([]);
    $entity->{$this->fieldName}->value = $value;

    // Verify that all HTML is escaped and newlines are retained.
    $this->renderEntityFields($entity, $this->display);
    $this->assertNoRaw($value);
    $this->assertRaw(nl2br(Html::escape($value)));

    // Verify the cache tags.
    $build = $entity->{$this->fieldName}->view();
    $this->assertTrue(!isset($build[0]['#cache']), 'The string formatter has no cache tags.');

    $value = $this->randomMachineName();
    $entity->{$this->fieldName}->value = $value;
    $entity->save();

    // Set the formatter to link to the entity.
    $this->display->setComponent($this->fieldName, [
      'type' => 'string',
      'settings' => [
        'link_to_entity' => TRUE,
      ],
    ]);
    $this->display->save();

    $this->renderEntityFields($entity, $this->display);
    $this->assertLink($value, 0);
    $this->assertLinkByHref($entity->toUrl()->toString());

    // $entity->toUrl('revision') falls back to the canonical URL if this is no
    // revision.
    $this->assertLinkByHref($entity->toUrl('revision')->toString());

    // Make the entity a new revision.
    $old_revision_id = $entity->getRevisionId();
    $entity->setNewRevision(TRUE);
    $value2 = $this->randomMachineName();
    $entity->{$this->fieldName}->value = $value2;
    $entity->save();
    $entity_new_revision = $this->entityTypeManager->getStorage('entity_test_rev')->loadRevision($old_revision_id);

    $this->renderEntityFields($entity, $this->display);
    $this->assertLink($value2, 0);
    $this->assertLinkByHref($entity->toUrl('revision')->toString());

    $this->renderEntityFields($entity_new_revision, $this->display);
    $this->assertLink($value, 0);
    $this->assertLinkByHref('/entity_test_rev/' . $entity_new_revision->id() . '/revision/' . $entity_new_revision->getRevisionId() . '/view');

    // Check that linking to a revisionable entity works if the entity type does
    // not specify a 'revision' link template.
    $entity_type = clone $this->entityTypeManager->getDefinition('entity_test_rev');
    $link_templates = $entity_type->getLinkTemplates();
    unset($link_templates['revision']);
    $entity_type->set('links', $link_templates);
    \Drupal::state()->set('entity_test_rev.entity_type', $entity_type);
    $this->entityTypeManager->clearCachedDefinitions();

    $this->renderEntityFields($entity_new_revision, $this->display);
    $this->assertLink($value, 0);
    $this->assertLinkByHref($entity->toUrl('canonical')->toString());
  }

}
