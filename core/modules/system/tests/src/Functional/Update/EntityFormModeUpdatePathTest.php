<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update path for the entity form mode description value from '' to NULL.
 *
 * @group system
 */
class EntityFormModeUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/remove-description-from-user-register-form-mode.php',
    ];
  }

  /**
   * Tests update path for the entity form mode description value from '' to NULL.
   */
  public function testRunUpdates(): void {
    $form_mode = EntityFormMode::load('user.register');
    $this->assertInstanceOf(EntityFormMode::class, $form_mode);
    $this->assertSame("\n", $form_mode->get('description'));
    $this->assertSame("\n", $form_mode->getDescription());
    $this->runUpdates();

    $form_mode = EntityFormMode::load('user.register');
    $this->assertInstanceOf(EntityFormMode::class, $form_mode);

    $this->assertNull($form_mode->get('description'));
    // Assert backward compatibility of EntityFormMode::getDescription().
    $this->assertSame('', $form_mode->getDescription());
  }

}
