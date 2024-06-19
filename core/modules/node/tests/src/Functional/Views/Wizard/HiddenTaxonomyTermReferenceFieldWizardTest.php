<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional\Views\Wizard;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\views\Functional\Wizard\WizardTestBase;

/**
 * Tests node wizard and content type with hidden Taxonomy Term Reference field.
 *
 * @group Views
 * @group node
 */
class HiddenTaxonomyTermReferenceFieldWizardTest extends WizardTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['taxonomy'];

  /**
   * Tests content type with a hidden Taxonomy Term Reference field.
   */
  public function testHiddenTaxonomyTermReferenceField(): void {
    // Create Article node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Create a taxonomy_term_reference field on the article Content Type. By
    // not assigning a widget to that field we make sure it is hidden on the
    // Form Display.
    $field_name = $this->randomMachineName();
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'bundle' => 'article',
      'entity_type' => 'node',
      'settings' => [
        'handler' => 'default',
      ],
    ])->save();

    $this->drupalGet('admin/structure/views/add');
    $this->assertSession()->statusCodeEquals(200);
  }

}
