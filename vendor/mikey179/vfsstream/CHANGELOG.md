1.6.5 (2017-08-01)
------------------

   * fixed #157 seeking before beginning of file should fail, reported and fixed by @merijnvdk
   * structure array in `vfsStream::create()` and `vfsStream::setup()` now can contain instances of `org\bovigo\vfs\content\FileContent` and `org\bovigo\vfs\vfsStreamFile`, patch provivded by Joshua Smith (@jsmitty12)


1.6.4 (2016-07-18)
------------------

   * fixed #134 type safe directory names, reported and fixed by Sebastian Hopfe


1.6.3 (2016-04-09)
------------------

   * fixed #131 recursive mkdir() fails if the last dirname is '0'


1.6.2 (2016-01-13)
------------------

   * fixed #128 duplicate "valid" files/directories and incorrect file names


1.6.1 (2015-12-04)
------------------

   * `vfsStream::url()` didn't urlencode single path parts while `vfsStream::path()` did urldecode them
   * fixed #120, #122: create directory with trailing slash results in "Uninitialized string offset: 0"


1.6.0 (2015-10-06)
------------------

   * added `vfsStreamWrapper::unregister()`, provided by @malkusch with #114
   * fixed #115: incorrect handling of `..` in root directory on PHP 5.5, fix provided by @acoulton with #116


1.5.0 (2015-03-29)
------------------

   * implemented #91: `vfsStream::copyFromFileSystem()` should create large file instances
   * implemented #92: `vfsStream::copyFromFileSystem()` should respect block devices
   * fixed #107: `touch()` does not respect file permissions
   * fixed #105: vfs directory structure is not reset after each test
   * fixed #104: vfsStream can't handle url encoded pathes


1.4.0 (2014-09-14)
------------------

   * implemented #85: Added support for emulating block devices in the virtual filesystem, feature provided by Harris Borawski
   * fixed #68: Unlink a non-existing file now triggers a PHP warning


1.3.0 (2014-07-21)
------------------

   * implemented #79: possibility to mock large files without large memory footprint, see https://github.com/mikey179/vfsStream/wiki/MockingLargeFiles
   * implemented #67: added partial support for text-mode translation flag (i.e., no actual translation of line endings takes place) so it no longer throws an exception (provided by Anthon Pang)
   * fixed issue #74: issue with trailing windows path separators (provided by Sebastian Krüger)
   * fixed issue #50: difference between real file system and vfs with `RecursiveDirectoryIterator`
   * fixed issue #80: touch with no arguments for modification and access time behave incorrect
   * deprecated `org\bovigo\vfs\vfsStreamFile::readUntilEnd()`
   * deprecated `org\bovigo\vfs\vfsStreamFile::getBytesRead()`


1.2.0 (2013-04-01)
------------------

   * implemented issue #34: provide `url()` method on all `vfsStreamContent` instances
     * added `org\bovigo\vfs\vfsStreamContent::url()`
     * added `org\bovigo\vfs\vfsStreamContent::path()`
   * fixed issue #40: flock implementation doesn't work correctly, patch provided by Kamil Dziedzic
   * fixed issue #49: call to member function on a non-object when trying to delete a file one above root where a file with same name in root exists
   * fixed issue #51: `unlink()` must consider permissions of directory where file is inside, not of the file to unlink itself
   * fixed issue #52: `chmod()`, `chown()` and `chgrp()` must consider permissions of directory where file/directory is inside
   * fixed issue #53: `chmod()`, `chown()` and `chgrp()` must consider current user and current owner of file/directoy to change


1.1.0 (2012-08-25)
------------------

   * implemented issue #11: add support for `streamWrapper::stream_metadata()` vfsStream now supports `touch()`, `chown()`, `chgrp()` and `chmod()`
   * implemented issue #33: add support for `stream_truncate()` (provided by https://github.com/nikcorg)
   * implemented issue #35: size limit (quota) for VFS


1.0.0 (2012-05-15)
------------------

   * raised requirement for PHP version to 5.3.0
   * migrated codebase to use namespaces
   * changed distribution from PEAR to Composer
   * implemented issue #30: support "c" mode for `fopen()`
   * fixed issue #31: prohibit aquiring locks when already locked / release lock on `fclose()`
   * fixed issue #32: problems when subfolder has same name as folder
   * fixed issue #36: `vfsStreamWrapper::stream_open()` should return false while trying to open existing non-writable file, patch provided by Alexander Peresypkin


0.11.2 (2012-01-14)
-------------------

   * fixed issue #29: set permissions properly when using `vfsStream::copyFromFileSystem()`, patch provided by predakanga
   * fixed failing tests under PHP > 5.3.2


0.11.1 (2011-12-04)
-------------------

   * fixed issue #28: `mkdir()` overwrites existing directories/files


0.11.0 (2011-11-29)
-------------------

   * implemented issue #20: `vfsStream::create()` removes old structure
   * implemented issue #4: possibility to copy structure from existing file system
   * fixed issue #23: `unlink()` should not remove any directory
   * fixed issue #25: `vfsStreamDirectory::hasChild()` gives false positives for nested paths, patch provided by Andrew Coulton
   * fixed issue #26: opening a file for reading only should not update its modification time, reported and initial patch provided by Ludovic Chabant


0.10.1 (2011-08-22)
-------------------

   * fixed issue #16: replace `vfsStreamContent` to `vfsStreamContainer` for autocompletion
   * fixed issue #17: `vfsStream::create()` has issues with numeric directories, patch provided by mathieuk


0.10.0 (2011-07-22)
-------------------

   * added new method `vfsStreamContainer::hasChildren()` and `vfsStreamDirectory::hasChildren()`
   * implemented issue #14: less verbose way to initialize vfsStream
   * implemented issue #13: remove deprecated method `vfsStreamContent::setFilemtime()`
   * implemented issue #6: locking meachanism for files
   * ensured that `stream_set_blocking()`, `stream_set_timeout()` and `stream_set_write_buffer()` on vfsStream urls have the same behaviour with PHP 5.2 and 5.3
   * implemented issue #10: method to print directory structure


0.9.0 (2011-07-13)
------------------

   * implemented feature request issue #7: add support for `fileatime()` and `filectime()`
   * fixed issue #3: add support for `streamWrapper::stream_cast()`
   * fixed issue #9: resolve path not called everywhere its needed
   * deprecated `vfsStreamAbstractContent::setFilemtime()`, use `vfsStreamAbstractContent::lastModified()` instead, will be removed with 0.10.0


0.8.0 (2010-10-08)
------------------

   * implemented enhancement #6: use `vfsStream::umask()` to influence initial file mode for files and directories
   * implemented enhancement #19: support of .. in the url, patch provided by Guislain Duthieuw
   * fixed issue #18: `getChild()` returns NULL when child's name contains parent name
   * fixed bug with incomplete error message when accessing non-existing files on root level


0.7.0 (2010-06-08)
------------------

   * added new `vfsStream::setup()` method to simplify vfsStream usage
   * fixed issue #15: `mkdir()` creates a subfolder in a folder without permissions


0.6.0 (2010-02-15)
------------------

   * added support for `$mode` param when opening files, implements enhancement #7 and fixes issue #13
   * `vfsStreamWrapper::stream_open()` now evaluates `$options` for `STREAM_REPORT_ERRORS`


0.5.0 (2010-01-25)
------------------

   * added support for `rename()`, patch provided by Benoit Aubuchon
   * added support for . as directory alias so that `vfs://foo/.` resolves to `vfs://foo`, can be used as workaround for bug #8


0.4.0 (2009-07-13)
------------------

   * added support for file modes, users and groups (with restrictions, see http://code.google.com/p/bovigo/wiki/vfsStreamDocsKnownIssues)
   * fixed bug #5: `vfsStreamDirectory::addChild()` does not replace child with same name
   * fixed bug with `is_writable()` because of missing `stat()` fields, patch provided by Sergey Galkin


0.3.2 (2009-02-16)
------------------

   * support trailing slashes on directories in vfsStream urls, patch provided by Gabriel Birke
   * fixed bug #4: vfsstream can only be read once, reported by Christoph Bloemer
   * enabled multiple iterations at the same time over the same directory


0.3.1 (2008-02-18)
------------------

   * fixed path/directory separator issues under linux systems
   * fixed uid/gid issues under linux systems


0.3.0 (2008-01-02)
------------------

   * added support for `rmdir()`
   * added `vfsStream::newDirectory()`, dropped `vfsStreamDirectory::ceate()`
   * added new interface `vfsStreamContainer`
   * added `vfsStreamContent::at()` which allows code like `$file = vfsStream::newFile('file.txt.')->withContent('foo')->at($otherDir);`
   * added `vfsStreamContent::lastModified()`, made `vfsStreamContent::setFilemtime()` an alias for this
   * moved from Stubbles development environment to bovigo
   * refactorings to reduce crap index of various methods


0.2.0 (2007-12-29)
------------------

   * moved `vfsStreamWrapper::PROTOCOL` to `vfsStream::SCHEME`
   * added new `vfsStream::url()` method to assist in creating correct vfsStream urls
   * added `vfsStream::path()` method as opposite to `vfsStream::url()`
   * a call to `vfsStreamWrapper::register()` will now reset the root to null, implemented on request from David Zuelke
   * added support for `is_readable()`, `is_dir()`, `is_file()`
   * added `vfsStream::newFile()` to be able to do `$file = vfsStream::newFile("foo.txt")->withContent("bar");`


0.1.0 (2007-12-14)
------------------

   * Initial release.
