<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Batch\BatchNotFoundTest.
 */

namespace Drupal\Tests\Core\Batch;

use Drupal\simpletest\WebTestBase;

/**
 * Tests if Drupal returns page not found error when batch ID does not exist.
 *
 * @group Batch
 */
class BatchNotFoundTest extends WebTestBase {

  /**
   * The main user for testing.
   *
   * @var object
   */
  protected $userToBeDeleted;

  /**
   * Administrator user.
   *
   * @var object
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(array('administer users'));;
    $this->userToBeDeleted = $this->drupalCreateUser();
  }

  /**
   * Tests for page not found error if batch ID does not exist.
   */
  public function testBatchNotFound() {
    $this->drupalLogin($this->adminUser);

    // Replicate a batch process by cancelling a user.
    $edit = array(
      'action' => 'user_cancel_user_action',
      'user_bulk_form[2]' => TRUE,
    );
    $this->drupalPostForm('admin/people', $edit, t('Apply'));
    $this->drupalPostForm(NULL, array(), t('Cancel accounts'));

    $batch_id = db_next_id();

    $this->drupalGet('batch', array(
      'query' => array(
        'op' => 'start',
        'id' => $batch_id,
      ),
    ));

    $this->assertResponse(404);
  }

}
