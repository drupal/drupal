<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @group contact
 */
class ContactTest extends UnitTestCase {

  /**
   * Test contact_menu_local_tasks_alter doesn't throw warnings.
   */
  public function testLocalTasksAlter(): void {
    require_once $this->root . '/core/modules/contact/contact.module';
    $data = [];
    \contact_menu_local_tasks_alter($data, 'entity.user.canonical');
    $this->assertTrue(TRUE, 'No warning thrown');
  }

}
