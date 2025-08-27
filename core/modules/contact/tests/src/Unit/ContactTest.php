<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Unit;

use Drupal\contact\Hook\ContactHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for Contact hooks.
 */
#[Group('contact')]
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
