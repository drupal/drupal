<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\KernelTests\KernelTestBase;

/**
 * Confirms that theme assets copied from Classy have not been changed.
 *
 * If a copied Classy asset is changed, it should no longer be in a /classy
 * subdirectory. The files there should be exact copies from Classy. Once it has
 * changed, it is custom to the theme and should be moved to a different
 * location.
 *
 * @group Theme
 */
class ConfirmClassyCopiesTest extends KernelTestBase {

  /**
   * Tests Classy's assets have not been altered.
   */
  public function testClassyHashes() {
    $theme_path = $this->container->get('extension.list.theme')->getPath('classy');
    foreach (['images', 'css', 'js'] as $type => $sub_folder) {
      $asset_path = "$theme_path/$sub_folder";
      $directory = new \RecursiveDirectoryIterator($asset_path, \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS);
      $iterator = new \RecursiveIteratorIterator($directory);
      $this->assertGreaterThan(0, iterator_count($iterator));
      foreach ($iterator as $fileinfo) {
        $filename = $fileinfo->getFilename();
        $this->assertSame(
          $this->getClassyHash($sub_folder, $filename),
          md5_file($fileinfo->getPathname()),
          "$filename has expected hash"
        );
      }
    }
  }

  /**
   * Confirms that files copied from Classy have not been altered.
   *
   * The /classy subdirectory in a theme's css, js and images directories is for
   * unaltered copies of files from Classy. If a file in that subdirectory has
   * changed, then it is custom to that theme and should be moved to a different
   * directory. Additional information can be found in the README.txt of each of
   * those /classy subdirectories.
   *
   * @param string $theme
   *   The theme being tested.
   * @param string $path_replace
   *   A string to replace paths found in CSS so relative URLs don't cause the
   *   hash to differ.
   * @param string[] $filenames
   *   Provides list of every asset copied from Classy.
   *
   * @dataProvider providerTestClassyCopies
   */
  public function testClassyCopies($theme, $path_replace, array $filenames) {
    $theme_path = $this->container->get('extension.list.theme')->getPath($theme);

    foreach (['images', 'css', 'js'] as $sub_folder) {
      $asset_path = "$theme_path/$sub_folder/classy";
      // If a theme has completely customized all files of a type there is
      // potentially no Classy subdirectory for that type. Tests can be skipped
      // for that type.
      if (!file_exists($asset_path)) {
        $this->assertEmpty($filenames[$sub_folder]);
        continue;
      }

      // Create iterators to collect all files in a asset directory.
      $directory = new \RecursiveDirectoryIterator($asset_path, \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS);
      $iterator = new \RecursiveIteratorIterator($directory);
      $filecount = 0;
      foreach ($iterator as $fileinfo) {
        $filename = $fileinfo->getFilename();
        if ($filename === 'README.txt') {
          continue;
        }

        $filecount++;

        // Replace paths in the contents so the hash will match Classy's hashes.
        $contents = file_get_contents($fileinfo->getPathname());
        $contents = str_replace('(' . $path_replace, '(../../../../', $contents);
        $contents = str_replace('(../../../images/classy/icons', '(../../images/icons', $contents);

        $this->assertContains($filename, $filenames[$sub_folder], "$sub_folder file: $filename not present.");
        $this->assertSame(
          $this->getClassyHash($sub_folder, $filename),
          md5($contents),
          "$filename is in the theme's /classy subdirectory, but the file contents no longer match the original file from Classy. This should be moved to a new directory and libraries should be updated. The file can be removed from the data provider."
        );
      }
      $this->assertCount($filecount, $filenames[$sub_folder], "Different count for $sub_folder files in the /classy subdirectory. If a file was added to /classy, it shouldn't have been. If it was intentionally removed, it should also be removed from this test's data provider.");
    }
  }

  /**
   * Provides lists of filenames for a theme's asset files copied from Classy.
   *
   * @return array
   *   Theme name, how to replace a path to core assets and asset file names.
   */
  public function providerTestClassyCopies() {
    return [
      'umami' => [
        'theme-name' => 'umami',
        'path-replace' => '../../../../../../../',
        'filenames' => [
          'css' => [
            'action-links.css',
            'book-navigation.css',
            'breadcrumb.css',
            'button.css',
            'collapse-processed.css',
            'container-inline.css',
            'details.css',
            'dialog.css',
            'dropbutton.css',
            'exposed-filters.css',
            'field.css',
            'file.css',
            'form.css',
            'forum.css',
            'icons.css',
            'image-widget.css',
            'inline-form.css',
            'item-list.css',
            'link.css',
            'links.css',
            'media-embed-error.css',
            'media-library.css',
            'menu.css',
            'more-link.css',
            'node.css',
            'pager.css',
            'progress.css',
            'search-results.css',
            'tabledrag.css',
            'tableselect.css',
            'tablesort.css',
            'tabs.css',
            'textarea.css',
            'ui-dialog.css',
            'user.css',
          ],
          'js' => [
            'media_embed_ckeditor.theme.es6.js',
            'media_embed_ckeditor.theme.js',
          ],
          'images' => [
            'application-octet-stream.png',
            'application-pdf.png',
            'application-x-executable.png',
            'audio-x-generic.png',
            'forum-icons.png',
            'image-x-generic.png',
            'package-x-generic.png',
            'text-html.png',
            'text-plain.png',
            'text-x-generic.png',
            'text-x-script.png',
            'video-x-generic.png',
            'x-office-document.png',
            'x-office-presentation.png',
            'x-office-spreadsheet.png',
          ],
        ],
      ],
      'claro' => [
        'theme-name' => 'claro',
        'path-replace' => '../../../../../',
        'filenames' => [
          'css' => [
            'book-navigation.css',
            'container-inline.css',
            'exposed-filters.css',
            'field.css',
            'file.css',
            'forum.css',
            'icons.css',
            'indented.css',
            'inline-form.css',
            'item-list.css',
            'link.css',
            'links.css',
            'media-embed-error.css',
            'menu.css',
            'more-link.css',
            'node.css',
            'search-results.css',
            'tablesort.css',
            'textarea.css',
            'ui-dialog.css',
          ],
          'js' => [
            'media_embed_ckeditor.theme.es6.js',
            'media_embed_ckeditor.theme.js',
          ],
          'images' => [
            'application-octet-stream.png',
            'application-pdf.png',
            'application-x-executable.png',
            'audio-x-generic.png',
            'forum-icons.png',
            'image-x-generic.png',
            'package-x-generic.png',
            'text-html.png',
            'text-plain.png',
            'text-x-generic.png',
            'text-x-script.png',
            'video-x-generic.png',
            'x-office-document.png',
            'x-office-presentation.png',
            'x-office-spreadsheet.png',
          ],
        ],
      ],
      'seven' => [
        'theme-name' => 'seven',
        'path-replace' => '../../../../../',
        'filenames' => [
          'css' => [
            'action-links.css',
            'book-navigation.css',
            'breadcrumb.css',
            'button.css',
            'collapse-processed.css',
            'container-inline.css',
            'dropbutton.css',
            'exposed-filters.css',
            'field.css',
            'file.css',
            'form.css',
            'forum.css',
            'icons.css',
            'image-widget.css',
            'indented.css',
            'inline-form.css',
            'item-list.css',
            'link.css',
            'links.css',
            'media-embed-error.css',
            'media-library.css',
            'menu.css',
            'messages.css',
            'more-link.css',
            'node.css',
            'pager.css',
            'progress.css',
            'search-results.css',
            'tabledrag.css',
            'tableselect.css',
            'tablesort.css',
            'tabs.css',
            'textarea.css',
            'ui-dialog.css',
            'user.css',
          ],
          'js' => [
            'media_embed_ckeditor.theme.es6.js',
            'media_embed_ckeditor.theme.js',
          ],
          'images' => [
            'application-octet-stream.png',
            'application-pdf.png',
            'application-x-executable.png',
            'audio-x-generic.png',
            'forum-icons.png',
            'image-x-generic.png',
            'package-x-generic.png',
            'text-html.png',
            'text-plain.png',
            'text-x-generic.png',
            'text-x-script.png',
            'video-x-generic.png',
            'x-office-document.png',
            'x-office-presentation.png',
            'x-office-spreadsheet.png',
          ],
        ],
      ],
      // Will be populated when Classy libraries are copied to Bartik.
      'bartik' => [
        'theme-name' => 'bartik',
        'path-replace' => '../../../../../',
        'filenames' => [
          'css' => [
            'media-library.css',
            'action-links.css',
            'file.css',
            'dropbutton.css',
            'book-navigation.css',
            'tableselect.css',
            'ui-dialog.css',
            'user.css',
            'item-list.css',
            'image-widget.css',
            'field.css',
            'tablesort.css',
            'tabs.css',
            'forum.css',
            'progress.css',
            'collapse-processed.css',
            'details.css',
            'inline-form.css',
            'link.css',
            'textarea.css',
            'links.css',
            'form.css',
            'exposed-filters.css',
            'tabledrag.css',
            'indented.css',
            'messages.css',
            'pager.css',
            'search-results.css',
            'button.css',
            'node.css',
            'dialog.css',
            'menu.css',
            'icons.css',
            'breadcrumb.css',
            'media-embed-error.css',
            'container-inline.css',
            'more-link.css',
          ],
          'js' => [
            'media_embed_ckeditor.theme.es6.js',
            'media_embed_ckeditor.theme.js',
          ],
          'images' => [
            'application-octet-stream.png',
            'application-pdf.png',
            'application-x-executable.png',
            'audio-x-generic.png',
            'forum-icons.png',
            'image-x-generic.png',
            'package-x-generic.png',
            'text-html.png',
            'text-plain.png',
            'text-x-generic.png',
            'text-x-script.png',
            'video-x-generic.png',
            'x-office-document.png',
            'x-office-presentation.png',
            'x-office-spreadsheet.png',
          ],
        ],
      ],
    ];
  }

  /**
   * Gets the hash of a Classy asset.
   *
   * @param string $type
   *   The asset type.
   * @param string $file
   *   The asset filename.
   *
   * @return string
   *   A hash for the file.
   */
  protected function getClassyHash($type, $file) {
    static $hashes = [
      'css' => [
        'action-links.css' => '6abb88c2b3b6884c1a64fa5ca4853d45',
        'book-navigation.css' => 'e8219368d360bd4a10763610ada85a1c',
        'breadcrumb.css' => '14268f8071dffd40ce7a39862b8fbc56',
        'button.css' => '3abebf58e144fd4150d80facdbe5d10f',
        'collapse-processed.css' => 'e928df55485662a4499c9ba12def22e6',
        'container-inline.css' => 'ae9caee6071b319ac97bf0bb3e14b542',
        'details.css' => 'fdd0606ea856072f5e6a19ab1a2e850e',
        'dialog.css' => 'f30e4423380f5f01d02ef0a93e010c53',
        'dropbutton.css' => 'f8e4b0b81ff60206b27f622e85a6a0ee',
        'exposed-filters.css' => '396a5f76dafec5f78f4e736f69a0874f',
        'field.css' => '8f4718bc926eea7e007ecfd6f410ee8d',
        'file.css' => '7f36f62ca67c57a82f9d9e882918a01b',
        'form.css' => 'a8733b00eebffbc3293779cb779c808e',
        'forum.css' => '8aad2d86dfd29818e991757581cd7ab8',
        'icons.css' => '56f623bd343b9bc7e7ac3e3e95d7f3ce',
        'image-widget.css' => '2da54829199f64a2c390930c3b0913a3',
        'indented.css' => '48e214a106d9fede1e05aa10b4796361',
        'inline-form.css' => 'cc5cbfd34511d9021a53ec693c110740',
        'item-list.css' => '1d519afe6007f4b01e00f22b0ba8bf33',
        'link.css' => '22f42d430fe458080a7739c70a2d2ea5',
        'links.css' => '21fe64349f5702cd5b89104a1d3b9cd3',
        'media-embed-error.css' => 'ab7f4c91f7b312122d30d7e09bb1bcc4',
        'media-library.css' => 'bb405519d30970c721405452dfb7b38e',
        'menu.css' => 'c4608b4ac9aafce1f6e0d21c6e6e6ee8',
        'messages.css' => '2930ea9bebf4d1658e9bdc3b1f83bd43',
        'more-link.css' => 'b2ebfb826e035334340193b42246b180',
        'node.css' => '81ea0a3fef211dbc32549ac7f39ec646',
        'pager.css' => 'd10589366720f9c15b66df434baab4da',
        'progress.css' => '5147a9b07ede9f456c6a3f3efeb520e1',
        'search-results.css' => 'ce3ca8fcd54e72f142ba29da5a3a5c9a',
        'tabledrag.css' => '98d24ff864c7699dfa6da9190c5e70df',
        'tableselect.css' => '8e966ac85a0cc60f470717410640c8fe',
        'tablesort.css' => 'f6ed3b44832bebffa09fc3b4b6ce27ab',
        'tabs.css' => 'e58827db5c767c41b67488244c14056c',
        'textarea.css' => '2bc390c137c5205bbcd7645d6c1c86de',
        'ui-dialog.css' => '4a3d036007ba8c8c80f4a21a369c72cc',
        'user.css' => '0ec6acc22567a7c9c228f04b5a97c711',
      ],
      'js' => [
        'media_embed_ckeditor.theme.es6.js' => 'decf95c314bf22c642fb630179502e43',
        'media_embed_ckeditor.theme.js' => 'f8e192b79f25d2b61a6ff43b9733ec72',
      ],
      'images' => [
        'application-octet-stream.png' => 'fef73511632890590b5ae0a13c99e4bf',
        'application-pdf.png' => 'bb41f8b679b9d93323b30c87fde14de9',
        'application-x-executable.png' => 'fef73511632890590b5ae0a13c99e4bf',
        'audio-x-generic.png' => 'f7d0e6fbcde58594bd1102db95e3ea7b',
        'forum-icons.png' => 'dfa091b192819cc14523ccd653e7b5ff',
        'image-x-generic.png' => '9aca2e02c3cdbb391ca721d40fa4c0c6',
        'package-x-generic.png' => 'bb8581301a2030b48ff3c67374eed88a',
        'text-html.png' => '9d2d3003a786ab392d42744b2d064eec',
        'text-plain.png' => '1b769df473f54d6f78f7aba79ec25e12',
        'text-x-generic.png' => '1b769df473f54d6f78f7aba79ec25e12',
        'text-x-script.png' => 'f9dc156d35298536011ea48226b21682',
        'video-x-generic.png' => 'a5dc89b884a8a1b666c15bb41fd88ee9',
        'x-office-document.png' => '48e0c92b5dec1a027f43a5c6fe190f39',
        'x-office-presentation.png' => '8ba9f51c97a2b47de2c8c117aafd7dcd',
        'x-office-spreadsheet.png' => 'fc5d4b32f259ea6d0f960b17a0886f63',
      ],
    ];
    $this->assertArrayHasKey($type, $hashes);
    $this->assertArrayHasKey($file, $hashes[$type]);
    return $hashes[$type][$file];
  }

}
