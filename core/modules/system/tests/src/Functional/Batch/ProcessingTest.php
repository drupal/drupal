<?php

namespace Drupal\Tests\system\Functional\Batch;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests batch processing in form and non-form workflow.
 *
 * @group Batch
 */
class ProcessingTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['batch_test', 'test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests batches triggered outside of form submission.
   */
  public function testBatchNoForm() {
    // Displaying the page triggers batch 1.
    $this->drupalGet('batch-test/no-form');
    $this->assertBatchMessages($this->_resultMessages('batch_1'), 'Batch for step 2 performed successfully.');
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_1'), 'Execution order was correct.');
    $this->assertText('Redirection successful.', 'Redirection after batch execution is correct.');
  }

  /**
   * Tests batches that redirect in the batch finished callback.
   */
  public function testBatchRedirectFinishedCallback() {
    // Displaying the page triggers batch 1.
    $this->drupalGet('batch-test/finish-redirect');
    $this->assertBatchMessages($this->_resultMessages('batch_1'), 'Batch for step 2 performed successfully.');
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_1'), 'Execution order was correct.');
    $this->assertText('Test page text.', 'Custom redirection after batch execution displays the correct page.');
    $this->assertUrl(Url::fromRoute('test_page_test.test_page'));
  }

  /**
   * Tests batches defined in a form submit handler.
   */
  public function testBatchForm() {
    // Batch 0: no operation.
    $edit = ['batch' => 'batch_0'];
    $this->drupalPostForm('batch-test', $edit, 'Submit');
    // If there is any escaped markup it will include at least an escaped '<'
    // character, so assert on each page that there is no escaped '<' as a way
    // of verifying that no markup is incorrectly escaped.
    $this->assertNoEscaped('<');
    $this->assertBatchMessages($this->_resultMessages('batch_0'), 'Batch with no operation performed successfully.');
    $this->assertText('Redirection successful.', 'Redirection after batch execution is correct.');

    // Batch 1: several simple operations.
    $edit = ['batch' => 'batch_1'];
    $this->drupalPostForm('batch-test', $edit, 'Submit');
    $this->assertNoEscaped('<');
    $this->assertBatchMessages($this->_resultMessages('batch_1'), 'Batch with simple operations performed successfully.');
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_1'), 'Execution order was correct.');
    $this->assertText('Redirection successful.', 'Redirection after batch execution is correct.');

    // Batch 2: one multistep operation.
    $edit = ['batch' => 'batch_2'];
    $this->drupalPostForm('batch-test', $edit, 'Submit');
    $this->assertNoEscaped('<');
    $this->assertBatchMessages($this->_resultMessages('batch_2'), 'Batch with multistep operation performed successfully.');
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_2'), 'Execution order was correct.');
    $this->assertText('Redirection successful.', 'Redirection after batch execution is correct.');

    // Batch 3: simple + multistep combined.
    $edit = ['batch' => 'batch_3'];
    $this->drupalPostForm('batch-test', $edit, 'Submit');
    $this->assertNoEscaped('<');
    $this->assertBatchMessages($this->_resultMessages('batch_3'), 'Batch with simple and multistep operations performed successfully.');
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_3'), 'Execution order was correct.');
    $this->assertText('Redirection successful.', 'Redirection after batch execution is correct.');

    // Batch 4: nested batch.
    $edit = ['batch' => 'batch_4'];
    $this->drupalPostForm('batch-test', $edit, 'Submit');
    $this->assertNoEscaped('<');
    $this->assertBatchMessages($this->_resultMessages('batch_4'), 'Nested batch performed successfully.');
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_4'), 'Execution order was correct.');
    $this->assertText('Redirection successful.', 'Redirection after batch execution is correct.');

    // Submit batches 4 and 7. Batch 4 will trigger batch 2. Batch 7 will
    // trigger batches 6 and 5.
    $edit = ['batch' => ['batch_4', 'batch_7']];
    $this->drupalPostForm('batch-test', $edit, 'Submit');
    $this->assertSession()->assertNoEscaped('<');
    $this->assertSession()->responseContains('Redirection successful.');
    $this->assertBatchMessages($this->_resultMessages('batch_4'), 'Nested batch performed successfully.');
    $this->assertBatchMessages($this->_resultMessages('batch_7'), 'Nested batch performed successfully.');
    $expected_stack = array_merge($this->_resultStack('batch_4'), $this->_resultStack('batch_7'));
    $this->assertEquals($expected_stack, batch_test_stack(), 'Execution order was correct.');
    $batch = \Drupal::state()->get('batch_test_nested_order_multiple_batches');
    $this->assertCount(5, $batch['sets']);
    // Ensure correct queue mapping.
    foreach ($batch['sets'] as $index => $batch_set) {
      $this->assertEquals('drupal_batch:' . $batch['id'] . ':' . $index, $batch_set['queue']['name']);
    }
    // Ensure correct order of the nested batches. We reset the indexes in
    // order to directly access the batches by their order.
    $batch_sets = array_values($batch['sets']);
    $this->assertEquals('batch_4', $batch_sets[0]['batch_test_id']);
    $this->assertEquals('batch_2', $batch_sets[1]['batch_test_id']);
    $this->assertEquals('batch_7', $batch_sets[2]['batch_test_id']);
    $this->assertEquals('batch_6', $batch_sets[3]['batch_test_id']);
    $this->assertEquals('batch_5', $batch_sets[4]['batch_test_id']);
  }

  /**
   * Tests batches defined in a multistep form.
   */
  public function testBatchFormMultistep() {
    $this->drupalGet('batch-test/multistep');
    $this->assertNoEscaped('<');
    $this->assertText('step 1', 'Form is displayed in step 1.');

    // First step triggers batch 1.
    $this->drupalPostForm(NULL, [], 'Submit');
    $this->assertBatchMessages($this->_resultMessages('batch_1'), 'Batch for step 1 performed successfully.');
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_1'), 'Execution order was correct.');
    $this->assertText('step 2', 'Form is displayed in step 2.');
    $this->assertNoEscaped('<');

    // Second step triggers batch 2.
    $this->drupalPostForm(NULL, [], 'Submit');
    $this->assertBatchMessages($this->_resultMessages('batch_2'), 'Batch for step 2 performed successfully.');
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_2'), 'Execution order was correct.');
    $this->assertText('Redirection successful.', 'Redirection after batch execution is correct.');
    $this->assertNoEscaped('<');

    // Extra query arguments will trigger logic that will add them to the
    // redirect URL. Make sure they are persisted.
    $this->drupalGet('batch-test/multistep', ['query' => ['big_tree' => 'small_axe']]);
    $this->drupalPostForm(NULL, [], 'Submit');
    $this->assertText('step 2', 'Form is displayed in step 2.');
    $this->assertStringContainsString('batch-test/multistep?big_tree=small_axe', $this->getUrl(), 'Query argument was persisted and another extra argument was added.');
  }

  /**
   * Tests batches defined in different submit handlers on the same form.
   */
  public function testBatchFormMultipleBatches() {
    // Batches 1, 2 and 3 are triggered in sequence by different submit
    // handlers. Each submit handler modify the submitted 'value'.
    $value = rand(0, 255);
    $edit = ['value' => $value];
    $this->drupalPostForm('batch-test/chained', $edit, 'Submit');
    // Check that result messages are present and in the correct order.
    $this->assertBatchMessages($this->_resultMessages('chained'), 'Batches defined in separate submit handlers performed successfully.');
    // The stack contains execution order of batch callbacks and submit
    // handlers and logging of corresponding $form_state->getValues().
    $this->assertEqual(batch_test_stack(), $this->_resultStack('chained', $value), 'Execution order was correct, and $form_state is correctly persisted.');
    $this->assertText('Redirection successful.', 'Redirection after batch execution is correct.');
  }

  /**
   * Tests batches defined in a programmatically submitted form.
   *
   * Same as above, but the form is submitted through drupal_form_execute().
   */
  public function testBatchFormProgrammatic() {
    // Batches 1, 2 and 3 are triggered in sequence by different submit
    // handlers. Each submit handler modify the submitted 'value'.
    $value = rand(0, 255);
    $this->drupalGet('batch-test/programmatic/' . $value);
    // Check that result messages are present and in the correct order.
    $this->assertBatchMessages($this->_resultMessages('chained'), 'Batches defined in separate submit handlers performed successfully.');
    // The stack contains execution order of batch callbacks and submit
    // handlers and logging of corresponding $form_state->getValues().
    $this->assertEqual(batch_test_stack(), $this->_resultStack('chained', $value), 'Execution order was correct, and $form_state is correctly persisted.');
    $this->assertText('Got out of a programmatic batched form.', 'Page execution continues normally.');
  }

  /**
   * Test form submission during a batch operation.
   */
  public function testDrupalFormSubmitInBatch() {
    // Displaying the page triggers a batch that programmatically submits a
    // form.
    $value = rand(0, 255);
    $this->drupalGet('batch-test/nested-programmatic/' . $value);
    $this->assertEqual(batch_test_stack(), ['mock form submitted with value = ' . $value], '\Drupal::formBuilder()->submitForm() ran successfully within a batch operation.');
  }

  /**
   * Tests batches that return $context['finished'] > 1 do in fact complete.
   *
   * @see https://www.drupal.org/node/600836
   */
  public function testBatchLargePercentage() {
    // Displaying the page triggers batch 5.
    $this->drupalGet('batch-test/large-percentage');
    $this->assertBatchMessages($this->_resultMessages('batch_5'), 'Batch for step 2 performed successfully.');
    $this->assertEqual(batch_test_stack(), $this->_resultStack('batch_5'), 'Execution order was correct.');
    $this->assertText('Redirection successful.', 'Redirection after batch execution is correct.');
  }

  /**
   * Triggers a pass if the texts were found in order in the raw content.
   *
   * @param $texts
   *   Array of raw strings to look for .
   * @param $message
   *   Message to display.
   *
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  public function assertBatchMessages($texts, $message) {
    $pattern = '|' . implode('.*', $texts) . '|s';
    return $this->assertPattern($pattern, $message);
  }

  /**
   * Returns expected execution stacks for the test batches.
   */
  public function _resultStack($id, $value = 0) {
    $stack = [];
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

      case 'batch_6':
        for ($i = 1; $i <= 10; $i++) {
          $stack[] = "op 6 id $i";
        }
        break;

      case 'batch_7':
        for ($i = 1; $i <= 5; $i++) {
          $stack[] = "op 7 id $i";
        }
        $stack[] = 'setting up batch 6';
        $stack[] = 'setting up batch 5';
        for ($i = 6; $i <= 10; $i++) {
          $stack[] = "op 7 id $i";
        }
        $stack = array_merge($stack, $this->_resultStack('batch_6'));
        $stack = array_merge($stack, $this->_resultStack('batch_5'));
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
   * Returns expected result messages for the test batches.
   */
  public function _resultMessages($id) {
    $messages = [];

    switch ($id) {
      case 'batch_0':
        $messages[] = 'results for batch 0<div class="item-list"><ul><li>none</li></ul></div>';
        break;

      case 'batch_1':
        $messages[] = 'results for batch 1<div class="item-list"><ul><li>op 1: processed 10 elements</li></ul></div>';
        break;

      case 'batch_2':
        $messages[] = 'results for batch 2<div class="item-list"><ul><li>op 2: processed 10 elements</li></ul></div>';
        break;

      case 'batch_3':
        $messages[] = 'results for batch 3<div class="item-list"><ul><li>op 1: processed 10 elements</li><li>op 2: processed 10 elements</li></ul></div>';
        break;

      case 'batch_4':
        $messages[] = 'results for batch 4<div class="item-list"><ul><li>op 1: processed 10 elements</li></ul></div>';
        $messages = array_merge($messages, $this->_resultMessages('batch_2'));
        break;

      case 'batch_5':
        $messages[] = 'results for batch 5<div class="item-list"><ul><li>op 5: processed 10 elements</li></ul></div>';
        break;

      case 'batch_6':
        $messages[] = 'results for batch 6<div class="item-list"><ul><li>op 6: processed 10 elements</li></ul></div>';
        break;

      case 'batch_7':
        $messages[] = 'results for batch 7<div class="item-list"><ul><li>op 7: processed 10 elements</li></ul></div>';
        $messages = array_merge($messages, $this->_resultMessages('batch_6'));
        $messages = array_merge($messages, $this->_resultMessages('batch_5'));
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
