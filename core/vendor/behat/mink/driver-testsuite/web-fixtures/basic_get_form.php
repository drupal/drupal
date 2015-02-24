<!DOCTYPE html>
<html>
<head>
    <title>Basic Get Form</title>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
</head>
<body>
    <h1>Basic Get Form Page</h1>

    <div id="serach">
        <?php echo isset($_GET['q']) && $_GET['q'] ? $_GET['q'] : 'No search query' ?>
    </div>

    <form>
        <input name="q" value="" type="text" />

        <input type="submit" value="Find" />
    </form>
</body>
</html>
