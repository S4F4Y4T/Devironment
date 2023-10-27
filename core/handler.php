<?php

use apache\apache;
use vhost\vhost;

class handler{
    public array $cmdList;
    public string $appName;
    public string $version;
    public string $author;
    public string $repository;
    public string $branch;
    public string $projectDir;
    
    public function __construct($appName, $version, $author, $repository, $branch, $projectDir){

        $this->appName = $appName;
        $this->version = $version;
        $this->author = $author;
        $this->repository = $repository;
        $this->branch = $branch;
        $this->projectDir = $projectDir;
    }

    //register available commands
    private function registerCmd(): array
    {
    	//all available commands and there actions
        return [
            'install' => [
                'description' => 'Install script globally',
                'action' => 'installer'
            ],
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
            'latest' => [
                'description' => 'Update to latest version',
                'action' => 'sync'
            ],
            'ping' => [
                'description' => 'Check Your Internet Connection',
                'action' => 'ping'
            ],
            'host' => [
                'description' => 'Manage Virtual Hosts. EX: vhost [action] [domain] [project name] [directory]',
                'action' => 'vhost'
            ],
            'apache' => [
                'description' => 'Manage Apache Server',
                'action' => 'apache'
            ],
            'kill' => [
                'description' => 'Exit the program',
                'action' => 'kill'
            ]
        ];
    }

    //get commands array
    public function getCmd() : array
    {
    	return $this->registerCmd();
    }

    //get user input match with available options
    private function getAction(string $action, array $options): string
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

    //validate if git is available in local
    private function isGit(): bool
    {
        $command = "git -C $this->projectDir rev-parse --is-inside-work-tree 2>&1";
        $output = shell_exec($command);

        // Check the output of the command
        return trim($output) === "true";
    }

    //check script function
    public function version(): array
    {
        return ['status' => 1, 'message' => $this->version ?? "0.0.0"];
    }

    //validate if new update available
    public function status(): array
    {
        if(!$this->isGit()){
            return ['status' => 0, 'message' => "Script repository not found."];
        }

        echo "Checking for new updates...". PHP_EOL;

        // Get the latest commit SHA from the remote repository
        $latestRemoteCommit = shell_exec("git ls-remote $this->repository $this->branch");
        $latestRemoteCommit = explode("	", $latestRemoteCommit);

        // Get the local commit SHA of the branch
        $localCommit = trim(shell_exec("git -C $this->projectDir rev-parse $this->branch"));
        
        if ($latestRemoteCommit[0] ?? "" === $localCommit) {
            return ['status' => 1, 'message' => "Up to date! Nothing to update."];
        }

        return ['status' => 0, 'message' => "There is a new version available."];
    }

    //install script globally
    public function installer(): array
    {
        exec('which devenv', $output, $exitCode);

        if($exitCode === 0){
            return ['status' => 0, 'message' => 'This script is already installed on your device.'];
        }

        if((int)($this->sudo()['status'] ?? 0) === 0){
            return ['status' => 0, 'message' => 'This operation require superuser (sudo) privileges.'];
        }

        echo "Installing...". PHP_EOL;
        ob_start();
        require_once $this->projectDir.'/installer.php'; //run installer
        $output = get_object_vars(json_decode(ob_get_clean())); //catch the response from installer

        return ['status' => $output['status'] ?? 0, 'message' => $output['message'] ?? "Something went wrong."];
    }

    //update script to latest version
    public function sync(): array
    {
        if((int)($this->ping()['status'] ?? 0) === 0)
        {
            return ['status' => 0, 'message' => 'Your internet is not available.'];
        }
        //validate if git exist
        if(!$this->isGit())
        {
            return ['status' => 0, 'message' => "Script repository not found."];
        }
        //validate if new update is available
        if((int)($this->status()['status'] ?? 0) === 1)
        {
            return ['status' => 0, 'message' => "Up to date! Nothing to update."];
        }
        //validate if sudo privilege
        if((int)($this->sudo()['status'] ?? 0) === 0)
        {
            return ['status' => 0, 'message' => 'This operation require superuser (sudo) privileges.'];
        }

        if(!file_exists($this->projectDir.'/installer.php'))
        {
            return ['status' => 0, 'message' => 'Installer not found.'];
        }

        echo "Initializing...". PHP_EOL;

        // Execute a `git pull` command to fetch and apply the latest changes from the remote repository
        $gitPullOutput = shell_exec("git -C $this->projectDir pull $this->repository $this->branch");

        if (strpos($gitPullOutput, 'Already up to date') !== false) {

            $response = ['message' => "Already up to date."];

        } elseif (strpos($gitPullOutput, 'Updating') !== false) {

            $installer = $this->installer(); //run installer

            if((int)($installer['status'] ?? 0) === 1){

                $response = ['status' => 1, 'message' => "Script updated to latest version successfully! Restart to make the changes."];

            }else{

                $response = ['status' => 0, 'message' => $installer['message']];

            }

        }else{

            $response = ['status' => 0, 'message' => "Something went wrong."];

        }

        return $response;
    }

    //validate if user in sudo mode
    private function sudo(): array
    {
        if (posix_geteuid() !== 0)
        {
            return ['status' => 0, 'message' => 'This user do not has superuser (sudo) privileges.'];
        }

        return ['status' => 1, 'message' => 'This user has superuser (sudo) privileges.'];
    }

    //validate if internet available
    public function ping(): array
    {
        $ipAddress = '8.8.8.8'; // Google Public DNS

        exec("ping -c 1 -W 2 $ipAddress", $output, $exitCode);

        if($exitCode === 0){
            return ['status' => 1, 'message' => 'Internet is available'];
        }

        return ['status' => 0, 'message' => 'Internet is not available'];
    }

    //close the application
    public function kill(): array
    {
        return ['processing' => 0, 'message' => 'Exiting Program...'];
    }

    //check all available commands
    public function help(): void
    {
        foreach ($this->getCmd() as $key => $val) {
            echo '  [] ' . $key . ' - ' . $val['description'] . PHP_EOL;
        }

        return;
    }

    //mange virtual hosts
    public function vhost(string $action = "", string $domain = "", string $project_name = "" , string $dir = ""): array | null
    {
      	if((int)($this->sudo()['status'] ?? 0) === 0){
            return ['status' => 0, 'message' => 'This operation require superuser (sudo) privileges.'];
        }

        // validate if apache is active
        $apacheStatus = shell_exec('systemctl is-active apache2');
        if (trim($apacheStatus) !== 'active') {
            return ['status' => 0, 'message' => 'Apache service is not active.'];
        }
        
        //load require files
        require_once $this->projectDir . '/modules/vhost/init.php';
        $init = new vhost($domain, $project_name , $dir);

        $action = $this->getAction($action, $init->getOptions()); // take user action and validate

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
                $response = ['message' => 'Invalid action'];
                break;
        }

        return $response;
    }

    //mange web server
    public function apache(string $action = ""): array | null
    {
        if((int)($this->sudo()['status'] ?? 0) === 0){
            return ['status' => 0, 'message' => 'This operation require superuser (sudo) privileges.'];
        }

        //load require files
        require_once $this->projectDir . '/modules/apache/init.php';
        $init = new apache();

        $action = $this->getAction($action, $init->getOptions()); // take user action and validate

        //call the appropriate method according to user action
        switch ($action) {
            case 'status':
            case 1:
                $response = $init->status();
                break;
            default:
                $response = ['message' => 'Invalid action'];
                break;
        }

        return $response;
    }
}
