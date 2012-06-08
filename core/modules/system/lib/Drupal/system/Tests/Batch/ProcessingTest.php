<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Batch\ProcessingTest.
 */

namespace Drupal\system\Tests\Batch;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the Batch API.
 */
class ProcessingTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Batch processing',
      'description' => 'Test batch processing in form and non-form workflow.',
      'group' => 'Batch API',
    );
  }

  function setUp() {
    parent::setUp('batch_test');
  }

  /**
   * Test batches triggered outside of form submission.
   */
  function testBatchNoForm() {
    // Displaying the page triggers batch 1.
    $this->drupalGet('batch-test/no-form');
    $this->assertBatchMessages($this->_resultMessages(1), t('Batch for step 2 performed successfully.'));
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_1'), t('Execution order was correct.'));
    $this->assertText('Redirection successful.', t('Redirection after batch execution is correct.'));
  }

  /**
   * Test batches defined in a form submit handler.
   */
  function testBatchForm() {
    // Batch 0: no operation.
    $edit = array('batch' => 'batch_0');
    $this->drupalPost('batch-test/simple', $edit, 'Submit');
    $this->assertBatchMessages($this->_resultMessages('batch_0'), t('Batch with no operation performed successfully.'));
    $this->assertText('Redirection successful.', t('Redirection after batch execution is correct.'));

    // Batch 1: several simple operations.
    $edit = array('batch' => 'batch_1');
    $this->drupalPost('batch-test/simple', $edit, 'Submit');
    $this->assertBatchMessages($this->_resultMessages('batch_1'), t('Batch with simple operations performed successfully.'));
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_1'), t('Execution order was correct.'));
    $this->assertText('Redirection successful.', t('Redirection after batch execution is correct.'));

    // Batch 2: one multistep operation.
    $edit = array('batch' => 'batch_2');
    $this->drupalPost('batch-test/simple', $edit, 'Submit');
    $this->assertBatchMessages($this->_resultMessages('batch_2'), t('Batch with multistep operation performed successfully.'));
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_2'), t('Execution order was correct.'));
    $this->assertText('Redirection successful.', t('Redirection after batch execution is correct.'));

    // Batch 3: simple + multistep combined.
    $edit = array('batch' => 'batch_3');
    $this->drupalPost('batch-test/simple', $edit, 'Submit');
    $this->assertBatchMessages($this->_resultMessages('batch_3'), t('Batch with simple and multistep operations performed successfully.'));
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_3'), t('Execution order was correct.'));
    $this->assertText('Redirection successful.', t('Redirection after batch execution is correct.'));

    // Batch 4: nested batch.
    $edit = array('batch' => 'batch_4');
    $this->drupalPost('batch-test/simple', $edit, 'Submit');
    $this->assertBatchMessages($this->_resultMessages('batch_4'), t('Nested batch performed successfully.'));
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_4'), t('Execution order was correct.'));
    $this->assertText('Redirection successful.', t('Redirection after batch execution is correct.'));
  }

  /**
   * Test batches defined in a multistep form.
   */
  function testBatchFormMultistep() {
    $this->drupalGet('batch-test/multistep');
    $this->assertText('step 1', t('Form is displayed in step 1.'));

    // First step triggers batch 1.
    $this->drupalPost(NULL, array(), 'Submit');
    $this->assertBatchMessages($this->_resultMessages('batch_1'), t('Batch for step 1 performed successfully.'));
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_1'), t('Execution order was correct.'));
    $this->assertText('step 2', t('Form is displayed in step 2.'));

    // Second step triggers batch 2.
    $this->drupalPost(NULL, array(), 'Submit');
    $this->assertBatchMessages($this->_resultMessages('batch_2'), t('Batch for step 2 performed successfully.'));
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_2'), t('Execution order was correct.'));
    $this->assertText('Redirection successful.', t('Redirection after batch execution is correct.'));
  }

  /**
   * Test batches defined in different submit handlers on the same form.
   */
  function testBatchFormMultipleBatches() {
    // Batches 1, 2 and 3 are triggered in sequence by different submit
    // handlers. Each submit handler modify the submitted 'value'.
    $value = rand(0, 255);
    $edit = array('value' => $value);
    $this->drupalPost('batch-test/chained', $edit, 'Submit');
    // Check that result messages are present and in the correct order.
    $this->assertBatchMessages($this->_resultMessages('chained'), t('Batches defined in separate submit handlers performed successfully.'));
    // The stack contains execution order of batch callbacks and submit
    // hanlders and logging of corresponding $form_state[{values'].
    $this->assertEqual(batch_test_stack(), $this->_resultStack('chained', $value), t('Execution order was correct, and $form_state is correctly persisted.'));
    $this->assertText('Redirection successful.', t('Redirection after batch execution is correct.'));
  }

  /**
   * Test batches defined in a programmatically submitted form.
   *
   * Same as above, but the form is submitted through drupal_form_execute().
   */
  function testBatchFormProgrammatic() {
    // Batches 1, 2 and 3 are triggered in sequence by different submit
    // handlers. Each submit handler modify the submitted 'value'.
    $value = rand(0, 255);
    $this->drupalGet('batch-test/programmatic/' . $value);
    // Check that result messages are present and in the correct order.
    $this->assertBatchMessages($this->_resultMessages('chained'), t('Batches defined in separate submit handlers performed successfully.'));
    // The stack contains execution order of batch callbacks and submit
    // hanlders and logging of corresponding $form_state[{values'].
    $this->assertEqual(batch_test_stack(), $this->_resultStack('chained', $value), t('Execution order was correct, and $form_state is correctly persisted.'));
    $this->assertText('Got out of a programmatic batched form.', t('Page execution continues normally.'));
  }

  /**
   * Test that drupal_form_submit() can run within a batch operation.
   */
  function testDrupalFormSubmitInBatch() {
    // Displaying the page triggers a batch that programmatically submits a
    // form.
    $value = rand(0, 255);
    $this->drupalGet('batch-test/nested-programmatic/' . $value);
    $this->assertEqual(batch_test_stack(), array('mock form submitted with value = ' . $value), t('drupal_form_submit() ran successfully within a batch operation.'));
  }

  /**
   * Test batches that return $context['finished'] > 1 do in fact complete.
   * See http://drupal.org/node/600836
   */
  function testBatchLargePercentage() {
    // Displaying the page triggers batch 5.
    $this->drupalGet('batch-test/large-percentage');
    $this->assertBatchMessages($this->_resultMessages(1), t('Batch for step 2 performed successfully.'));
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_5'), t('Execution order was correct.'));
    $this->assertText('Redirection successful.', t('Redirection after batch execution is correct.'));
  }


  /**
   * Will trigger a pass if the texts were found in order in the raw content.
   *
   * @param $texts
   *   Array of raw strings to look for .
   * @param $message
   *   Message to display.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertBatchMessages($texts, $message) {
    $pattern = '|' . implode('.*', $texts) .'|s';
    return $this->assertPattern($pattern, $message);
  }

  /**
   * Helper function: return expected execution stacks for the test batches.
   */
  function _resultStack($id, $value = 0) {
    $stack = array();
    switch ($id) {
      case 'batch_1':
        for ($i = 1; $i <= 10; $i++) {
          $stack[] = "op 1 id $i";
        }
        break;

      case 'batch_2':
        for ($i = 1; $i <= 10; $i++) {
          $stack[] = "op 2 id $i";
        }
        break;

      case 'batch_3':
        for ($i = 1; $i <= 5; $i++) {
          $stack[] = "op 1 id $i";
        }
        for ($i = 1; $i <= 5; $i++) {
          $stack[] = "op 2 id $i";
        }
        for ($i = 6; $i <= 10; $i++) {
          $stack[] = "op 1 id $i";
        }
        for ($i = 6; $i <= 10; $i++) {
          $stack[] = "op 2 id $i";
        }
        break;

      case 'batch_4':
        for ($i = 1; $i <= 5; $i++) {
          $stack[] = "op 1 id $i";
        }
        $stack[] = 'setting up batch 2';
        for ($i = 6; $i <= 10; $i++) {
          $stack[] = "op 1 id $i";
        }
        $stack = array_merge($stack, $this->_resultStack('batch_2'));
        break;

      case 'batch_5':
        for ($i = 1; $i <= 10; $i++) {
          $stack[] = "op 5 id $i";
        }
        break;

      case 'chained':
        $stack[] = 'submit handler 1';
        $stack[] = 'value = ' . $value;
        $stack = array_merge($stack, $this->_resultStack('batch_1'));
        $stack[] = 'submit handler 2';
        $stack[] = 'value = ' . ($value + 1);
        $stack = array_merge($stack, $this->_resultStack('batch_2'));
        $stack[] = 'submit handler 3';
        $stack[] = 'value = ' . ($value + 2);
        $stack[] = 'submit handler 4';
        $stack[] = 'value = ' . ($value + 3);
        $stack = array_merge($stack, $this->_resultStack('batch_3'));
        break;
    }
    return $stack;
  }

  /**
   * Helper function: return expected result messages for the test batches.
   */
  function _resultMessages($id) {
    $messages = array();

    switch ($id) {
      case 'batch_0':
        $messages[] = 'results for batch 0<br />none';
        break;

      case 'batch_1':
        $messages[] = 'results for batch 1<br />op 1: processed 10 elements';
        break;

      case 'batch_2':
        $messages[] = 'results for batch 2<br />op 2: processed 10 elements';
        break;

      case 'batch_3':
        $messages[] = 'results for batch 3<br />op 1: processed 10 elements<br />op 2: processed 10 elements';
        break;

      case 'batch_4':
        $messages[] = 'results for batch 4<br />op 1: processed 10 elements';
        $messages = array_merge($messages, $this->_resultMessages('batch_2'));
        break;

      case 'batch_5':
        $messages[] = 'results for batch 5<br />op 1: processed 10 elements. $context[\'finished\'] > 1 returned from batch process, with success.';
        break;

      case 'chained':
        $messages = array_merge($messages, $this->_resultMessages('batch_1'));
        $messages = array_merge($messages, $this->_resultMessages('batch_2'));
        $messages = array_merge($messages, $this->_resultMessages('batch_3'));
        break;
    }
    return $messages;
  }
}
