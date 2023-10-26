<?php

    if (posix_geteuid() !== 0)
    {
        echo "\033[31mThis script require sudo(super user) privilege\033[0m" . PHP_EOL. PHP_EOL;
        exit();
    }

    echo "Initializing...". PHP_EOL;
    echo "Setting up directory...". PHP_EOL;

    try{
        // Define the current directory
        $currentDirectory = __DIR__; // Current directory where your script is located
        // Define the target directory
        $dependencyDirectory = '/usr/local/lib/devironment';
        processFileSystem($currentDirectory, $dependencyDirectory); //process files to their appropriate destination
        echo "Installation Completed.". PHP_EOL;

    } catch (Exception $e) {
        echo "An Error Occurred: " .$e->getMessage(). PHP_EOL;
    }

    //copy folder to target folder
    /**
     * @throws Exception
     */
    function processFileSystem($source, $destDir)
    {
        // Define the script name
        global $currentDirectory;
        $scriptName = 'dev';
        // Running with sudo, get the home directory of the user who invoked sudo
        $loggedInUser = $_SERVER['SUDO_USER'];

        //if the source dir is bin then process the bin file instead of dependency dir
        if ($source === $currentDirectory. "/bin") {
            $binaries = scandir($currentDirectory. "/bin");
            if(!empty($binaries)){
                foreach ($binaries as $bin) {
                    if ($bin != '.' && $bin != '..') {
                        if (is_file($currentDirectory. "/bin/" . $bin)) {
                            $destItem = '/usr/local/bin/'. $scriptName;
                            if (!copy($currentDirectory. "/bin/" . $bin, $destItem)) {
                                throw new Exception('Executable File could not copy.' . PHP_EOL);
                            }
                        }
                    }
                }
            }
        }else{

            // Create the destination directory if it doesn't exist
            if (!is_dir($destDir)) {
                if(!mkdir($destDir, 0755, true)){
                    throw new Exception('An error occurred while making directory.' . PHP_EOL);
                }
            }

            // Get a list of all files and directories in the source directory
            $items = scandir($source);

            if(!empty($items)){
                foreach ($items as $item) {
                    if ($item[0] !== '.' && $item != '.git') { //filter everything starts with . except .git

                        $destItem = $destDir . '/' . basename($item);

                        if (is_dir($item)) {
                            // If it's a directory, recursively copy its contents
                            processFileSystem($source . DIRECTORY_SEPARATOR .$item, $destItem);
                        } else {
                            // If it's a file, copy it to the destination
                            if (!copy($source . DIRECTORY_SEPARATOR .$item, $destItem)) {
                                throw new Exception('File could not copy.' . PHP_EOL);
                            }
                        }

                    }
                }
            }

        }

        if(isset($destItem)){
            chown($destItem, $loggedInUser); //update ownership of file
            chgrp($destItem, $loggedInUser); //update group of file
            chmod($destItem, 0755); //update permission of file
        }
    }
