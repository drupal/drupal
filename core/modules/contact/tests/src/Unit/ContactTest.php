<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\contact\Hook\ContactHooks;

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
    $contactMenuLocalTasksAlter = new ContactHooks();
    $contactMenuLocalTasksAlter->menuLocalTasksAlter($data, 'entity.user.canonical');
    $this->assertTrue(TRUE, 'No warning thrown');
  }

}
