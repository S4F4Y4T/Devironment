<?php

require_once 'validation.php';

class Vhost{

    private $domain;
    private $project_name;
    private $dir;
    private $validation;

    public function __construct($domain = "", $project_name = "" , $dir = ""){
        $this->domain = trim($domain);
        $this->project_name = trim($project_name);
        $this->dir = trim($dir);
        $this->validation = new validation();
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

    public function validateOption(string $action) : bool
    {
        return $this->validation->validate_option($action, $this->getOptions());
    }

    private function captureDomain(){
        //validate empty domain name
        while (empty($this->domain)) {

            echo "Enter Domain Name: ";
            $this->domain = strtolower(trim(fgets(STDIN)));

            if (empty(trim($this->domain))) {
                echo "Error: Domain cannot be empty" . PHP_EOL;
            }
        }
    }

    private function validateDomain()
    {
        //validate domain
        while (!empty($this->domain) && !preg_match('/^[a-zA-Z0-9.]+$/', $this->domain)) {

            echo "Error: Can't contain spaces or any special characters except for a dot (.)" . PHP_EOL;

            echo "Enter Domain Name: ";
            $this->domain = trim(strtolower(fgets(STDIN)));
        }
    }

    private function validDomain()
    {
        //validate domain
        while (!$this->validation->duplicate_domain($this->domain)) {

            if (!$this->validation->duplicate_domain($this->domain)) {
                echo "Error: This virtual host does not exists in your system" . PHP_EOL;
            }

            echo "Enter Domain Name: ";
            $this->domain = strtolower(trim(fgets(STDIN)));
        }
    }

    private function duplicateDomain()
    {
        //validate domain
        while ($this->validation->duplicate_domain($this->domain)) {

            if ($this->validation->duplicate_domain($this->domain)) {
                echo "Error: This virtual host already exists in your system ". $this->domain . PHP_EOL;
            }

            echo "Enter Domain Name: ";
            $this->domain = strtolower(trim(fgets(STDIN)));
        }
    }

    public function create(): array
    {
        //validate empty domain name
        $this->captureDomain();
        //validate domain
        $this->validateDomain();
        //validate duplicate domain
        $this->duplicateDomain();

        //validate empty project name
        while (empty($this->project_name)) {

            echo "Enter your Project Name: ";
            $this->project_name = trim(fgets(STDIN));

            if (empty($this->project_name)) {
                echo "Error: Project name cannot be empty" . PHP_EOL;
            }
        }
        //validate empty directory
        while (empty($this->dir)) {

            echo "Enter Directory Path: ";
            $this->dir = trim(fgets(STDIN));

            if (empty($this->dir)) {

                echo "Error: Directory cannot be empty" . PHP_EOL;

            }

        }
        //append '/' to directory
        if (substr($this->dir, -1) != '/') {
            $this->dir .= '/';
        }
        //validate directory
        while (!is_dir($this->dir)) {

            if (!is_dir($this->dir)) {
                echo "Error: Invalid directory" . PHP_EOL;
            }

            echo "Enter Directory Path: ";
            $this->dir = trim(fgets(STDIN));

        }

        echo "Initializing..." . PHP_EOL;

        //process virtual host
        try {
            //make directory and set permission if doesn't exist
            if (!is_dir($this->dir . $this->project_name)) {

                if (!mkdir($this->dir . $this->project_name, 0775, true)) {
                    throw new Exception('An error occurred while making the project directory.' . PHP_EOL);
                }

                chown($this->dir . $this->project_name, get_current_user()); // update permission

                //handle index.html file for new directory
                $indexFilePath = $this->dir . $this->project_name . "/index.html";
                $index = fopen($indexFilePath, "w");

                if ($index) {
                    $html = "<!DOCTYPE html>
                        <html>
                            <head>
                              <meta charset='UTF-8'>
                              <title>Powered BY VHOST</title>
                            </head>
                            <body>
                              This site was generated by Devironment
                            </body>
                        </html>";

                    if (fwrite($index, $html) !== false) {
                        chown($indexFilePath, get_current_user());
                    }

                    fclose($index);
                }
            }

            echo "Project initiated" . PHP_EOL;

            $vhost = fopen('/etc/apache2/sites-available/' . $this->domain . '.conf', "w");
            $conf = '<VirtualHost ' . $this->domain . ':80>
                    <Directory ' . $this->dir . $this->project_name . '>
                        Options Indexes FollowSymLinks MultiViews
                        AllowOverride All
                        Require all granted
                    </Directory>
                    ServerAdmin admin@' . $this->domain . '
                    ServerName ' . $this->domain . '
                    ServerAlias www.' . $this->domain . '
                    DocumentRoot ' . $this->dir . $this->project_name . '
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

            $command = "a2ensite " . $this->domain . ".conf; systemctl reload apache2";
            $restart = shell_exec($command);// create virtual host configuration and enable apache2 site and restart apache2

            if ($restart === null) {
                throw new Exception("Apache failed to restart.");
            }

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
        $this->validateDomain();

        $directory = '/etc/apache2/';
        $available_configs = scandir($directory . 'sites-available');
        $enable_configs = scandir($directory . 'sites-enabled');

        //filter ubuntu default virtual hosts
        $available_configs = array_filter($available_configs, function ($item) {return $item !== "000-default.conf" && $item !== "default-ssl.conf"; });
        $enable_configs = array_filter($enable_configs, function ($item) {return $item !== "000-default.conf" && $item !== "default-ssl.conf"; });

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
        $this->captureDomain();
        $this->validateDomain();
        $this->validDomain();

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
        $this->captureDomain();
        $this->validateDomain();
        $this->validDomain();

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
