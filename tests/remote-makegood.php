<?php
$remote_user = 'your';
$remote_host = '192.0.2.123';
$remote_dir = '/home/your/work/project';
$local_dir = __DIR__;
$preload_script = '';
$phpunit_config = '';

///

$fn = __DIR__ . DIRECTORY_SEPARATOR . basename(__FILE__, '.php') . '.local.php';

if (file_exists($fn)) {
    /** @noinspection PhpIncludeInspection */
    require $fn;
}

$args = ['--no-ansi', 'phpunit'];

if (strlen($preload_script)) {
    $args[] = "--preload-script=$preload_script";
}

if (strlen($phpunit_config)) {
    $args[] = "--phpunit-config=$phpunit_config";
}

$win = DIRECTORY_SEPARATOR !== '/';
$prefix = $local_dir . DIRECTORY_SEPARATOR;

if ($win) {
    $prefix = str_replace(DIRECTORY_SEPARATOR, '/', strtolower($prefix));
}

$junit = null;

$argv = $_SERVER['argv'];
$args = array_reduce($argv, function ($r, $arg) use ($prefix, $win, &$junit) {
    if ($junit === null) {
        if (strncmp('--log-junit=', $arg, strlen('--log-junit=')) === 0) {
            $junit = substr($arg, strlen('--log-junit='));
        }
    }
    if ($junit !== null) {
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
        $r[] = $arg;
    }
    return $r;
}, $args);

$params = array(
    'args' => $args,
    'remote_dir' => $remote_dir,
    'local_dir' => $local_dir,
);

$file = file_get_contents(__FILE__, null, null, __COMPILER_HALT_OFFSET__);
$file = strchr($file, "\n");
$file = ltrim($file);
$file = str_replace('__IDE_PHPUNIT_PARAMS__', var_export($params, true), $file);

$cmd = sprintf(
    'plink %s -l %s -batch php 2> %s',
    escapeshellarg($remote_host),
    escapeshellarg($remote_user),
    escapeshellarg($junit)
);

$cmd = sprintf('plink %s -batch php', escapeshellarg($remote_host));

$desc = array(
    0 => array('pipe', 'r'),
    1 => STDOUT,
    2 => array('file', $junit, 'w'),
);

$proc = proc_open($cmd, $desc, $pipes);
fwrite($pipes[0], $file);
fclose($pipes[0]);
exit(proc_close($proc));

__halt_compiler();?>
<?php
/** @noinspection PhpUndefinedConstantInspection */
/** @noinspection PhpUnreachableStatementInspection */
$params = __IDE_PHPUNIT_PARAMS__;

$args = $params['args'];
$remote_dir = $params['remote_dir'];
$local_dir = $params['local_dir'];

chdir($remote_dir);

$tmp = tempnam(sys_get_temp_dir(), "makegood-junit-");
unlink($tmp);
posix_mkfifo($tmp, 0600);

try {
    $args = array_reduce($args, function ($r, $arg) use ($tmp) {
        if (strncmp('--log-junit=', $arg, strlen('--log-junit=')) === 0) {
            $arg = "--log-junit=$tmp";
        }
        $r[] = $arg;
        return $r;
    }, []);

    $cmd = array_merge(['vendor/bin/testrunner'], array_map(function ($arg) {
        return escapeshellarg($arg);
    }, $args));

    $cmd = implode(" ", $cmd);

    $desc = array(
        0 => array('file', '/dev/null', 'r'),
        1 => array('file', 'php://stdout', 'w'),
        2 => array('file', 'php://stdout', 'w'),
    );

    $proc = proc_open($cmd, $desc, $pipes);

    $fifo = fopen($tmp, 'r');
    $stderr = fopen('php://stderr', 'w');

    while (strlen($data = fread($fifo, 1024)) !== 0) {
        $data = str_replace($remote_dir, $local_dir, $data);
        fwrite($stderr, $data);
    }

    $exit = proc_close($proc);
} finally {
    unlink($tmp);
}

exit($exit);
