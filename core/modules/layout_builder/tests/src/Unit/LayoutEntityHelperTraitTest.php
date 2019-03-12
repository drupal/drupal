<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\layout_builder\LayoutEntityHelperTrait
 *
 * @group layout_builder
 */
class LayoutEntityHelperTraitTest extends UnitTestCase {

  /**
   * @covers ::isEntityUsingFieldOverride
   *
   * @dataProvider providerTestIsEntityUsingFieldOverride
   *
   * @expectedDeprecation \Drupal\layout_builder\LayoutEntityHelperTrait::isEntityUsingFieldOverride() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Internal storage of overrides may change so the existence of the field does not necessarily guarantee an overridable entity. See https://www.drupal.org/node/3030609.
   *
   * @group legacy
   */
  public function testIsEntityUsingFieldOverride(EntityInterface $entity, $expected) {
    $test_class = new TestClass();
    $this->assertSame($expected, $test_class->isEntityUsingFieldOverride($entity));
  }

  /**
   * Dataprovider for testIsEntityUsingFieldOverride().
   */
  public function providerTestIsEntityUsingFieldOverride() {
    $data['non fieldable entity'] = [
      $this->prophesize(EntityInterface::class)->reveal(),
      FALSE,
    ];
    $fieldable_entity = $this->prophesize(FieldableEntityInterface::class);
    $fieldable_entity->hasField(OverridesSectionStorage::FIELD_NAME)->willReturn(FALSE);
    $data['fieldable entity without layout field'] = [
      $fieldable_entity->reveal(),
      FALSE,
    ];
    $entity_using_field = $this->prophesize(FieldableEntityInterface::class);
    $entity_using_field->hasField(OverridesSectionStorage::FIELD_NAME)->willReturn(TRUE);
    $data['fieldable entity with layout field'] = [
      $entity_using_field->reveal(),
      TRUE,
    ];
    return $data;
  }

  /**
   * Dataprovider method for tests that need sections with inline blocks.
   */
  public function providerSectionsWithInlineComponents() {
    $components = [];

    // Ensure a non-derivative component is not returned.
    $non_derivative_component = $this->prophesize(SectionComponent::class);
    $non_derivative_component->getPlugin()->willReturn($this->prophesize(PluginInspectionInterface::class)->reveal());
    $components[] = $non_derivative_component->reveal();

    // Ensure a derivative component with a different base Id is not returned.
    $derivative_non_inline_component = $this->prophesize(SectionComponent::class);
    $plugin = $this->prophesize(DerivativeInspectionInterface::class);
    $plugin->getBaseId()->willReturn('some_other_base_id_which_we_do_not_care_about_but_it_is_nothing_personal');
    $derivative_non_inline_component->getPlugin()->willReturn($plugin);
    $components[] = $derivative_non_inline_component->reveal();

    // Ensure that inline block component is returned.
    $inline_component = $this->prophesize(SectionComponent::class);
    $inline_plugin = $this->prophesize(DerivativeInspectionInterface::class)->willImplement(ConfigurablePluginInterface::class);
    $inline_plugin->getBaseId()->willReturn('inline_block');
    $inline_plugin->getConfiguration()->willReturn(['block_revision_id' => 'the_revision_id']);
    $inline_component->getPlugin()->willReturn($inline_plugin->reveal());
    $inline_component = $inline_component->reveal();
    $components[] = $inline_component;

    // Ensure that inline block component without revision is returned.
    $inline_component_without_revision_id = $this->prophesize(SectionComponent::class);
    $inline_plugin_without_revision_id = $this->prophesize(DerivativeInspectionInterface::class)->willImplement(ConfigurablePluginInterface::class);
    $inline_plugin_without_revision_id->getBaseId()->willReturn('inline_block');
    $inline_plugin_without_revision_id->getConfiguration()->willReturn(['other_key' => 'other_value']);
    $inline_component_without_revision_id->getPlugin()->willReturn($inline_plugin_without_revision_id->reveal());
    $inline_component_without_revision_id = $inline_component_without_revision_id->reveal();
    $components[] = $inline_component_without_revision_id;

    $section = $this->prophesize(Section::class);
    $section->getComponents()->willReturn($components);

    $components = [];
    // Ensure that inline block components in all sections are returned.
    $inline_component2 = $this->prophesize(SectionComponent::class);
    $inline_plugin2 = $this->prophesize(DerivativeInspectionInterface::class)->willImplement(ConfigurablePluginInterface::class);
    $inline_plugin2->getBaseId()->willReturn('inline_block');
    $inline_plugin2->getConfiguration()->willReturn(['block_revision_id' => 'the_other_revision_id']);
    $inline_component2->getPlugin()->willReturn($inline_plugin2->reveal());
    $inline_component2 = $inline_component2->reveal();
    $components[] = $inline_component2;

    $section2 = $this->prophesize(Section::class);
    $section2->getComponents()->willReturn($components);

    return [
      [
        [$section->reveal(), $section2->reveal()],
        // getInlineBlockComponents() should return inline blocks even if they
        // have no revision Id.
        [
          $inline_component,
          $inline_component_without_revision_id,
          $inline_component2,
        ],
        // getInlineBlockRevisionIdsInSections should just the revision Ids.
        ['the_revision_id', 'the_other_revision_id'],
      ],
    ];
  }

  /**
   * @covers ::getInlineBlockComponents
   *
   * @dataProvider providerSectionsWithInlineComponents
   */
  public function testGetInlineBlockComponents($sections, $expected_components) {
    $test_class = new TestClass();
    $this->assertSame($expected_components, $test_class->getInlineBlockComponents($sections));
  }

  /**
   * @covers ::getInlineBlockRevisionIdsInSections
   *
   * @dataProvider providerSectionsWithInlineComponents
   */
  public function testGetInlineBlockRevisionIdsInSections($sections, $components, $expected_revision_ids) {
    $test_class = new TestClass();
    $this->assertSame($expected_revision_ids, $test_class->getInlineBlockRevisionIdsInSections($sections));
  }

}

/**
 * Test class using the trait.
 */
class TestClass {
  use LayoutEntityHelperTrait {
    isEntityUsingFieldOverride as public;
    getInlineBlockComponents as public;
    getInlineBlockRevisionIdsInSections as public;
  }

}
