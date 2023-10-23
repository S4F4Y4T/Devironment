#!/usr/bin/env php
<?php
    $appName = "Devironment";
    $version = "3.0.0";
    $author = "S4F4Y4T";
    $repository = 'https://github.com/S4F4Y4T/Devironment'; // url of original git repo
    $branch = 'main'; // name of the master branch

    require_once '../handler.php';
    $handler = new handler($appName, $version, $author, $repository, $branch);

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

    //Handle script update validation
    $status = $handler->status();
    $response = ['status' => 3, 'message' => ''];
    if($handler->isGit()) {
        if ($status['type'] === 'error') {
            $response['type'] = 'error';
            $response['message'] = $status['message'];
        }
    }

    //instruction to see the available commands of program
    echo PHP_EOL;
    echo "Enter command 'help' to see all the available commands" . PHP_EOL;

    while (($response['status'] ?? 0) === 3) {

        if (!empty($response['message'])) {
            switch ($response['type']) {
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
        echo "Enter Command: ";
        $command_input = trim(fgets(STDIN)); // Read the command input from user

        $commands = explode(" ", $command_input); // Explode the command by spaces
        $action = $commands[0]; // Get the first index as the command
        $cmd_opt = array_slice($commands, 1); // Get the remaining parts as arguments in an array

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
