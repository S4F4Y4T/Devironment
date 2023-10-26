<?php

require_once 'validation.php';

class Vhost{
    private $domain;
    private $project_name;
    private $dir;
    private $validation;
    private $usrDir;

    public function __construct($domain = "", $project_name = "" , $dir = ""){
        $this->domain = trim($domain);
        $this->project_name = trim($project_name);
        $this->dir = trim($dir);
        $this->validation = new validation();
        $this->usrDir = '/home/'. get_current_user();
    }

    public function getOptions() : array
    {
        return [
            ['action-id' => 1, 'action' => 'create', 'label' => 'Create', 'description' => 'Create New Virtual Host'],
            ['action-id' => 2, 'action' => 'list', 'label' => 'List', 'description' => 'List virtual hosts on your system'],
            ['action-id' => 3, 'action' => 'enable', 'label' => 'Enable', 'description' => 'Enable a inactive host'],
            ['action-id' => 4, 'action' => 'disable', 'label' => 'Disable', 'description' => 'Disable a active host']
        ];
    }

    //validate empty domain name
    private function inputDomain()
    {
        while (empty($this->domain)) {

            echo "Enter Domain Name: ";
            $this->domain = strtolower(trim(fgets(STDIN)));

            if (empty(trim($this->domain))) {
                echo "Error: Domain cannot be empty" . PHP_EOL;
            }
        }
    }

    //validate domain name
    private function validateDomainName()
    {
        while (!empty($this->domain) && !preg_match('/^[a-zA-Z0-9.]+$/', $this->domain)) {

            echo "Error: Can't contain spaces or any special characters except for a dot (.)" . PHP_EOL;

            echo "Enter Domain Name: ";
            $this->domain = trim(fgets(STDIN));
        }
    }

    private function validateDomain()
    {
        //validate domain
        while (!$this->validation->is_exist($this->domain)) {

            if (!$this->validation->is_exist($this->domain)) {
                echo "Error: This virtual host does not exists in your system" . PHP_EOL;
            }

            echo "Enter Domain Name: ";
            $this->domain = trim(fgets(STDIN));
        }
    }

    //validate duplicate domain
    private function duplicateDomain()
    {
        while ($this->validation->is_exist($this->domain)) {

            if ($this->validation->is_exist($this->domain)) {
                echo "Error: This virtual host already exists in your system ". $this->domain . PHP_EOL;
            }

            echo "Enter Domain Name: ";
            $this->domain = trim(fgets(STDIN));
        }
    }

    //validate empty project name
    private function inputProjectName()
    {
        while (empty($this->project_name)) {

            echo "Enter your Project Name: ";
            $this->project_name = trim(fgets(STDIN));

            if (empty($this->project_name)) {
                echo "Error: Project name cannot be empty" . PHP_EOL;
            }
        }
    }

    //validate empty directory
    private function inputDir()
    {
        while (empty($this->dir)) {

            echo "Enter Directory Path: \033[32m" . $this->usrDir . "/". "\033[0m";
            $this->dir = trim(fgets(STDIN));

            if (empty($this->dir)) {
                echo "Error: Directory cannot be empty" . PHP_EOL;
            }
        }
    }

    //validate invalid directory
    private function validateDir()
    {
        while (!is_dir($this->usrDir . $this->dir)) {
            if (!is_dir($this->usrDir . $this->dir)) {
                echo "Error: Invalid directory" . PHP_EOL;
            }

            echo "Enter Directory Path: \033[32m" . $this->usrDir . "/". "\033[0m";
            $this->dir = trim(fgets(STDIN));
        }
    }

    public function create(): array
    {
        $this->inputDomain(); //take domain input
        $this->validateDomainName(); //validate domain name
        $this->duplicateDomain(); //validate duplicate domain
        $this->inputProjectName(); //validate empty project name
        $this->inputDir(); //validate empty dir
//        $this->validateDir(); //validate invalid dir

        //prepend / to directory
        if (substr($this->dir, 0, 1) !== '/') {
            $this->dir = '/' . $this->dir;
        }

        //append '/' to directory
        if (substr($this->dir, -1) != '/') {
            $this->dir .= '/';
        }

        echo "Initializing..." . PHP_EOL;

        //process virtual host
        try {
            $projectDir = $this->usrDir . $this->dir . $this->project_name;
            //make directory and set permission if doesn't exist
            if (!is_dir($projectDir)) {

                if (!mkdir($projectDir, 0775, true)) {
                    throw new Exception('An error occurred while making the project directory.' . PHP_EOL);
                }

                //set ownership for all recursive new directory
                $directoryParts = explode('/', $this->dir . $this->project_name);
                $partialPath = '';

                if(!empty($directoryParts)){
                    foreach ($directoryParts as $part) {
                        if(!empty($part)){
                            $partialPath .= '/' . $part;

                            if (file_exists($partialPath)) {
                                if (!chown($this->usrDir . $partialPath, get_current_user())) {
                                    throw new Exception('Failed to change ownership.' . PHP_EOL);
                                }
                            }
                        }
                    }
                }

                //handle index.html file for new directory
                copy(__DIR__ . '/demo', $projectDir.'/index.html');
                chown($projectDir.'/index.html', get_current_user());
            }

            echo "Project initiated" . PHP_EOL;

            $vhost = fopen('/etc/apache2/sites-available/' . $this->domain . '.conf', "w");
            $conf = '<VirtualHost ' . $this->domain . ':80>
                    <Directory ' . $projectDir . '>
                        Options Indexes FollowSymLinks MultiViews
                        AllowOverride All
                        Require all granted
                    </Directory>
                    ServerAdmin admin@' . $this->domain . '
                    ServerName ' . $this->domain . '
                    ServerAlias www.' . $this->domain . '
                    DocumentRoot ' . $projectDir . '
                    ErrorLog ${APACHE_LOG_DIR}/error.log
                </VirtualHost>';
            fwrite($vhost, $conf);
            fclose($vhost);

            echo "Apache config initiated" . PHP_EOL;

            $hosts = "/etc/hosts";
            // Read the contents of the file into a string
            $file_contents = file_get_contents($hosts);
            // Prepend the new line to the existing contents
            $new_record = "127.0.0.1	" . $this->domain . PHP_EOL . $file_contents;
            // Write the new contents back to the file
            file_put_contents($hosts, $new_record);

            echo "Server host initiated" . PHP_EOL;
            echo "Enabling server..." . PHP_EOL;
            echo "Restarting server..." . PHP_EOL;

            $this->enable(); //enable and restart apache

            echo "Server Enabled" . PHP_EOL;
            echo "Server Restarted" . PHP_EOL;

            $message = 'Virtual Host generated successfully. Go to http://' . $this->domain;

        } catch (Exception $e) {
            // Handle the exception
            $message = "An error occurred: " . $e->getMessage();
            $status = 'error';
        }

        return ['status' => 3, 'type' => $status ?? 'success', 'message' => $message];
    }

    public function list(): array
    {
        //validate domain
        $this->validateDomainName();

        $directory = '/etc/apache2/';
        $available_configs = scandir($directory . 'sites-available');
        $enable_configs = scandir($directory . 'sites-enabled');

        //filter ubuntu default virtual hosts
        $available_configs = array_filter($available_configs, function ($item) {return $item !== "000-default.conf" && $item !== "default-ssl.conf"; });
        $enable_configs = array_filter($enable_configs, function ($item) {return $item !== "000-default.conf" && $item !== "default-ssl.conf"; });

        if(empty($available_configs) && empty($enable_configs))
        {
            return ['status' => 3, 'type' => '', 'message' => 'No virtual hosts available'];
        }

        //extract .conf from the end of the domain
        $available_configs = array_map(function($available_config) { return str_replace('.conf', '', $available_config); }, $available_configs);
        $enable_configs = array_map(function($enable_config) { return str_replace('.conf', '', $enable_config); }, $enable_configs);

        //if any domain passed then filter out all other domain
        if(!empty($this->domain))
        {
            $domain = $this->domain;
            $available_configs = array_filter($available_configs, function ($item) use ($domain) {
                return $item === $domain;
            });

            $enable_configs = array_filter($enable_configs, function ($item) use ($domain) {
                return $item === $domain;
            });
        }

        if(!empty($available_configs)){
            foreach ($available_configs as $config) {
                if ($config != '.' && $config != '..') {
                    $path = $directory . 'sites-available' . DIRECTORY_SEPARATOR . $config.'.conf';
                    if (is_file($path)) {
                        $status = in_array($config, $enable_configs) ? "(\033[32mActive\033[0m) [http://$config]" : "(Inactive)";
                        echo "      [] $config $status". PHP_EOL;
                    }
                }
            }
        }

        if(!empty($enable_configs)){

            $enable_configs = array_diff($enable_configs, $available_configs);

            foreach ($enable_configs as $config) {
                if ($config != '.' && $config != '..') {
                    $path = $directory . 'sites-enabled' . DIRECTORY_SEPARATOR . $config.'.conf';
                    echo "      [] $config (\033[31mBroken\033[0m)". PHP_EOL;
                }
            }
        }

        return ['status' => 3, 'type' => '', 'message' => ''];
    }

    public function enable(): array
    {
        $this->inputDomain();
        $this->validateDomainName();
        $this->validateDomain();

        // Command to enable the virtual host
        $command = "a2ensite ".$this->domain.".conf; systemctl reload apache2";

        // Execute the command and capture the output and exit code
        $output = shell_exec($command);

        if (strpos($output, "Site $this->domain enabled.") !== false) {

            $message = $this->domain . " has been enabled.";

        } elseif (strpos($output, "Site $this->domain already enabled") !== false){

            $message = $this->domain . " already enabled";

        } elseif (strpos($output, "Enabling site $this->domain.") !== false){

            $message = $this->domain . " has been enabled.";

        } else {

            $message = "Error enabling " . $this->domain . " Details: " . PHP_EOL . $output;
        }

        return ['status' => 3, 'type' => '', 'message' => $message];
    }

    public function disable(): array
    {
        $this->inputDomain();
        $this->validateDomainName();
        $this->validateDomain();

        // Command to enable the virtual host
        $command = "a2dissite ".$this->domain.".conf; systemctl reload apache2";

        // Execute the command and capture the output and exit code
        $output = shell_exec($command);

        if (strpos($output, "Site $this->domain disabled.") !== false) {

            $message = $this->domain . " has been disabled.";

        } elseif (strpos($output, "Site $this->domain already disabled") !== false){

            $message = $this->domain . " already disabled.";

        } else {
            $message = "Error disabling " . $this->domain . " Details: " . PHP_EOL . $output;
        }

        return ['status' => 3, 'type' => '', 'message' => $message ?? ""];
    }
}
