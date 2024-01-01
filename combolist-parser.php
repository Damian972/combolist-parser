<?php

/**
 * Combolist parser
 * Author: Damian972
 * Description: A simple script to filter gmail, yahoo, hotmail, orange, laposte, etc... mail with combolist like email:password
 * Version: 2.1
 * Usage: php combolist-parser.php -f list.txt.
 */
ini_set('memory_limit', '800M');
define('RESULTS_FOLDER', __DIR__.DIRECTORY_SEPARATOR.'results');
define('EMAIL_PATTERN_VALIDATOR', '/^[^@ \t\r\n]+@[^@ \t\r\n]+\.[^@ \t\r\n]+$/');
define('LOGS_FILENAME', 'logs');
define('LOGS_TEMPLATE', '[{DATETIME}] {MESSAGE} - {TARGET_FILE}');

$filters = [
    'gmail' => 'gmail.com',
    'yahoo' => ['yahoo.com', 'yahoo.fr'],
    'hotmail' => 'hotmail.com',
    'orange' => 'orange.fr',
    'laposte' => 'laposte.net',
];

$options = getopt('f:hu', ['help', 'unknow', 'logs']);
if (empty($options['f']) || isset($options['h']) || isset($options['help'])) {
    showHelp();

    exit;
}

$file = $options['f'];
if (!is_readable($file)) {
    echo '[-] File not found or readable'.PHP_EOL;

    exit;
}

$linesCount = getLinesCountForFile($file);
if (0 === $linesCount) {
    echo '[-] File is empty'.PHP_EOL;

    exit;
}

is_dir(RESULTS_FOLDER) || mkdir(RESULTS_FOLDER);
$handle = fopen($file, 'r');
if ($handle) {
    $withUnknow = isset($options['u']) || isset($options['unknow']);
    $canLog = isset($options['logs']);

    $cursorIndex = 0;
    $progressInPercent = 0;
    while (($line = fgets($handle)) !== false) {
        $consoleOutput = '';
        $line = trim($line);
        if (empty($line)) {
            ++$cursorIndex;

            continue;
        }
        $parts = explode(':', $line);
        if (2 > count($parts)) {
            if ($canLog) {
                appendToLogs("Invalid line {$cursorIndex}", $file);
            }
            ++$cursorIndex;

            continue;
        }

        $email = $parts[0];
        // implode + array_slice to prevent password with ':' in it
        $password = implode('', array_slice($parts, 1));
        if (!preg_match(EMAIL_PATTERN_VALIDATOR, $email)) {
            if ($canLog) {
                appendToLogs("Invalid email at line {$cursorIndex}", $file);
            }
            ++$cursorIndex;

            continue;
        }

        [$identifier, $emailDomain] = explode('@', $email);

        foreach ($filters as $serviceName => $domains) {
            $domains = is_array($domains) ? $domains : [$domains];
            if (in_array($emailDomain, $domains, true)) {
                appendToFile($serviceName, "{$email}:{$password}");

                break;
            }
        }

        if ($withUnknow) {
            appendToFile('unknow', "{$email}:{$password}");
        }

        ++$cursorIndex;
        $progressInPercent = round(($cursorIndex / $linesCount) * 100, 2);

        $consoleOutput .= "Progress: {$cursorIndex}/{$linesCount} ({$progressInPercent}%)  ".PHP_EOL;

        replaceOut($consoleOutput);
    }
}
echo PHP_EOL;

function getLinesCountForFile(string $file): int
{
    $linesCount = 0;
    $handle = fopen($file, 'r');
    while (!feof($handle)) {
        if (false !== fgets($handle)) {
            ++$linesCount;
        }
    }
    fclose($handle);

    return $linesCount;
}

function appendToFile(string $filename, string $value, bool $appendMode = true): void
{
    $flags = $appendMode ? FILE_APPEND | LOCK_EX : LOCK_EX;
    file_put_contents(RESULTS_FOLDER.DIRECTORY_SEPARATOR."{$filename}.txt", $value.PHP_EOL, $flags);
}

function appendToLogs(string $message, string $targetFile, bool $appendMode = true): void
{
    $message = str_replace(
        '{DATETIME}',
        date('Y-m-d H:i:s'),
        str_replace(
            '{MESSAGE}',
            $message,
            str_replace(
                '{TARGET_FILE}',
                $targetFile,
                LOGS_TEMPLATE
            )
        )
    );

    appendToFile(LOGS_FILENAME, $message, $appendMode);
}

function replaceOut(string $value): void
{
    $linesCount = substr_count($value, PHP_EOL);
    echo chr(27).'[0G';
    echo $value;
    echo chr(27).'['.$linesCount.'A';
}

function showHelp(): void
{
    $split = explode(DIRECTORY_SEPARATOR, __FILE__);
    echo 'Usage: php '.end($split).' -f list.txt'.PHP_EOL;
    echo 'Options:'.PHP_EOL;
    echo '  -f <file>       File to parse'.PHP_EOL;
    echo '  -h, --help      Show help'.PHP_EOL;
    echo '  -l, --logs      Save errors logs'.PHP_EOL;
    echo '  -u, --unknow    Save unknow results'.PHP_EOL;
}
