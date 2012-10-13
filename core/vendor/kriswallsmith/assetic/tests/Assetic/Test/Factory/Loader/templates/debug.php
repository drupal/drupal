<assets>
    <?php foreach (assetic_stylesheets(
        array('foo.css', 'bar.css'),
        array('?foo', 'bar'),
        array('name' => 'test123', 'output' => 'css/packed.css', 'debug' => true)) as $url): ?>
        <asset url="<?php echo $url ?>" />
    <?php endforeach; ?>
</assets>
