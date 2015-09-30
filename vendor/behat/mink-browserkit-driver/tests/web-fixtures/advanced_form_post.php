<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
    <title>Advanced form save</title>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
</head>
<body>
<?php
error_reporting(0);

$request = $app['request'];
$POST = $request->request->all();
$FILES = $request->files->all();

if (isset($POST['select_multiple_numbers']) && false !== strpos($POST['select_multiple_numbers'][0], ',')) {
    $POST['select_multiple_numbers'] = explode(',', $POST['select_multiple_numbers'][0]);
}

// checkbox can have any value and will be successful in case "on"
// http://www.w3.org/TR/html401/interact/forms.html#checkbox
$POST['agreement'] = isset($POST['agreement']) ? 'on' : 'off';
ksort($POST);
echo str_replace('>', '', var_export($POST, true)) . "\n";
if (isset($FILES['about']) && file_exists($FILES['about']->getPathname())) {
    echo $FILES['about']->getClientOriginalName() . "\n";
    echo file_get_contents($FILES['about']->getPathname());
} else {
    echo "no file";
}
?>
</body>
</html>
