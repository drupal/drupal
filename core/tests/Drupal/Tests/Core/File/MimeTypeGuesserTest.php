<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\File\MimeTypeGuesserTest.
 */

namespace Drupal\Tests\Core\File;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\File\MimeType\MimeTypeGuesser;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser as SymfonyMimeTypeGuesser;

/**
 * @coversDefaultClass \Drupal\Core\File\MimeType\MimeTypeGuesser
 * @group File
 */
class MimeTypeGuesserTest extends UnitTestCase {

  /**
   * @covers ::guess
   * @covers ::addGuesser
   * @covers ::sortGuessers
   */
  public function testGuess() {
    $stream_wrapper_manager = $this->getMockBuilder('Drupal\Core\StreamWrapper\StreamWrapperManager')
      ->disableOriginalConstructor()
      ->getMock();
    $stream_wrapper_manager->expects($this->any())
      ->method('getViaUri')
      ->willReturn(NULL);
    $mime_guesser_service = new MimeTypeGuesser($stream_wrapper_manager);
    $guesser_1 = $this->getMock('Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface');
    $guesser_1->expects($this->once())
      ->method('guess')
      ->with('file.txt')
      ->willReturn('text/plain');
    $mime_guesser_service->addGuesser($guesser_1);
    $this->assertEquals('text/plain', $mime_guesser_service->guess('file.txt'));
    $guesser_2 = $this->getMock('Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface');
    $guesser_2->expects($this->once())
      ->method('guess')
      ->with('file.txt')
      ->willReturn('text/x-diff');
    $mime_guesser_service->addGuesser($guesser_2, 10);
    $this->assertEquals('text/x-diff', $mime_guesser_service->guess('file.txt'));
  }

  /**
   * @covers ::registerWithSymfonyGuesser
   *
   * @see Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser
   */
  public function testSymfonyGuesserRegistration() {
    // Make the guessers property accessible on Symfony's MimeTypeGuesser.
    $symfony_guesser = SymfonyMimeTypeGuesser::getInstance();
    // Test that the Drupal mime type guess is not being used before the
    // override method is called. It is possible that the test environment does
    // not support the default guessers.
    $guessers = $this->readAttribute($symfony_guesser, 'guessers');
    if (count($guessers)) {
      $this->assertNotInstanceOf('Drupal\Core\File\MimeType\MimeTypeGuesser', $guessers[0]);
    }
    $stream_wrapper_manager = $this->getMockBuilder('Drupal\Core\StreamWrapper\StreamWrapperManager')
      ->disableOriginalConstructor()
      ->getMock();
    $container = new ContainerBuilder();
    $container->set('file.mime_type.guesser', new MimeTypeGuesser($stream_wrapper_manager));
    MimeTypeGuesser::registerWithSymfonyGuesser($container);
    $guessers = $this->readAttribute($symfony_guesser, 'guessers');
    $this->assertInstanceOf('Drupal\Core\File\MimeType\MimeTypeGuesser', $guessers[0]);
  }

}
