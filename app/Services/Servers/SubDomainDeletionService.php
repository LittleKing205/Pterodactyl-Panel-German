<?php

namespace Pterodactyl\Services\Servers;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class SubDomainDeletionService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * SubDomainDeletionService constructor.
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param $server_id
     * @param $egg_id
     */
    public function delete($server_id, $egg_id)
    {
        $subdomains = DB::table('subdomain_manager_subdomains')->where('server_id', '=', $server_id)->get();
        if (count($subdomains) > 0) {
            foreach ($subdomains as $subdomain) {
                $domain = DB::table('subdomain_manager_domains')->where('id', '=', $subdomain->domain_id)->get();
                if (count($domain) > 0) {
                    $protocol = unserialize($domain[0]->protocol);
                    $protocol = $protocol[$egg_id];

                    $type = unserialize($domain[0]->protocol_types);
                    $type = empty($type[$egg_id]) || !isset($type[$egg_id]) ? 'tcp' : $type[$egg_id];

                    try {
                        $key = new \Cloudflare\API\Auth\APIKey(
                            $this->settings->get('settings::subdomain::cf_email', ''),
                            $this->settings->get('settings::subdomain::cf_api_key', '')
                        );
                        $adapter = new \Cloudflare\API\Adapter\Guzzle($key);
                        $zones = new \Cloudflare\API\Endpoints\Zones($adapter);
                        $dns = new \Cloudflare\API\Endpoints\DNS($adapter);

                        $zoneID = $zones->getZoneID($domain[0]->domain);
                    } catch (\Exception $e) {
                        $error = true;
                    }

                    if (empty($protocol)) {
                        $subdomain_all = $subdomain->subdomain . '.' . $domain[0]->domain;

                        $result = $dns->listRecords($zoneID, 'CNAME', $subdomain_all)->result;

                        if (count($result) > 0) {
                            $recordId = $result[0]->id;
                        }
                    } else {
                        $subdomain_all = $protocol . '._' . $type . '.' . $subdomain->subdomain . '.' . $domain[0]->domain;

                        $result = $dns->listRecords($zoneID, 'SRV', $subdomain_all)->result;

                        if (count($result) > 0) {
                            $recordId = $result[0]->id;
                        }
                    }

                    if (isset($recordId)) {
                        try {
                            $dns->deleteRecord($zoneID, $recordId);
                        } catch (\Exception $e) {
                            $error = true;
                        }
                    }
                }
            }

            DB::table('subdomain_manager_subdomains')->where('server_id', '=', $server_id)->delete();
        }
    }
}

?>
