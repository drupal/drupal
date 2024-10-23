<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\layout_builder\Section;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests validation of entity_view_display entities.
 *
 * @group Entity
 * @group Validation
 */
class EntityViewDisplayValidationTest extends ConfigEntityValidationTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected bool $hasLabel = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'field', 'node', 'text', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('node');
    $this->createContentType(['type' => 'one']);
    $this->createContentType(['type' => 'two']);

    EntityTestBundle::create(['id' => 'one'])->save();
    EntityTestBundle::create(['id' => 'two'])->save();

    EntityViewMode::create([
      'id' => 'node.test',
      'label' => 'Test',
      'targetEntityType' => 'node',
    ])->save();

    $this->entity = $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getViewDisplay('node', 'one', 'test');
    $this->entity->save();
  }

  /**
   * Tests that the plugin ID of a Layout Builder section is validated.
   */
  public function testLayoutSectionPluginIdIsValidated(): void {
    $this->enableModules(['layout_builder', 'layout_discovery']);

    $this->entity = $this->container->get('entity_display.repository')
      ->getViewDisplay('user', 'user');
    $this->assertInstanceOf(LayoutEntityDisplayInterface::class, $this->entity);
    $this->entity->enableLayoutBuilder()->save();
    $sections = array_map(fn(Section $section) => $section->toArray(), $this->entity->getSections());
    $this->assertCount(1, $sections);
    $sections[0]['layout_id'] = 'non_existent';

    $this->entity->setThirdPartySetting('layout_builder', 'sections', $sections);
    $this->assertValidationErrors([
      'third_party_settings.layout_builder.sections.0.layout_id' => "The 'non_existent' plugin does not exist.",
    ]);
  }

  /**
   * Tests that the target bundle of the entity view display is checked.
   */
  public function testTargetBundleMustExist(): void {
    $this->entity->set('bundle', 'superhero');
    $this->assertValidationErrors([
      '' => "The 'bundle' property cannot be changed.",
      'bundle' => "The 'superhero' bundle does not exist on the 'node' entity type.",
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testImmutableProperties(array $valid_values = []): void {
    parent::testImmutableProperties([
      'id' => 'entity_test_with_bundle.two.full',
      'targetEntityType' => 'entity_test_with_bundle',
      'bundle' => 'two',
    ]);
  }

}
