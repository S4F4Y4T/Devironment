#!/usr/bin/php
<?php

$appName = "Ubuntu Vhost";
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
        'description' => 'Proceed to make virtual host',
        'action' => 'vhost'
    ]
];

//instruction to see the available commands of program
echo PHP_EOL;
echo "Enter command 'list' to see all the available commands". PHP_EOL;
echo PHP_EOL;

$logic = ['status' => 3, 'message' => 'processing...'];
while ($logic['status'] === 3)
{
    echo "Enter Command: ";
    $command = trim(fgets(STDIN));

    if(array_key_exists($command, $cmd_lists)) {

        $logic = $cmd_lists[$command]['action']();

    } else {
        echo "Command not found! Use 'list' command to see all available command" . PHP_EOL;
    }
}

//all actions function
function version()
{
    global $version;
    echo $version. PHP_EOL;

    return ['status' => 3, 'message' => 'processing...'];
}

function kill()
{
    return ['status' => 0, 'message' => 'Exiting Program...'];
}

function lists()
{
    global $cmd_lists;
    foreach ($cmd_lists as $key => $val){
        echo '  []'.$key .' - '.$val['description'] . PHP_EOL;
    }

    return ['status' => 3, 'message' => 'processing...'];
}

function vhost()
{
    function is_duplicate($name){
        if(file_exists('/etc/apache2/sites-available/'.$name.'.vh.conf')){
            return true;
        }

        return false;
    }

    //take input for the project name of the vhost and validate space and special characters
    $project_name = "";
    while (!preg_match('/^[a-zA-Z0-9]+$/', $project_name) || is_duplicate($project_name))
    {
        echo "Enter your Project Name: ";
        $project_name = trim(fgets(STDIN));

        if(!preg_match('/^[a-zA-Z0-9]+$/', $project_name)){
            echo "Can't contain space or any special character". PHP_EOL;
        }

        if(is_duplicate($project_name)){
            echo "This vhost already exist in your system". PHP_EOL;
        }

    }

//take directory input and validate
    $dir = "";
    while (!is_dir($dir))
    {
        echo "Enter Directory Path: ";
        $dir = trim(fgets(STDIN));

        if(!is_dir($dir)){
            echo "Invalid directory". PHP_EOL;
        }
    }

    //validate if / include at the end of the directory location, if not then append
    if (substr($dir, -1) != '/') {
        $dir .= '/';
    }

    echo "Processing...". PHP_EOL;

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

    $vhost = fopen('/etc/apache2/sites-available/'.$project_name.'.vh.conf', "w");
    $conf = '
    <VirtualHost '.$project_name.'.vh:80>
        <Directory '.$dir.$project_name.'>
            Options Indexes FollowSymLinks MultiViews
            AllowOverride All
            Require all granted
        </Directory>
        ServerAdmin admin@'.$project_name.'.vh
        ServerName '.$project_name.'.vh
        ServerAlias www.'.$project_name.'.vh
        DocumentRoot '.$dir.$project_name.'
        ErrorLog ${APACHE_LOG_DIR}/error.log
    </VirtualHost>';
    fwrite($vhost, $conf);
    fclose($vhost);

    $hosts = "/etc/hosts";
    // Read the contents of the file into a string
    $file_contents = file_get_contents($hosts);
    // Prepend the new line to the existing contents
    $new_record = "127.0.0.1	".$project_name.".vh" . PHP_EOL . $file_contents;
    // Write the new contents back to the file
    file_put_contents($hosts, $new_record);

    $command = "a2ensite ".$project_name.".vh.conf; systemctl reload apache2";
    shell_exec($command);// create virtual host configuration and enable apache2 site and restart apache2

    return ['status' => 1, 'message' => 'Vhost generated successfully. Open http://'.$project_name.'.vh'];
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





