<?php

if (posix_geteuid() !== 0)
{
    echo "\033[31mThis script require sudo(super user) privilege\033[0m" . PHP_EOL. PHP_EOL;
    exit();
}

echo "Processing...". PHP_EOL;

// Define the current directory
$currentDirectory = __DIR__; // Current directory where your script is located
// Define the target directory
$targetDirectory = '/usr/local/bin/devironment';
// Define the script name
$scriptName = 'devironment.php';
// Running with sudo, get the home directory of the user who invoked sudo
$loggedInUser = $_SERVER['SUDO_USER'];

echo "Copying directory...". PHP_EOL;
//copy folder to target folder
function copyDirectory($source, $destination) {
    global $loggedInUser;
    // Create the destination directory if it doesn't exist
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    // Get a list of all files and directories in the source directory
    $items = glob("$source/*");

    foreach ($items as $item) {
        $destItem = $destination . '/' . basename($item);

        if (is_dir($item)) {
            // If it's a directory, recursively copy its contents
            copyDirectory($item, $destItem);
        } else {
            // If it's a file, copy it to the destination
            if (!copy($item, $destItem)) {
                echo "Error: Copying file $item" . PHP_EOL;
            }
        }

        chown($destItem, $loggedInUser);
        chgrp($destItem, $loggedInUser);
        chgrp($destItem, 0755);
    }
}

copyDirectory($currentDirectory, $targetDirectory);
//chmod($targetDirectory.'/public/'.$scriptName, 0755);

// Determine the user's default shell
$userShell = shell_exec('basename $SHELL');

// Check the default shell and update the corresponding configuration file
if (trim($userShell) === 'bash') {
    // Bash shell
    $configFile = $_SERVER['HOME'] . '/.bashrc';
} elseif (trim($userShell) === 'zsh') {
    // Zsh shell
    $configFile = $_SERVER['HOME'] . '/.zshrc';
} else {
    // Default to Bash if the shell type is not recognized
    $configFile = $_SERVER['HOME'] . '/.bashrc';
}
echo "Adding to executable path variable...". PHP_EOL;
// Prepare the export command with the new directory
$exportCommand = 'export PATH="$PATH:' . $targetDirectory . '/public"';

$homeDirectory = posix_getpwnam($loggedInUser)['dir'];
// Get the content of the user's .bashrc file
$bashrcFile = $homeDirectory . '/.bashrc';

// Check if the export command already exists in .bashrc
$existingContent = file_get_contents($bashrcFile);
if (strpos($existingContent, $exportCommand) === false) {
    // Append the export command to the .bashrc file
    if (file_put_contents($bashrcFile, $exportCommand . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
        echo "Error modifying shell config" . PHP_EOL;
    }
}

// add the executable dir for sudo as well
$sudoers = '/etc/sudoers.d/devironment';  // Specify the file you want to modify.
$newSecurePath = 'Defaults secure_path="<default value>:/usr/local/bin/devironment/public"';  // Define the new secure path.

if (!file_exists($sudoers)) {
    // If the file doesn't exist, create it and write the configuration.
    $file = fopen($sudoers, 'w');
    if ($file) {
        fwrite($file, $newSecurePath);
        fclose($file);
        echo "Adding the path to sudo path variable...". PHP_EOL;
    }
}

echo "Installation Completed.". PHP_EOL;

