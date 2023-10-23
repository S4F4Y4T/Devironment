<?php
class handler{
    public $cmdList;
    public $appName;
    public $version;
    public $author;
    public $repository;
    public $branch;
    public function __construct($appName, $version, $author, $repository, $branch){

        $this->appName = $appName;
        $this->version = $version;
        $this->author = $author;
        $this->repository = $repository;
        $this->branch = $branch;

        //all available commands and there actions
        $this->cmdList = [
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
            'sync' => [
                'description' => 'Update to latest version',
                'action' => 'sync'
            ],
            'ping' => [
                'description' => 'Check Your Internet Connection',
                'action' => 'ping'
            ],
            'vhost' => [
                'description' => 'Manage Virtual Hosts. EX: vhost [action] [domain] [project name] [directory]',
                'action' => 'vhost'
            ],
            'kill' => [
                'description' => 'Exit the program',
                'action' => 'kill'
            ]
//    'apache' => [
//        'description' => 'Install Apache',
//        'action' => 'apache'
//    ],
//    'mysqldump' => [
//        'description' => 'Backup Mysql Dump Ex: mysqldump [username] [password] [dbname] [backupfile] --optional [host] --optional',
//        'action' => 'mysqldump'
//    ]
        ];
    }
    public function getAction(string $action, array $options)
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

    public function isGit()
    {
        $parentDirectory = __DIR__; // Get the parent directory of the current working directory
        $command = "git -C $parentDirectory rev-parse --is-inside-work-tree 2>&1";
        $output = shell_exec($command);

        // Check the output of the command
        return trim($output) === "true";
    }

    //all actions function
    public function version(): array
    {
        return ['status' => 3, 'type' => 'success', 'message' => $this->version ?? "0.0.0"];
    }

    public function status(): array
    {
        if(!$this->isGit()){
            return ['status' => 3, 'type' => 'error', 'message' => "Script repository not found."];
        }

        echo "Checking for new updates...". PHP_EOL;

        // Get the latest commit SHA from the remote repository
        $latestRemoteCommit = trim(shell_exec("git ls-remote $this->repository $this->branch"));

        // Get the local commit SHA of the branch
        $localCommit = trim(shell_exec("git rev-parse $this->branch"));

        if ($latestRemoteCommit[0] ?? "" === $localCommit) {
            return ['status' => 3, 'type' => 'success', 'message' => "Up to date! Nothing to update."];
        }

        return ['status' => 3, 'type' => 'error', 'message' => "There is a new version available."];
    }

    public function sync(): array
    {
        if(!$this->isGit()){
            return ['status' => 3, 'type' => 'error', 'message' => "Script repository not found."];
        }

        if($this->status()['type'] === 'success'){
            return ['status' => 3, 'type' => 'error', 'message' => "Up to date! Nothing to update."];
        }

        echo "Processing...". PHP_EOL;

        // Execute a `git pull` command to fetch and apply the latest changes from the remote repository
        $gitPullOutput = shell_exec("git pull $this->repository $this->branch");

        if (strpos($gitPullOutput, 'Already up to date') !== false) {
            $response = ['status' => 3, 'message' => "Already up to date."];
        } elseif (strpos($gitPullOutput, 'Updating') !== false) {
            $response = ['status' => 3, 'type' => 'success', 'message' => "Updated successfully! Restart to make the changes."];
        }else{
            $response = ['status' => 3, 'type' => 'error', 'message' => "Something went wrong."];
        }

        return $response;
    }

    public function sudo()
    {
        if (posix_geteuid() !== 0)
        {
            return ['status' => 3, 'type' => 'error', 'message' => 'This user do not has superuser (sudo) privileges.'];
        }

        return ['status' => 3, 'type' => 'success', 'message' => 'This user has superuser (sudo) privileges.'];
    }

    public function ping(): array
    {
        $ipAddress = '8.8.8.8'; // Google Public DNS

        exec("ping -c 1 -W 2 $ipAddress", $output, $exitCode);

        if($exitCode === 0){
            return ['status' => 3, 'type' => 'success', 'message' => 'Internet is available'];
        }

        return ['status' => 3, 'type' => 'error', 'message' => 'Internet is not available'];
    }

    public function kill(): array
    {
        return ['status' => 1, 'message' => 'Exiting Program...'];
    }

    public function help(): array
    {
        foreach ($this->cmdList as $key => $val) {
            echo '  [] ' . $key . ' - ' . $val['description'] . PHP_EOL;
        }

        return ['status' => 3, 'message' => ''];
    }

    public function vhost(string $action = "", string $domain = "", string $project_name = "" , string $dir = "")
    {
        require_once 'vhost/init.php';

        if($this->sudo()['type'] === 'error'){
            return ['status' => 3, 'type' => 'error', 'message' => 'This operation require superuser (sudo) privileges.'];
        }

        // validate if apache is active
        $apacheStatus = shell_exec('systemctl is-active apache2');
        if (trim($apacheStatus) !== 'active') {
            return ['status' => 3, 'type' => 'error', 'message' => 'Apache service is not active.'];
        }

        $init = new Vhost($domain, $project_name , $dir);
        $options = $init->getOptions();

        $action = $this->getAction($action, $options); // take user action and validate

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
                $response = ['status' => 3, 'type' => '', 'message' => 'No action found'];
                break;
        }

        return $response;
    }

    public function apache()
    {
        $command_list = [
            'sudo apt-get update',
            'sudo apt-get install apache2',
            'ufw allow in "Apache"',
            'sudo systemctl restart apache2',
        ];

        $command = implode(';', $command_list);
        shell_exec($command);

        return ['status' => 1, 'type' => 'success', 'message' => 'Apache installed successfully. Go to http://localhost'];
    }

    public function mysqldump($username = "", $password = "", $db = "")
    {
        $dbConfig = __DIR__ . '/.db.cnf';

        // Check if the option file exists
        if (file_exists($dbConfig)) {

            // Option file exists, update the content
            $content = "[client]\nuser=$username\npassword=$password\n";
            file_put_contents($dbConfig, $content);
            echo "DB Config updated.\n" . PHP_EOL;

        } else {

            // Option file doesn't exist, create a new one
            $content = "[client]\nuser=$username\npassword=$password\n";
            if (file_put_contents($dbConfig, $content) !== false) {

                chmod($dbConfig, 0600); // Set permissions to make it readable and writable only by the owner
                echo "DB Config created.\n" . PHP_EOL;

            } else {

                echo "Unable to write content to the file.";
                return ['status' => 3, 'type' => 'error', 'message' => 'Unable to write content to the file.'];
            }

        }

        exec('mysql --defaults-extra-file=' . $dbConfig . ' -e "SELECT 1"', $output, $return_var);

        // Check if the command was successful
        if ($return_var !== 0) {
            // Command failed, and $output may contain error messages
            return ['status' => 3, 'type' => 'error', 'message' => 'Invalid DB Config '];
        }


        $downloadDir = '/home/' . get_current_user() . '/Downloads/';
        if (!$downloadDir) {
            echo "Download directory not found.";
            return ['status' => 3, 'type' => 'error', 'message' => 'Download directory not found.'];
        }

        $command = 'mysqldump --defaults-extra-file=' . $dbConfig . ' ' . $db . ' > ' . $downloadDir . $db . '~' . date("Y-m-d") . '.sql';

        exec($command, $output, $return_var);

        // Check if the command was successful
        if ($return_var === 0) {

            return ['status' => 3, 'type' => 'success', 'message' => 'Backup Successful'];

        } else {
            // Command failed, and $output may contain error messages
            $messages = implode("\n", $output);
            return ['status' => 3, 'type' => 'error', 'message' => 'Backup Failed: ' . $messages];
        }
    }
}