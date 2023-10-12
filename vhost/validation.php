<?php
class validation{

    function duplicate_domain($conf = ""): bool
    {
        if(file_exists('/etc/apache2/sites-available/'.$conf.'.conf')){
            return true;
        }

        return false;
    }

    function is_valid($conf = ""): bool
    {
        if(file_exists('/etc/apache2/sites-available/'.$conf.'.conf')){
            return true;
        }

        return false;
    }

    public function validate_option(string $action, array $options) : bool
    {
        return in_array($action, array_column($options, 'action'));
    }
}