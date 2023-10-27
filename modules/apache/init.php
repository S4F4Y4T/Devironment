<?php

namespace apache;

use Exception;

class apache
{
    public function __construct()
    {}

    public function getOptions(): array
    {
        return [
            ['action-id' => 1, 'action' => 'status', 'label' => 'Status', 'description' => 'Apache Status']
        ];
    }
    public function status(): array
    {
        // validate if apache is active
        $apacheStatus = shell_exec('systemctl is-active apache2');
        if (trim($apacheStatus) !== 'active') {
            return ['status' => 0, 'message' => 'Apache service is not active.'];
        }

        return ['status' => 1, 'message' => 'Apache service is active'];
    }

}
