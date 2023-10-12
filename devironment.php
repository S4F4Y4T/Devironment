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
echo PHP_EOL;

$logic = ['status' => 3, 'message' => ''];
if (posix_geteuid() !== 0) {
    $logic = ['status' => 0, 'type' => 'error', 'message' => 'This script requires superuser (sudo) privileges.'];
}else{
    //instruction to see the available commands of program
    echo PHP_EOL;
    echo "Enter command 'list' to see all the available commands" . PHP_EOL;
    echo PHP_EOL;
}

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
        'description' => 'Manage Virtual Hosts. EX: vhost [action] [domain] [project name] --optional [directory] --optional',
        'action' => 'vhost'
    ],
//    'apache' => [
//        'description' => 'Install Apache',
//        'action' => 'apache'
//    ],
//    'mysqldump' => [
//        'description' => 'Backup Mysql Dump Ex: mysqldump [username] [password] [dbname] [backupfile] --optional [host] --optional',
//        'action' => 'mysqldump'
//    ]
];

while ($logic['status'] === 3) {

    if (!empty($logic['message'])) {
        switch ($logic['type']) {
            case 'success':
                echo "\033[32m" . $logic['message'] . "\033[0m" . PHP_EOL;
                break;
            case 'error':
                echo "\033[31m" . $logic['message'] . "\033[0m" . PHP_EOL;
                break;
            default:
                echo $logic['message'] . PHP_EOL;
                break;
        }
    }

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

//all actions function
function version(): array
{
    global $version;
    echo $version . PHP_EOL;

    return ['status' => 3, 'message' => ''];
}

function kill(): array
{
    echo "Exiting Program...";
    return ['status' => 0, 'message' => ''];
}

function lists(): array
{
    global $cmd_lists;
    foreach ($cmd_lists as $key => $val) {
        echo '  [] ' . $key . ' - ' . $val['description'] . PHP_EOL;
    }

    return ['status' => 3, 'message' => ''];
}

function vhost(string $action = "", string $domain = "", string $project_name = "" , string $dir = "")
{
    require_once 'vhost/init.php';

    // validate if apache is active
    $apacheStatus = shell_exec('systemctl is-active apache2');
    if (trim($apacheStatus) !== 'active') {
        return ['status' => 3, 'type' => 'error', 'message' => 'Apache service is not active.'];
    }

    $init = new Init($domain, $project_name , $dir);
    $options = $init->getOptions();

    if(empty($action) && !empty($options)){
        echo PHP_EOL;
        echo "Action you want to perform?" . PHP_EOL;
        foreach ($options as $option){
            echo "  ".$option['action']."- ".$option['description']. PHP_EOL;
        }
    }

    //take action user wants to perform
    while (!$init->validateOption($action)) {

        echo "Choose your action: ";
        $action = strtolower(trim(fgets(STDIN)));

        if (!$init->validateOption($action)) {
            echo "Error: Invalid action" . PHP_EOL;
        }
    }

    switch ($action) {
        case 'create':
            $response = $init->create();
            break;
        case 'list':
            $response = $init->list();
            break;
        case 'enable':
            $response = $init->enable();
            break;
        case 'disable':
            $response = $init->disable();
            break;
        default:
            $response = ['status' => 3, 'type' => '', 'message' => 'No action found'];
            break;
    }

    return $response;
}

function vlist($domain = "")
{
    require_once 'vhost/init.php';

    $apacheStatus = shell_exec('systemctl is-active apache2'); // Ubuntu/Debian-specific command

    if (trim($apacheStatus) !== 'active') {
        return ['status' => 3, 'type' => 'error', 'message' => 'Apache service is not active.'];
    }

    $init = new Init($domain);
    return $init->list();
}

function vactive($domain = "")
{
    require_once 'vhost/init.php';

    $apacheStatus = shell_exec('systemctl is-active apache2'); // Ubuntu/Debian-specific command

    if (trim($apacheStatus) !== 'active') {
        return ['status' => 3, 'type' => 'error', 'message' => 'Apache service is not active.'];
    }

    $init = new Init($domain);
    return $init->active();
}
function vInActive($domain = "")
{
    require_once 'vhost/init.php';

    $apacheStatus = shell_exec('systemctl is-active apache2'); // Ubuntu/Debian-specific command

    if (trim($apacheStatus) !== 'active') {
        return ['status' => 3, 'type' => 'error', 'message' => 'Apache service is not active.'];
    }

    $init = new Init($domain);
    return $init->inActive();
}

function apache()
{
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





