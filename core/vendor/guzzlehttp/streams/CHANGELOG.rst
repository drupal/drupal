=========
Changelog
=========

1.5.1 (2014-09-10)
------------------

* Stream metadata is grabbed from the underlying stream each time
  ``getMetadata`` is called rather than returning a value from a cache.
* Properly closing all underlying streams when AppendStream is closed.
* Seek functions no longer throw exceptions.
* LazyOpenStream now correctly returns the underlying stream resource when
  detached.

1.5.0 (2014-08-07)
------------------

* Added ``Stream\safe_open`` to open stream resources and throw exceptions
  instead of raising errors.

1.4.0 (2014-07-19)
------------------

* Added a LazyOpenStream

1.3.0 (2014-07-15)
------------------

* Added an AppendStream to stream over multiple stream one after the other.

1.2.0 (2014-07-15)
------------------

* Updated the ``detach()`` method to return the underlying stream resource or
  ``null`` if it does not wrap a resource.
* Multiple fixes for how streams behave when the underlying resource is
  detached
* Do not clear statcache when a stream does not have a 'uri'
* Added a fix to LimitStream
* Added a condition to ensure that functions.php can be required multiple times
