<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional;

use Drupal\file\Entity\File;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the file validation in the ManagedFile element for private files.
 */
#[Group('file')]
#[RunTestsInSeparateProcesses]
class ManagedFileFidValidationTest extends FileFieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * File owner user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $fileOwner;

  /**
   * Another user with limited access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $anotherUser;

  /**
   * A private file owned by the file owner.
   *
   * @var \Drupal\file\Entity\File
   */
  protected File $ownerFile;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create the file owner user.
    $this->fileOwner = $this->drupalCreateUser(['access content']);

    // Create a second user.
    $this->anotherUser = $this->drupalCreateUser(['access content']);

    $this->drupalLogin($this->fileOwner);
    $file_name = $this->randomMachineName();
    $this->ownerFile = File::create([
      'uid' => $this->fileOwner->id(),
      'filename' => \sprintf('%s.pdf', $file_name),
      'uri' => \sprintf('private://%s.pdf', $file_name),
      'filemime' => 'application/pdf',
      'status' => 0,
    ]);
    $this->ownerFile->save();
    $this->assertNotNull($this->ownerFile->id(), 'Owner file was created.');
  }

  /**
   * Tests that another user cannot access the owner's temporary file.
   */
  public function testOtherUserCannotAccessOwnerFile(): void {
    $this->assertFalse($this->ownerFile->access('download', $this->anotherUser));
    $this->assertEquals($this->fileOwner->id(), $this->ownerFile->getOwnerId());
    $this->assertTrue($this->ownerFile->isTemporary());
  }

  /**
   * Tests that other users' file IDs are rejected when passed via URL.
   */
  public function testOtherUsersFileIdNotAcceptedViaUrl(): void {
    $this->drupalLogin($this->anotherUser);

    $owner_fid = $this->ownerFile->id();

    // Visit the form with the other user's file ID in the URL.
    $this->drupalGet('file/test/1/1/1/' . $owner_fid);
    $this->submitForm([], 'Save');

    $this->getSession()->getPage()->getText();
    $this->assertSession()->pageTextContains('The file ids are');

    $response = $this->getSession()->getPage()->getText();
    $this->assertStringNotContainsString("The file ids are {$owner_fid}.", $response);
    $this->assertSession()->pageTextContains('The file ids are .');
  }

  /**
   * Tests that other users' file IDs are rejected even with a valid upload.
   */
  public function testOtherUsersFileIdNotAcceptedWithUpload(): void {
    $this->drupalLogin($this->anotherUser);

    $owner_fid = $this->ownerFile->id();
    $test_file = $this->getTestFile('text');

    // The other user visits the form with the owner's file ID AND uploads a
    // legitimate file.
    $this->drupalGet('file/test/1/1/1/' . $owner_fid);
    $this->submitForm([
      'files[nested_file][]' => \Drupal::service('file_system')
        ->realpath($test_file->getFileUri()),
    ], 'Save');

    $response = $this->getSession()->getPage()->getText();
    $uploaded_fid = $this->getLastFileId();

    $this->assertSession()->pageTextContains('The file ids are');

    $contains_owner_file = str_contains($response, (string) $owner_fid);
    $contains_uploaded = str_contains($response, (string) $uploaded_fid);

    $this->assertFalse($contains_owner_file);
    $this->assertTrue($contains_uploaded);
  }

}
