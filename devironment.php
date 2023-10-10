#!/usr/bin/php
<?php

$appName = "Devironment";
$version = "2.4.0";
$author = "S4F4Y4T";

echo "    
________   _______________   ____.___ __________ ________    _______      _____   ___________ _______ ___________ 
\______ \  \_   _____/\   \ /   /|   |\______   \\_____  \   \      \    /     \  \_   _____/ \      \\__    ___/ 
 |    |  \  |    __)_  \   Y   / |   | |       _/ /   |   \  /   |   \  /  \ /  \  |    __)_  /   |   \ |    |    
 |    `   \ |        \  \     /  |   | |    |   \/    |    \/    |    \/    Y    \ |        \/    |    \|    |    
/_______  //_______  /   \___/   |___| |____|_  /\_______  /\____|__  /\____|__  //_______  /\____|__  /|____|    
        \/         \/                         \/         \/         \/         \/         \/         \/                                                                                                                                                                                            
";
echo PHP_EOL;
echo "# Version: " . $version . PHP_EOL;
echo "# Author: " . $author . PHP_EOL;

//all available commands and there actions
$cmd_lists = [
    '--v' => [
        'description' => 'View current version of the script',
        'action' => 'version'
    ],
    'kill' => [
        'description' => 'Exit the program',
        'action' => 'kill'
    ],
    'list' => [
        'description' => 'List all available commands',
        'action' => 'lists'
    ],
    'vhost' => [
        'description' => 'Proceed to make virtual host, e.g: vhost [domain] [project name] [directory]',
        'action' => 'vhost'
    ],
    'apache' => [
        'description' => 'Install Apache',
        'action' => 'apache'
    ],
    'mysqldump' => [
        'description' => 'Backup Mysql Dump Ex: mysqldump [username] [password] [dbname] [backupfile] --optional [host] --optional',
        'action' => 'mysqldump'
    ]
];

//instruction to see the available commands of program
echo PHP_EOL;
echo "Enter command 'list' to see all the available commands" . PHP_EOL;
echo PHP_EOL;

$logic = ['status' => 3, 'message' => ''];
while ($logic['status'] === 3) {
    if (!empty($logic['message'])) {
        switch ($logic['type']) {
            case 'success':
                echo "\033[32m" . $logic['message'] . "\033[0m" . PHP_EOL;
                break;
            default:
                echo "\033[31m" . $logic['message'] . "\033[0m" . PHP_EOL;
                break;
        }
    }

    if (posix_geteuid() !== 0) {

        $logic = ['status' => 0, 'type' => 'error', 'message' => 'This script requires superuser (sudo) privileges.'];

    }else{

        echo "Enter Command: ";
        $command_input = trim(fgets(STDIN)); // Read the command input from user

        $commands = explode(" ", $command_input); // Explode the command by spaces

        $action = $commands[0]; // Get the first index as the command

        $cmd_opt = array_slice($commands, 1); // Get the remaining parts as arguments in an array

        if (array_key_exists($action, $cmd_lists)) {

            $logic = $cmd_lists[$action]['action'](...$cmd_opt);

        } else {
            echo "Command not found! Use 'list' command to see all available command" . PHP_EOL;
            $logic = ['status' => 3, 'message' => ''];
        }

    }

}

//all actions function
function version(): array
{
    global $version;
    echo $version . PHP_EOL;

    return ['status' => 3, 'message' => ''];
}

function kill(): array
{
    return ['status' => 0, 'message' => 'Exiting Program...'];
}

function lists(): array
{
    global $cmd_lists;
    foreach ($cmd_lists as $key => $val) {
        echo '  []' . $key . ' - ' . $val['description'] . PHP_EOL;
    }

    return ['status' => 3, 'message' => ''];
}

function vhost($domain = "", $project_name = "" , $dir = "")
{
    require_once 'vhost/init.php';

    $apacheStatus = shell_exec('systemctl is-active apache2'); // Ubuntu/Debian-specific command

    if (trim($apacheStatus) !== 'active') {
        return ['status' => 3, 'type' => 'error', 'message' => 'Apache service is not active.'];
    }

    $init = new Init($domain = "", $project_name = "" , $dir = "");
    return $init->make();
}

function apache()
{
    if (posix_geteuid() !== 0) {
        return ['status' => 3, 'type' => 'error', 'message' => 'This script requires superuser (sudo) privileges. '];
    }

    $command_list = [
        'sudo apt-get update',
        'sudo apt-get install apache2',
        'ufw allow in "Apache"',
        'sudo systemctl restart apache2',
    ];

    $command = implode(';', $command_list);
    shell_exec($command);

    return ['status' => 1, 'message' => 'Apache installed successfully. Go to http://localhost'];
}

function mysqldump($username = "", $password = "", $db = "")
{
    $dbConfig = __DIR__ . '/.db.cnf';

    // Check if the option file exists
    if (file_exists($dbConfig)) {

        // Option file exists, update the content
        $content = "[client]\nuser=$username\npassword=$password\n";
        file_put_contents($dbConfig, $content);
        echo "DB Config updated.\n" . PHP_EOL;

    } else {

        // Option file doesn't exist, create a new one
        $content = "[client]\nuser=$username\npassword=$password\n";
        if (file_put_contents($dbConfig, $content) !== false) {

            chmod($dbConfig, 0600); // Set permissions to make it readable and writable only by the owner
            echo "DB Config created.\n" . PHP_EOL;

        } else {

            echo "Unable to write content to the file.";
            return ['status' => 3, 'type' => 'error', 'message' => 'Unable to write content to the file.'];
        }

    }

    exec('mysql --defaults-extra-file=' . $dbConfig . ' -e "SELECT 1"', $output, $return_var);

    // Check if the command was successful
    if ($return_var !== 0) {
        // Command failed, and $output may contain error messages
        return ['status' => 3, 'type' => 'error', 'message' => 'Invalid DB Config '];
    }


    $downloadDir = '/home/' . get_current_user() . '/Downloads/';
    if (!$downloadDir) {
        echo "Download directory not found.";
        return ['status' => 3, 'type' => 'error', 'message' => 'Download directory not found.'];
    }

    $command = 'mysqldump --defaults-extra-file=' . $dbConfig . ' ' . $db . ' > ' . $downloadDir . $db . '~' . date("Y-m-d") . '.sql';

    exec($command, $output, $return_var);

    // Check if the command was successful
    if ($return_var === 0) {

        return ['status' => 3, 'type' => 'success', 'message' => 'Backup Successful'];

    } else {
        // Command failed, and $output may contain error messages
        $messages = implode("\n", $output);
        return ['status' => 3, 'type' => 'error', 'message' => 'Backup Failed: ' . $messages];
    }
}

switch ($logic['status']) {
    case 1:
        echo "\033[32m" . $logic['message'] . "\033[0m" . PHP_EOL;
        break;
    case 0:
        echo "\033[31m" . $logic['message'] . "\033[0m" . PHP_EOL;
        break;
    default:
        echo "Exiting program..." . PHP_EOL;
}





