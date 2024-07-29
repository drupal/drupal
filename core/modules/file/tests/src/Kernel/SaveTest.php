<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\file\Entity\File;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * File saving tests.
 *
 * @group file
 */
class SaveTest extends FileManagedUnitTestBase {

  use UserCreationTrait;

  public function testFileSave(): void {
    $account = $this->createUser();
    // Create a new file entity.
    $file = File::create([
      'uid' => $account->id(),
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
    ]);
    $file->setPermanent();
    file_put_contents($file->getFileUri(), 'hello world');

    // Save it, inserting a new record.
    $file->save();

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['insert']);

    // Verify that a new file ID is set when saving a new file to the database.
    $this->assertGreaterThan(0, $file->id());
    $loaded_file = File::load($file->id());
    $this->assertNotNull($loaded_file, 'Record exists in the database.');
    $this->assertEquals($file->isPermanent(), $loaded_file->isPermanent(), 'Status was saved correctly.');
    $this->assertEquals(filesize($file->getFileUri()), $file->getSize(), 'File size was set correctly.');
    // Verify that the new file size was set correctly.
    $this->assertGreaterThan(1, $file->getChangedTime());
    $this->assertEquals('en', $loaded_file->langcode->value, 'Langcode was defaulted correctly.');

    // Resave the file, updating the existing record.
    file_test_reset();
    $file->status->value = 7;
    $file->save();

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['load', 'update']);

    $this->assertEquals($file->id(), $file->id(), 'The file ID of an existing file is not changed when updating the database.');
    $loaded_file = File::load($file->id());
    // Verify that the timestamp didn't go backwards.
    $this->assertGreaterThanOrEqual($file->getChangedTime(), $loaded_file->getChangedTime());
    $this->assertNotNull($loaded_file, 'Record still exists in the database.');
    $this->assertEquals($file->isPermanent(), $loaded_file->isPermanent(), 'Status was saved correctly.');
    $this->assertEquals('en', $loaded_file->langcode->value, 'Langcode was saved correctly.');

    // Try to insert a second file with the same name apart from case insensitivity
    // to ensure the 'uri' index allows for filenames with different cases.
    $uppercase_values = [
      'uid' => $account->id(),
      'filename' => 'DRUPLICON.txt',
      'uri' => 'public://DRUPLICON.txt',
      'filemime' => 'text/plain',
    ];
    $file->setPermanent();
    $uppercase_file = File::create($uppercase_values);
    file_put_contents($uppercase_file->getFileUri(), 'hello world');
    $violations = $uppercase_file->validate();
    $this->assertCount(0, $violations, 'No violations when adding an URI with an existing filename in upper case.');
    $uppercase_file->save();

    // Ensure the database URI uniqueness constraint is triggered.
    $uppercase_file_duplicate = File::create($uppercase_values);
    file_put_contents($uppercase_file_duplicate->getFileUri(), 'hello world');
    $violations = $uppercase_file_duplicate->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals(sprintf('The file %s already exists. Enter a unique file URI.', $uppercase_file_duplicate->getFileUri()), $violations[0]->getMessage());
    // Ensure that file URI entity queries are case sensitive.
    $fids = \Drupal::entityQuery('file')
      ->accessCheck(FALSE)
      ->condition('uri', $uppercase_file->getFileUri())
      ->execute();

    $this->assertCount(1, $fids);
    $this->assertEquals([$uppercase_file->id() => $uppercase_file->id()], $fids);

    // Save a file with zero bytes.
    $file = File::create([
      'uid' => $account->id(),
      'filename' => 'no-druplicon.txt',
      'uri' => 'public://no-druplicon.txt',
      'filemime' => 'text/plain',
    ]);
    $file->setPermanent();

    file_put_contents($file->getFileUri(), '');

    // Save it, inserting a new record.
    $file->save();

    // Check the file size was set to zero.
    $this->assertSame(0, $file->getSize());
  }

}
