<?php
$remote_host = '192.0.2.123';
$remote_dir = '/home/your/work/project';
$local_dir = __DIR__;

///

$fn = __DIR__ . DIRECTORY_SEPARATOR . basename(__FILE__, '.php') . '.local.php';

if (file_exists($fn)) {
    /** @noinspection PhpIncludeInspection */
    require $fn;
}

$win = DIRECTORY_SEPARATOR !== '/';
$prefix = $local_dir . DIRECTORY_SEPARATOR;

if ($win) {
    $prefix = str_replace(DIRECTORY_SEPARATOR, '/', strtolower($prefix));
}

$argv = $_SERVER['argv'];
$argv = array_map(function ($arg) use ($prefix, $win) {
    if (file_exists($arg)) {
        $mat = $arg;
        if ($win) {
            $mat = str_replace(DIRECTORY_SEPARATOR, '/', strtolower($mat));
        }
        if (strncmp($mat, $prefix, strlen($prefix)) === 0) {
            $arg = './' . substr($arg, strlen($prefix));
            if ($win) {
                $arg = str_replace(DIRECTORY_SEPARATOR, '/', $arg);
            }
        }
    }
    return $arg;
}, $argv);

$params = array(
    'remote_dir' => $remote_dir,
    'local_dir' => $local_dir,
    'argv' => $argv,
);

$file = ltrim(substr(file_get_contents(__FILE__), __COMPILER_HALT_OFFSET__));
$file = str_replace('__IDE_PHPUNIT_PARAMS__', var_export($params, true), $file);
$file .= '?' . '>';
$file .= file_get_contents($_SERVER['SCRIPT_FILENAME']);

$cmd = sprintf('plink %s -batch env IDE_DEBUG=%d bash',
    escapeshellarg($remote_host),
    isset($_SERVER['XDEBUG_CONFIG'])
);

$pp = popen($cmd, "wb");
fwrite($pp, $file);
stream_copy_to_stream($pp, STDOUT);
pclose($pp);
exit;
__halt_compiler();
#!/bin/bash

if [ "$IDE_DEBUG" -ne 0 ]; then
    export XDEBUG_CONFIG="remote_host=$(echo $SSH_CLIENT | awk '{print $1}')"
    export PHP_IDE_CONFIG="serverName=$(hostname -s)"
fi

exec php
<?php
$params = __IDE_PHPUNIT_PARAMS__;

$remote_dir = $params['remote_dir'];
$local_dir = $params['local_dir'];
$argv = $params['argv'];
$argc = count($argv);

$_SERVER['argv'] = $argv;
$_SERVER['argc'] = $argc;

chdir($remote_dir);

if (file_exists("$remote_dir/vendor/autoload.php")) {
    /** @noinspection PhpIncludeInspection */
    require "$remote_dir/vendor/autoload.php";
}

ob_start(function ($out) use ($remote_dir, $local_dir) {
    return str_replace($remote_dir, $local_dir, $out);
});
