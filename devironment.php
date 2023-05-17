#!/usr/bin/php
<?php

$appName = "Devironment";
$version = "1.1.0";
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
        'description' => 'Proceed to make virtual host, e.g: vhost [project name] [directory]',
        'action' => 'vhost'
    ],
    'apache' => [
        'description' => 'Install Apache',
        'action' => 'apache'
    ],
    'php' => [
        'description' => 'Install PHP',
        'action' => 'php'
    ]
];

//instruction to see the available commands of program
echo PHP_EOL;
echo "Enter command 'list' to see all the available commands". PHP_EOL;
echo PHP_EOL;

$logic = ['status' => 3, 'message' => ''];
while ($logic['status'] === 3)
{
    if(!empty($logic['message'])){
        if($logic['type'] === 'success'){
            echo "\033[32m".$logic['message']."\033[0m" . PHP_EOL;
        }
    }
    echo "Enter Command: ";
    $command_input = trim(fgets(STDIN)); // Read the command input from user

    $commands = explode(" ", $command_input); // Explode the command by spaces

    $action = $commands[0]; // Get the first index as the command

    $cmd_opt = array_slice($commands, 1); // Get the remaining parts as arguments in an array

    if(array_key_exists($action, $cmd_lists)) {

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
    echo $version. PHP_EOL;

    return ['status' => 3, 'message' => ''];
}

function kill(): array
{
    return ['status' => 0, 'message' => 'Exiting Program...'];
}

function lists(): array
{
    global $cmd_lists;
    foreach ($cmd_lists as $key => $val){
        echo '  []'.$key .' - '.$val['description'] . PHP_EOL;
    }

    return ['status' => 3, 'message' => ''];
}

function vhost($project_name = "", $dir = "")
{
    $error = false;

    if (!function_exists('is_duplicate')) {
        function is_duplicate($name): bool
        {
            if(file_exists('/etc/apache2/sites-available/'.$name.'.conf')){
                return true;
            }

            return false;
        }
    }

    //take input for the project name of the vhost and validate space and special characters
    while (empty(trim($project_name)) || is_duplicate($project_name) || !preg_match('/^[a-zA-Z0-9.]+$/', $project_name)) {
        if(empty($project_name) || $error) {
            echo "Enter your Project Name: ";
            $project_name = trim(fgets(STDIN));
        }

        if (empty(trim($project_name))) {
            echo "Project name cannot be empty" . PHP_EOL;
            $error = true;
        } elseif (!preg_match('/^[a-zA-Z0-9.]+$/', $project_name)) {
            echo "Can't contain spaces or any special characters except for a period (.)" . PHP_EOL;
            $error = true;
        }elseif (is_duplicate($project_name)) {
            echo "This vhost already exists in your system" . PHP_EOL;
            $error = true;
        }
    }

    $error = false;

//take directory input and validate
    while (empty(trim($dir)) || !is_dir($dir) || is_dir($dir.$project_name))
    {
        if(empty(trim($dir)) || $error){
            echo "Enter Directory Path: ";
            $dir = trim(fgets(STDIN));
        }

        //validate if / include at the end of the directory location, if not then append
        if (substr($dir, -1) != '/') {
            $dir .= '/';
        }

        if(!is_dir($dir)){
            echo "Invalid directory". PHP_EOL;
            $error = true;
        }

        if(is_dir($dir.$project_name)){
            echo "Project folder already exist in this directory". PHP_EOL;
            $error = true;
        }

    }

    echo "Directory validated...". PHP_EOL;



    echo "Initializing...". PHP_EOL;

    //make the project directory
    if (!file_exists($dir.$project_name)) {
        mkdir($dir.$project_name, 0775, true);
        chown($dir.$project_name, get_current_user());

        $index = fopen($dir.$project_name."/index.html", "w");
        $html = "<!DOCTYPE html>
                <html>
                    <head>
                      <meta charset='UTF-8'>
                      <title>Powered BY VHOST</title>
                    </head>
                    <body>
                      This site was generated by VHOST
                    </body>
                </html>";
        fwrite($index, $html);
        fclose($index);
        chown($dir.$project_name."/index.html", get_current_user());
    }

    $vhost = fopen('/etc/apache2/sites-available/'.$project_name.'.conf', "w");
    $conf = '
    <VirtualHost '.$project_name.':80>
        <Directory '.$dir.$project_name.'>
            Options Indexes FollowSymLinks MultiViews
            AllowOverride All
            Require all granted
        </Directory>
        ServerAdmin admin@'.$project_name.'
        ServerName '.$project_name.'
        ServerAlias www.'.$project_name.'
        DocumentRoot '.$dir.$project_name.'
        ErrorLog ${APACHE_LOG_DIR}/error.log
    </VirtualHost>';
    fwrite($vhost, $conf);
    fclose($vhost);

    echo "Apache config created...". PHP_EOL;

    $hosts = "/etc/hosts";
    // Read the contents of the file into a string
    $file_contents = file_get_contents($hosts);
    // Prepend the new line to the existing contents
    $new_record = "127.0.0.1	".$project_name . PHP_EOL . $file_contents;
    // Write the new contents back to the file
    file_put_contents($hosts, $new_record);

    echo "Added to host...". PHP_EOL;
    echo "Restarting server...". PHP_EOL;

    $command = "a2ensite ".$project_name.".conf; systemctl reload apache2";
    shell_exec($command);// create virtual host configuration and enable apache2 site and restart apache2

    return ['status' => 3, 'type' => 'success', 'message' => 'Vhost generated successfully. Open http://'.$project_name];
}

function apache(){
    $command_list = [
                        'sudo apt-get update',
                        'sudo apt-get install apache2',
                        'ufw allow in "Apache"',
                        'sudo systemctl restart apache2',
                    ];

    $command = implode(';', $command_list);
    shell_exec($command);

    return ['status' => 1, 'message' => 'Apache installed successfully. Open http://localhost'];
}

function php(){
    $command_list = [
        'sudo apt-get update',
        'sudo apt-get install php libapache2-mod-php php-mysql',
        'sudo systemctl restart apache2',
    ];

    $command = implode(';', $command_list);
    shell_exec($command);

    return ['status' => 3, 'message' => 'PHP installed successfully.'];
}

switch ($logic['status']) {
    case 1:
        echo "\033[32m".$logic['message']."\033[0m" . PHP_EOL;
        break;
    case 0:
        echo $logic['message'] . PHP_EOL;
        break;
    default:
        echo "Exiting program..." . PHP_EOL;
}





