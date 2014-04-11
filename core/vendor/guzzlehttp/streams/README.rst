==============
Guzzle Streams
==============

Provides a simple abstraction over streams of data.

This library is used in `Guzzle 4 <https://github.com/guzzle/guzzle>`_ and is
an implementation of the proposed `PSR-7 stream interface <https://github.com/php-fig/fig-standards/blob/master/proposed/http-message.md#34-psrhttpstreaminterface>`_.

Installation
============

This package can be installed easily using `Composer <http://getcomposer.org>`_.
Simply add the following to the composer.json file at the root of your project:

.. code-block:: javascript

    {
      "require": {
        "guzzlehttp/streams": "1.*"
      }
    }

Then install your dependencies using ``composer.phar install``.

Documentation
=============

The documentation for this package can be found on the main Guzzle website at
http://docs.guzzlephp.org/en/guzzle4/streams.html.

Testing
=======

This library is tested using PHPUnit. You'll need to install the dependencies
using `Composer <http://getcomposer.org>`_ then run ``make test``.
