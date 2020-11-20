--TEST--
Reproduce octal output from stream wrapper invocation

See https://bugs.php.net/bug.php?id=71287
See https://github.com/mikey179/vfsStream/issues/120
--FILE--
<?php
class Stream {
    public function stream_open($path, $mode, $options, $opened_path) {

        return true;
    }

    public function stream_write($data) {
        return (int) (strlen($data) - 2);
    }
}

stream_wrapper_register('test', 'Stream');
file_put_contents('test://file.txt', 'foobarbaz');
?>
--EXPECTF--
Warning: file_put_contents(): Only 7 of 9 bytes written, possibly out of free disk space in %s on line %d