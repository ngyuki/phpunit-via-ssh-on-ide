<?php
$remote_user = 'your';
$remote_host = '192.0.2.123';
$remote_dir = '/home/your/work/project';
$local_dir = dirname(__DIR__);

/*
 * IDE [Settings -> PHP -> PHPUnit]
 *  - Use custom loader: on
 *  - Path to script: /path/to/project/tests/misc/remote-phpstorm.php
 */

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
    'argv' => $argv,
    'remote_dir' => $remote_dir,
    'local_dir' => $local_dir,
);

$file = file_get_contents(__FILE__, null, null, __COMPILER_HALT_OFFSET__);
$file = strchr($file, "\n");
$file = ltrim($file);
$file = str_replace('__IDE_PHPUNIT_PARAMS__', var_export($params, true), $file);

$file .= '?' . '>';
$file .= file_get_contents($_SERVER['SCRIPT_FILENAME']);

if (isset($_SERVER['XDEBUG_CONFIG'])) {
    $cmd = sprintf(
        'plink %s -l %s -batch env %s php',
        escapeshellarg($remote_host),
        escapeshellarg($remote_user),
        'XDEBUG_CONFIG="remote_host=${SSH_CLIENT%% *} PHP_IDE_CONFIG="serverName=${HOSTNAME%%.*}"'
    );
} else {
    $cmd = sprintf(
        'plink %s -l %s -batch php',
        escapeshellarg($remote_host),
        escapeshellarg($remote_user)
    );
}

$desc = array(
    0 => array('pipe', 'r'),
    1 => STDOUT,
    2 => STDERR,
);

$proc = proc_open($cmd, $desc, $pipes);
fwrite($pipes[0], $file);
fclose($pipes[0]);
exit(proc_close($proc));

__halt_compiler();
<?php
/** @noinspection PhpUnreachableStatementInspection */
/** @noinspection PhpUndefinedConstantInspection */
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

ob_implicit_flush(true);
ob_start(function ($out) use ($remote_dir, $local_dir) {
    return str_replace($remote_dir, $local_dir, $out);
}, 2);
