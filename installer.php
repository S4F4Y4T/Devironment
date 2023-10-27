<?php
    // Set the error reporting level to hide notices and warnings
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

    if (posix_geteuid() !== 0)
    {
        echo "\033[31mThis script require sudo(super user) privilege\033[0m" . PHP_EOL. PHP_EOL;
        exit();
    }

    if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "Initializing..." . PHP_EOL;
        echo "Processing directory..." . PHP_EOL;
    }

//copy folder to target folder, must be on top before calling
/**
 * @throws Exception
 */
if(!function_exists('processFileSystem'))
{
    function processFileSystem($source, $destDir)
    {
        // Define the script name
        global $currentDirectory, $scriptName, $binDir;
        // Running with sudo, get the home directory of the user who invoked sudo
        $loggedInUser = $_SERVER['SUDO_USER'];

        // Create the destination directory if it doesn't exist
        if (!is_dir($destDir)) {
            if(!mkdir($destDir, 0755, true)){
                throw new Exception('An error occurred while making directory.' . PHP_EOL);
            }

            chown($destDir, $loggedInUser); //update ownership of file
        }

        // Get a list of all files and directories in the source directory
        $items = scandir($source);

        if(!empty($items)){
            foreach ($items as $item) {
                if ($item[0] !== '.' || $item === '.git') { //filter everything starts with . except .git

                    $destItem = $destDir . '/' . basename($item);

                    if (is_dir($source . DIRECTORY_SEPARATOR .$item)) {
                        // If it's a directory, recursively copy its contents
                        processFileSystem($source . DIRECTORY_SEPARATOR .$item, $destItem);
                    } else {
                        // If it's a file, copy it to the destination
                        if (!copy($source . DIRECTORY_SEPARATOR .$item, $destItem)) {
                            throw new Exception('File could not copy.' . PHP_EOL);
                        }

                        //move the destination to /bin if its from bin dir
                        if ($source === $currentDirectory. "/bin") {
                            $destItem = $binDir .'/'. $scriptName;

                            if (!copy($source . DIRECTORY_SEPARATOR .$item, $destItem)) {
                                throw new Exception('File could not copy.' . PHP_EOL);
                            }
                        }
                    }

                    chown($destItem, $loggedInUser); //update ownership of file
                    chmod($destItem, 0755); //update permission of file
                }
            }
        }
    }
}

    try{

        global $currentDirectory;
        global $dependencyDirectory;
        global $binDir;
        global $scriptName;

        // Define the current directory
        $currentDirectory = __DIR__; // Current directory where your script is located
        // Define the target directory
        $dependencyDirectory = '/usr/local/lib/devironment';
        $binDir = '/usr/local/bin';
        $scriptName = 'devenv';

        processFileSystem($currentDirectory, $dependencyDirectory); //process files to their appropriate destination

        if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])){
            echo "\033[32mInstallation is completed. run '$scriptName' to execute\033[0m". PHP_EOL. PHP_EOL;
        }else{
            echo json_encode(['status' => 1, 'message' => "Installation completed. run '$scriptName' to execute"]);
        }

    } catch (Exception $e) {

        if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
            echo "An Error Occurred: " .$e->getMessage(). PHP_EOL;
        }else{
            echo json_encode(['status' => 0, 'message' => "An Error Occurred: " .$e->getMessage()]);
        }
    }
