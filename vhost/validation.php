<?php
class validation{

    function is_duplicate($project): bool
    {
        if(empty($project)){
            return $project;
        }

        if(file_exists('/etc/apache2/sites-available/'.$project.'.conf')){
            return true;
        }

        return false;
    }
}