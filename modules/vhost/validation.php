<?php

namespace vhost;
class validation
{
    function is_exist($conf = ""): bool
    {
        if (file_exists('/etc/apache2/sites-available/' . $conf . '.conf')) {
            return true;
        }

        return false;
    }
}