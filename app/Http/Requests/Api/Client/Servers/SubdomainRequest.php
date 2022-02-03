<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class SubdomainRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return 'subdomain.manage';
    }
}
