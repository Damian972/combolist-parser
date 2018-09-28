<?php

    /**
     * Combolist parser
     * Author: Damian972
     * Description: A simple script to filter gmail, yahoo, hotmail, orange, laposte, etc... mail with combolist like email:password
     * Version: 1.0
     * Usage: php combolist-parser.php list.txt
     */
    
    define('ROOT', __DIR__);
    define('RESULTS_FOLDER', ROOT.DIRECTORY_SEPARATOR.'results'.DIRECTORY_SEPARATOR);
    ini_set('memory_limit', '800M');

    $filter = array(
        'gmail' => 'gmail.com',
        'yahoo' => ['yahoo.com', 'yahoo.fr'],
        'hotmail' => 'hotmail.com',
        'orange' => 'orange.fr',
        'laposte' => 'laposte.net'
    );

    if($argc === 2){
        is_dir(RESULTS_FOLDER) || mkdir(RESULTS_FOLDER);
        $file = $argv[1];
        if(is_readable($file)){
            $handle = fopen($file, 'r');
            if ($handle){
                $total_lines = count_lines($file);
                echo 'Welcome in Combolist parser'.PHP_EOL;
                echo '[!] Total lines to parse in '.$file.': '.$total_lines.PHP_EOL;
                while (($line = trim(fgets($handle))) !== false){
                    if (!empty($line)){
                        $is_unknow = true;
                        foreach ($filter as $key => $value){
                            $splitted_line = explode('@', $line);
                            $tmp_value = explode(':', $splitted_line[1]);
                            if (is_array($value)){
                                for ($i = 0; $i < count($value); $i++){
                                    if ($tmp_value[0] === $value[$i]){
                                        file_put_contents(RESULTS_FOLDER.$key.'.txt', $line.PHP_EOL, FILE_APPEND | LOCK_EX);
                                        $is_unknow = false;
                                    }
                                }
                            }else{
                                if ($tmp_value[0] === $value){
                                    file_put_contents(RESULTS_FOLDER.$key.'.txt', $line.PHP_EOL, FILE_APPEND | LOCK_EX);
                                    $is_unknow = false;
                                }
                            }
                        }
                        if($is_unknow) file_put_contents(RESULTS_FOLDER.'unknow.txt', $line.PHP_EOL, FILE_APPEND | LOCK_EX);
                    }
                }
                fclose($handle);
            }
        }else { echo '[-] File not found'.PHP_EOL; }
    }else { echo '[!] Usage: php combolist-parser.php list.txt'.PHP_EOL; }

    function count_lines(string $file){
        $total_lines = 0;
        if (file_exists($file)){
            $handle = fopen($file, "r");
            while (!feof($handle)){ if (fgets($handle) !== false) $total_lines += 1; }
            fclose($handle);
        } return $total_lines;
    }