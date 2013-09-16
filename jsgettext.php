#!/usr/bin/env php
<?php
function buildOptions($args) {
    $options = array(
        'files' => array(),
        '-o'	=> null,
        '-k'	=> '_',
        '-l'    => 'php://stdout'
    );
    $len = count($args);
    $i = 0;
    while ($i < $len) {
        if (preg_match('#^-[a-z]$#i', $args[$i])) {
            $options[$args[$i]] = isset($args[$i+1]) ? trim($args[$i+1]) : true;
            $i++;
        }
        elseif ($args[$i] !== __FILE__) {
            $options['files'][] = $args[$i];
        }
        $i++;
    }
    return $options;
}

$options = buildOptions($argv);

$fp = new SplFileObject($options['-l'], 'a');
if ('php://stdout' !== $options['-l']) {
    if (!$fp->getSize() || $fp->getMTime() < time() - 5) {
        $fp->ftruncate(0);
        $fp->fwrite('#!/bin/sh');
        $fp->fwrite(PHP_EOL);
        chmod($fp->getPathname(), $fp->getPerms() | 0110); // chomd ug+w
    }

    $fp->fwrite(PHP_EOL);
}
$fp->fwrite('# '.date('Y.m.d H:i:s'));
$fp->fwrite(PHP_EOL);

$fp->fwrite('php');
foreach($argv as $arg) {
    if ('-' !== substr($arg, 0, 1)) {
        $arg = escapeshellarg($arg);
    }
    $fp->fwrite(' '.$arg);
}
$fp->fwrite(PHP_EOL);

include_once __DIR__.'/JSParser.php';
include_once __DIR__.'/PoeditParser.php';

if (!file_exists(basename($options['-o'])) || !is_writable(basename($options['-o']))) {
    mkdir(basename($options['-o']), 0777, true);
}
if (!file_exists($options['-o']) || !is_writable($options['-o'])) {
    touch($options['-o']);
}

if (!file_exists($options['-o']) || !is_writable($options['-o'])) {
    if ('php://stdout' !== $options['-l']) {
        $fp->fwrite('Invalid output file name. Make sure it exists and is writable.');
        $fp->fwrite(PHP_EOL);
    }
    fwrite(STDERR, "Invalid output file name. Make sure it exists and is writable.".PHP_EOL);
    exit(-1);
}

$inputFiles = $options['files'];

if (empty($inputFiles)) {
    if ('php://stdout' !== $options['-l']) {
        $fp->fwrite('You did not provide any input file.');
        $fp->fwrite(PHP_EOL);
    }
    fwrite(STDERR, "You did not provide any input file.".PHP_EOL);
    exit(-1);
}

$poeditParser = new PoeditParser($options['-o']);
$poeditParser->parse();

$errors = array();

foreach ($inputFiles as $f) {
    if (!is_readable($f) || !preg_match('#\.js$#', $f)) {
        $errors[] = ("$f is not a valid javascript file.");
        continue;
    }
    $jsparser = new JSParser($f, explode(' ', $options['-k']));
    $jsStrings = $jsparser->parse();
    $poeditParser->merge($jsStrings);
}

if (!empty($errors)) {
    $fp->fwrite("\nThe following errors occured:\n" . implode("\n", $errors) . "\n");
    $fp->fwrite(PHP_EOL);
    fwrite(STDERR, "\nThe following errors occured:\n" . implode("\n", $errors) . "\n");
    exit(-1);
}

$poeditParser->save();