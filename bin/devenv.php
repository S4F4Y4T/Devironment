#!/usr/bin/env php
<?php
    // Set the error reporting level to hide notices and warnings
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

    $appName = "Devironment";
    $version = "5.0.0";
    $author = "S4F4Y4T";
    $repository = 'https://github.com/S4F4Y4T/Devironment'; // url of original git repo
    $branch = 'main'; // name of the master branch
    $projectDir = dirname(__DIR__);

    if($projectDir.'/bin' === '/usr/local/bin')
    {
        $projectDir = '/usr/local/lib/devironment';
    }

    require_once $projectDir . '/core/handler.php';
    $handler = new handler($appName, $version, $author, $repository, $branch, $projectDir);
    $response = ['status' => 3, 'message' => ''];

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

    //Handle script update validation if git and internet is available
    $ping = $handler->ping();
    if($ping['type'] === 'success' && $handler->isGit()) {

        $status = $handler->status();
        if ($status['type'] === 'error') { //new version available
            $response['type'] = 'error';
            $response['message'] = $status['message'];
        }
    }

    //instruction to see the available commands of program
    echo PHP_EOL;
    echo "Enter command 'help' to see all the available commands" . PHP_EOL;

    while (($response['status'] ?? 0) === 3) {

        if (!empty($response['message'])) {
            switch ($response['type'] ?? "") {
                case 'success':
                    echo "\033[32m" . $response['message'] . "\033[0m" . PHP_EOL;
                    break;
                case 'error':
                    echo "\033[31m" . $response['message'] . "\033[0m" . PHP_EOL;
                    break;
                default:
                    echo $response['message'] . PHP_EOL;
                    break;
            }
        }

        echo PHP_EOL;

        //check if command passed through terminal and handle accordingly
        if(isset($argv) && count($argv) >= 2)
        {
            $action = $argv[1];
            $cmd_opt = array_slice($argv, 2);
            unset($argv);

        } else {

            echo "Enter Command: ";
            $command_input = trim(fgets(STDIN)); // Read the command input from user

            $commands = explode(" ", $command_input); // Explode the command by spaces
            $action = $commands[0]; // Get the first index as the command
            $cmd_opt = array_slice($commands, 1); // Get the remaining parts as arguments in an array
        }

        if (array_key_exists($action, $handler->cmdList)) {

            $cmd = $handler->cmdList[$action]['action']; //get the function name from the command list
            $response = $handler->$cmd(...$cmd_opt);

        } else {
            $response = ['status' => 3, 'type' => 'error', 'message' => "Command '$action' not found! Use 'help' command to see all available commands."];
        }

    }

    switch ($response['type'] ?? "") {
        case 'success':
            echo "\033[32m" . $response['message'] . "\033[0m" . PHP_EOL . PHP_EOL;
            break;
        case 'error':
            echo "\033[31m" . $response['message'] . "\033[0m" . PHP_EOL . PHP_EOL;
            break;
        default:
            echo "Exiting program..." . PHP_EOL . PHP_EOL;
    }
