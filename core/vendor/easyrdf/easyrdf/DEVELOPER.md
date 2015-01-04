Contributing to EasyRdf
=======================

Contributions to the EasyRdf codebase are welcome using the usual Github pull request workflow.

To run the code style checker:

```
make cs
```

You can run the PHP unit test suite with:

```
make test-lib
```

Unit tests are automatically run after being received by Github:
http://ci.aelius.com/job/easyrdf/

The tests for the examples are run separately:
http://ci.aelius.com/job/easyrdf-examples/


Notes
-----

* Please ask on the [mailing list] before starting work on any significant changes
* Please write tests for any new features or bug fixes. The tests should be checked in the same commit as the code.

[mailing list]:http://groups.google.com/group/easyrdf
