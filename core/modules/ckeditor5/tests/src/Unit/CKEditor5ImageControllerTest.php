<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\ckeditor5\Controller\CKEditor5ImageController;
use Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface;
use Drupal\Core\Entity\EntityConstraintViolationList;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\editor\EditorInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file\Upload\FileUploadHandlerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Tests CKEditor5ImageController.
 */
#[CoversClass(CKEditor5ImageController::class)]
#[Group('ckeditor5')]
final class CKEditor5ImageControllerTest extends UnitTestCase {

  /**
   * Tests that upload fails correctly when the file is too large.
   */
  public function testInvalidFile(): void {
    $file_system = $this->prophesize(FileSystemInterface::class);
    $file_system->move(Argument::any())->shouldNotBeCalled();
    $directory = 'public://';
    $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)->willReturn(TRUE);
    $file_system->getDestinationFilename(Argument::cetera())->willReturn('/tmp/foo.txt');
    $lock = $this->prophesize(LockBackendInterface::class);
    $lock->acquire(Argument::any())->willReturn(TRUE);
    $container = $this->prophesize(ContainerInterface::class);
    $file_storage = $this->prophesize(EntityStorageInterface::class);
    $file = $this->prophesize(FileInterface::class);
    $violations = $this->prophesize(EntityConstraintViolationList::class);
    $violations->count()->willReturn(0);
    $file->validate()->willReturn($violations->reveal());
    $file_storage->create(Argument::any())->willReturn($file->reveal());
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('file')->willReturn($file_storage->reveal());
    $container->get('entity_type.manager')->willReturn($entity_type_manager->reveal());
    $entity_type_repository = $this->prophesize(EntityTypeRepositoryInterface::class);
    $entity_type_repository->getEntityTypeFromClass(File::class)->willReturn('file');
    $container->get('entity_type.repository')->willReturn($entity_type_repository->reveal());
    \Drupal::setContainer($container->reveal());
    $controller = new CKEditor5ImageController(
      $file_system->reveal(),
      $this->prophesize(FileUploadHandlerInterface::class)->reveal(),
      $lock->reveal(),
      $this->prophesize(CKEditor5PluginManagerInterface::class)->reveal(),
    );
    // We can't use vfsstream here because of how Symfony request works.
    $file_uri = tempnam(sys_get_temp_dir(), 'tmp');
    $fp = fopen($file_uri, 'w');
    fwrite($fp, 'foo');
    fclose($fp);
    $request = Request::create('/', files: [
      'upload' => [
        'name' => 'foo.txt',
        'type' => 'text/plain',
        'size' => 42,
        'tmp_name' => $file_uri,
        'error' => \UPLOAD_ERR_FORM_SIZE,
      ],
    ]);
    $editor = $this->prophesize(EditorInterface::class);
    $request->attributes->set('editor', $editor->reveal());
    $this->expectException(HttpException::class);
    $this->expectExceptionMessage('The file "foo.txt" exceeds the upload limit defined in your form.');
    $controller->upload($request);
  }

}
