<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\DrupalKernel;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests stream wrapper functions.
 *
 * @group File
 */
class StreamWrapperTest extends FileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file_test'];

  /**
   * A stream wrapper scheme to register for the test.
   *
   * @var string
   */
  protected $scheme = 'dummy';

  /**
   * A fully-qualified stream wrapper class name to register for the test.
   *
   * @var string
   */
  protected $classname = 'Drupal\file_test\StreamWrapper\DummyStreamWrapper';

  public function setUp(): void {
    parent::setUp();

    // Add file_private_path setting.
    $request = Request::create('/');
    $site_path = DrupalKernel::findSitePath($request);
    $this->setSetting('file_private_path', $site_path . '/private');
  }

  /**
   * Test the getClassName() function.
   */
  public function testGetClassName() {
    // Check the dummy scheme.
    $this->assertEquals($this->classname, \Drupal::service('stream_wrapper_manager')->getClass($this->scheme), 'Got correct class name for dummy scheme.');
    // Check core's scheme.
    $this->assertEquals('Drupal\Core\StreamWrapper\PublicStream', \Drupal::service('stream_wrapper_manager')->getClass('public'), 'Got correct class name for public scheme.');
  }

  /**
   * Test the getViaScheme() method.
   */
  public function testGetInstanceByScheme() {
    $instance = \Drupal::service('stream_wrapper_manager')->getViaScheme($this->scheme);
    $this->assertEquals($this->classname, get_class($instance), 'Got correct class type for dummy scheme.');

    $instance = \Drupal::service('stream_wrapper_manager')->getViaScheme('public');
    $this->assertEquals('Drupal\Core\StreamWrapper\PublicStream', get_class($instance), 'Got correct class type for public scheme.');
  }

  /**
   * Test the getViaUri() and getViaScheme() methods and target functions.
   */
  public function testUriFunctions() {
    $config = $this->config('system.file');

    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');

    $instance = $stream_wrapper_manager->getViaUri($this->scheme . '://foo');
    $this->assertEquals($this->classname, get_class($instance), 'Got correct class type for dummy URI.');

    $instance = $stream_wrapper_manager->getViaUri('public://foo');
    $this->assertEquals('Drupal\Core\StreamWrapper\PublicStream', get_class($instance), 'Got correct class type for public URI.');

    // Test file_uri_target().
    $this->assertEquals('foo/bar.txt', $stream_wrapper_manager::getTarget('public://foo/bar.txt'), 'Got a valid stream target from public://foo/bar.txt.');
    $this->assertEquals('image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==', $stream_wrapper_manager::getTarget('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg=='), t('Got a valid stream target from a data URI.'));
    $this->assertFalse($stream_wrapper_manager::getTarget('foo/bar.txt'), 'foo/bar.txt is not a valid stream.');
    $this->assertSame($stream_wrapper_manager::getTarget('public://'), '');
    $this->assertSame($stream_wrapper_manager::getTarget('data:'), '');

    // Test file_build_uri() and
    // Drupal\Core\StreamWrapper\LocalStream::getDirectoryPath().
    $this->assertEquals('public://foo/bar.txt', file_build_uri('foo/bar.txt'), 'Expected scheme was added.');
    $this->assertEquals(PublicStream::basePath(), $stream_wrapper_manager->getViaScheme('public')->getDirectoryPath(), 'Expected default directory path was returned.');
    $file_system = \Drupal::service('file_system');
    assert($file_system instanceof FileSystemInterface);
    $this->assertEquals($file_system->getTempDirectory(), $stream_wrapper_manager->getViaScheme('temporary')->getDirectoryPath(), 'Expected temporary directory path was returned.');
    $config->set('default_scheme', 'private')->save();
    $this->assertEquals('private://foo/bar.txt', file_build_uri('foo/bar.txt'), 'Got a valid URI from foo/bar.txt.');

    // Test file_create_url()
    // TemporaryStream::getExternalUrl() uses Url::fromRoute(), which needs
    // route information to work.
    $this->assertStringContainsString('system/temporary?file=test.txt', file_create_url('temporary://test.txt'), 'Temporary external URL correctly built.');
    $this->assertStringContainsString(Settings::get('file_public_path') . '/test.txt', file_create_url('public://test.txt'), 'Public external URL correctly built.');
    $this->assertStringContainsString('system/files/test.txt', file_create_url('private://test.txt'), 'Private external URL correctly built.');
  }

  /**
   * Test some file handle functions.
   */
  public function testFileFunctions() {
    $filename = 'public://' . $this->randomMachineName();
    file_put_contents($filename, str_repeat('d', 1000));

    // Open for rw and place pointer at beginning of file so select will return.
    $handle = fopen($filename, 'c+');
    $this->assertNotFalse($handle, 'Able to open a file for appending, reading and writing.');

    // Attempt to change options on the file stream: should all fail.
    $this->assertFalse(@stream_set_blocking($handle, 0), 'Unable to set to non blocking using a local stream wrapper.');
    $this->assertFalse(@stream_set_blocking($handle, 1), 'Unable to set to blocking using a local stream wrapper.');
    $this->assertFalse(@stream_set_timeout($handle, 1), 'Unable to set read time out using a local stream wrapper.');
    $this->assertEquals(-1 /*EOF*/, @stream_set_write_buffer($handle, 512), 'Unable to set write buffer using a local stream wrapper.');

    // This will test stream_cast().
    $read = [$handle];
    $write = NULL;
    $except = NULL;
    $this->assertEquals(1, stream_select($read, $write, $except, 0), 'Able to cast a stream via stream_select.');

    // This will test stream_truncate().
    $this->assertEquals(1, ftruncate($handle, 0), 'Able to truncate a stream via ftruncate().');
    fclose($handle);
    $this->assertEquals(0, filesize($filename), 'Able to truncate a stream.');

    // Cleanup.
    unlink($filename);
  }

  /**
   * Test the scheme functions.
   */
  public function testGetValidStreamScheme() {

    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');

    $this->assertEquals('foo', $stream_wrapper_manager::getScheme('foo://pork//chops'), 'Got the correct scheme from foo://asdf');
    $this->assertEquals('data', $stream_wrapper_manager::getScheme('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg=='), 'Got the correct scheme from a data URI.');
    $this->assertFalse($stream_wrapper_manager::getScheme('foo/bar.txt'), 'foo/bar.txt is not a valid stream.');
    $this->assertTrue($stream_wrapper_manager->isValidScheme($stream_wrapper_manager::getScheme('public://asdf')), 'Got a valid stream scheme from public://asdf');
    $this->assertFalse($stream_wrapper_manager->isValidScheme($stream_wrapper_manager::getScheme('foo://asdf')), 'Did not get a valid stream scheme from foo://asdf');
  }

  /**
   * Tests that phar stream wrapper is registered as expected.
   *
   * @see \Drupal\Core\StreamWrapper\StreamWrapperManager::register()
   */
  public function testPharStreamWrapperRegistration() {
    if (!in_array('phar', stream_get_wrappers(), TRUE)) {
      $this->markTestSkipped('There is no phar stream wrapper registered. PHP is probably compiled without phar support.');
    }
    // Ensure that phar is not treated as a valid scheme.
    $stream_wrapper_manager = $this->container->get('stream_wrapper_manager');
    $this->assertFalse($stream_wrapper_manager->getViaScheme('phar'));

    // Ensure that calling register again and unregister do not create errors
    // due to the PharStreamWrapperManager singleton.
    $stream_wrapper_manager->register();
    $this->assertContains('public', stream_get_wrappers());
    $this->assertContains('phar', stream_get_wrappers());
    $stream_wrapper_manager->unregister();
    $this->assertNotContains('public', stream_get_wrappers());
    // This will have reverted to the builtin phar stream wrapper.
    $this->assertContains('phar', stream_get_wrappers());
    $stream_wrapper_manager->register();
    $this->assertContains('public', stream_get_wrappers());
    $this->assertContains('phar', stream_get_wrappers());
  }

}
