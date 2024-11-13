<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Entity;

use Drupal\Core\Entity\Attribute\EntityType;
use Drupal\Core\Plugin\Discovery\AttributeClassDiscovery;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that entity type labels use sentence-case.
 *
 * @group Entity
 */
class EntityLabelTest extends KernelTestBase {

  /**
   * Tests that entity type labels use sentence-case.
   */
  public function testEntityLabelCasing(): void {
    $base_directory = $this->root . '/core/modules/';
    $modules = scandir($base_directory);
    $paths = [];
    foreach ($modules as $module) {
      $paths["Drupal\\{$module}"] = $base_directory . $module . '/src/';
    }
    $namespaces = new \ArrayObject($paths);
    $discovery = new AttributeClassDiscovery('Entity', $namespaces, EntityType::class);
    $definitions = $discovery->getDefinitions();

    foreach ($definitions as $definition) {
      /** @var \Drupal\Core\Entity\EntityType $definition */

      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
      $label = $definition->getLabel();
      $collection_label = $definition->getCollectionLabel();

      $label_string = $label->getUntranslatedString();
      $collection_label_string = $collection_label->getUntranslatedString();

      // Keep the first word as it is for nouns that are all capital letters
      // (like RDF, URL alias etc.) so we can't run strtolower() for the entire
      // string. Special cases may need to be added to this test in the future
      // if an acronym is in a different position in the label.
      $first_word = strtok($label_string, " ");
      $remaining_string = strtolower((string) strstr($label_string, " "));
      $this->assertEquals($first_word . $remaining_string, $label_string);

      $first_word = strtok($collection_label_string, " ");
      $remaining_string = strtolower((string) strstr($collection_label_string, " "));
      $this->assertEquals($first_word . $remaining_string, $collection_label_string);
    }
  }

}
