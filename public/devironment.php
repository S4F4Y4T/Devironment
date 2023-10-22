#!/usr/bin/env php

<?php

//require_once '../vhost/init.php';

$appName = "Devironment";
$version = "3.0.0";
$author = "S4F4Y4T";
$repository = 'https://github.com/S4F4Y4T/Devironment'; // url of original git repo
$branch = 'main'; // name of the master branch

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
echo "# Repository: " . $repository . PHP_EOL;

$sudo = sudo(); //validate if user has sudo privileges

//Handle script update validation
$status = status();
$logic = ['status' => 3, 'message' => ''];
if(isGit()) {
    if ($status['type'] === 'error') {
        $logic['type'] = 'error';
        $logic['message'] = 'There is a new update available';
    }
}

//instruction to see the available commands of program
echo PHP_EOL;
echo "Enter command 'help' to see all the available commands" . PHP_EOL;

//all available commands and there actions
$cmd_lists = [
    'help' => [
        'description' => 'List all available commands',
        'action' => 'help'
    ],
    '--v' => [
        'description' => 'View current version of the script',
        'action' => 'version'
    ],
    'status' => [
        'description' => 'Check for new update',
        'action' => 'status'
    ],
    'sync' => [
        'description' => 'Update to latest version',
        'action' => 'sync'
    ],
    'ping' => [
        'description' => 'Check Your Internet Connection',
        'action' => 'ping'
    ],
    'vhost' => [
        'description' => 'Manage Virtual Hosts. EX: vhost [action] [domain] [project name] [directory]',
        'action' => 'vhost'
    ],
    'kill' => [
        'description' => 'Exit the program',
        'action' => 'kill'
    ]
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
        switch ($logic['type'] ?? "") {
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

    echo PHP_EOL;
    echo "Enter Command: ";
    $command_input = trim(fgets(STDIN)); // Read the command input from user

    $commands = explode(" ", $command_input); // Explode the command by spaces

    $action = $commands[0]; // Get the first index as the command

    $cmd_opt = array_slice($commands, 1); // Get the remaining parts as arguments in an array

    if (array_key_exists($action, $cmd_lists)) {

        $logic = $cmd_lists[$action]['action'](...$cmd_opt);

    } else {
        echo "Command '$action' not found! Use 'help' command to see all available commands." . PHP_EOL;
        $logic = ['status' => 3];
    }

}

function getAction(string $action, array $options)
{
    if(empty($action) && !empty($options)){
        echo PHP_EOL;
        echo "Which action you want to perform?" . PHP_EOL;
        foreach ($options as $option){
            echo "      [".$option['action-id']."]".$option['label']."- ".$option['description']. PHP_EOL;
        }
        echo PHP_EOL;
    }

    //take action user wants to perform
    while (!(in_array($action, array_column($options, 'action')) || in_array($action, array_column($options, 'action-id')))) {

        if(!empty($action)){
            echo "Error: Invalid action" . PHP_EOL;
        }

        echo "Choose your action: ";
        $action = strtolower(trim(fgets(STDIN)));
    }

    return $action;
}

function isGit() {
    $parentDirectory = dirname(__DIR__); // Get the parent directory of the current working directory
    $command = "git -C $parentDirectory rev-parse --is-inside-work-tree 2>&1";
    $output = shell_exec($command);

    // Check the output of the command
    return trim($output) === "true";
}

//all actions function
function version(): array
{
    global $version;

    return ['status' => 3, 'type' => 'success', 'message' => $version];
}

function status(): array
{
    global $repository;
    global $branch;

    if(!isGit()){
        return ['status' => 3, 'type' => 'error', 'message' => "Script repository not found."];
    }

    echo "Checking for new updates...". PHP_EOL;

    // Get the latest commit SHA from the remote repository
    $latestRemoteCommit = trim(shell_exec("git ls-remote $repository $branch"));

    // Get the local commit SHA of the branch
    $localCommit = trim(shell_exec("git rev-parse $branch"));

    if ($latestRemoteCommit[0] ?? "" === $localCommit) {
        return ['status' => 3, 'type' => 'success', 'message' => "Up to date! Nothing to update."];
    }

    return ['status' => 3, 'type' => 'error', 'message' => "There is a new version available."];
}

function sync(): array
{
    global $repository;
    global $branch;
    global $status;

    if(!isGit()){
        return ['status' => 3, 'type' => 'error', 'message' => "Script repository not found."];
    }

    if($status['type'] === 'success'){
        return ['status' => 3, 'type' => 'error', 'message' => "Up to date! Nothing to update."];
    }

    echo "Processing...". PHP_EOL;

    // Execute a `git pull` command to fetch and apply the latest changes from the remote repository
    $gitPullOutput = shell_exec("git pull $repository $branch");

    if (strpos($gitPullOutput, 'Already up to date') !== false) {
        $response = ['status' => 3, 'message' => "Already up to date."];
    } elseif (strpos($gitPullOutput, 'Updating') !== false) {
        $response = ['status' => 3, 'type' => 'success', 'message' => "Updated successfully! Restart to make the changes."];
    }else{
        $response = ['status' => 3, 'type' => 'error', 'message' => "Something went wrong."];
    }

    return $response;
}

function sudo()
{
    if (posix_geteuid() !== 0)
    {
        return ['status' => 0, 'type' => 'error', 'message' => 'This user do not has superuser (sudo) privileges.'];
    }

    return ['status' => 3, 'type' => 'success', 'message' => 'This user has superuser (sudo) privileges.'];
}

function ping(): array
{
    $ipAddress = '8.8.8.8'; // Google Public DNS

    exec("ping -c 1 -W 2 $ipAddress", $output, $exitCode);

    if($exitCode === 0){
        return ['status' => 3, 'type' => 'success', 'message' => 'Internet is available'];
    }

    return ['status' => 3, 'type' => 'error', 'message' => 'Internet is not available'];
}

function kill(): array
{
    echo "Exiting Program...";
    return ['status' => 0, 'message' => ''];
}

function help(): array
{
    global $cmd_lists;
    foreach ($cmd_lists as $key => $val) {
        echo '  [] ' . $key . ' - ' . $val['description'] . PHP_EOL;
    }

    return ['status' => 3, 'message' => ''];
}

function vhost(string $action = "", string $domain = "", string $project_name = "" , string $dir = "")
{
    global $sudo;

    if($sudo['status'] === 0){
        return ['status' => 3, 'type' => 'error', 'message' => 'This operation require superuser (sudo) privileges.'];
    }

    // validate if apache is active
    $apacheStatus = shell_exec('systemctl is-active apache2');
    if (trim($apacheStatus) !== 'active') {
        return ['status' => 3, 'type' => 'error', 'message' => 'Apache service is not active.'];
    }

    $init = new Init($domain, $project_name , $dir);
    $options = $init->getOptions();

    $action = getAction($action, $options); // take user action and validate

    //call the appropriate method according to user action
    switch ($action) {
        case 'create':
        case 1:
            $response = $init->create();
            break;
        case 'list':
        case 2:
            $response = $init->list();
            break;
        case 3:
        case 'enable':
            $response = $init->enable();
            break;
        case 4:
        case 'disable':
            $response = $init->disable();
            break;
        default:
            $response = ['status' => 3, 'type' => '', 'message' => 'No action found'];
            break;
    }

    return $response;
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
        echo "\033[32m" . $logic['message'] . "\033[0m" . PHP_EOL. PHP_EOL;
        break;
    case 0:
        echo "\033[31m" . $logic['message'] . "\033[0m" . PHP_EOL. PHP_EOL;
        break;
    default:
        echo "Exiting program..." . PHP_EOL;
}
