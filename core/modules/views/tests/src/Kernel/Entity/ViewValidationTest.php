<?php

namespace Drupal\Tests\views\Kernel\Entity;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\views\Entity\View;

/**
 * Tests validation of view entities.
 *
 * @group views
 */
class ViewValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = View::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $this->entity->save();
  }

  /**
   * @group legacy
   */
  public function testLabelsAreRequired(): void {
    $this->entity->set('label', NULL);
    $this->expectDeprecation('Saving a view without an explicit label is deprecated in drupal:10.2.0 and will raise an error in drupal:11.0.0. See https://www.drupal.org/node/3381669');
    $this->assertSame($this->entity->id(), $this->entity->label());
  }

}
