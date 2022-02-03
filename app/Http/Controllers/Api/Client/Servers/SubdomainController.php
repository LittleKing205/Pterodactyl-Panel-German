<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Client\Servers\SubdomainRequest;

class SubdomainController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    private $settingsRepository;

    /**
     * SubdomainController constructor.
     * @param SettingsRepositoryInterface $settingsRepository
     */
    public function __construct(SettingsRepositoryInterface $settingsRepository)
    {
        parent::__construct();

        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @param SubdomainRequest $request
     * @param Server $server
     * @return array
     */
    public function index(SubdomainRequest $request, Server $server): array
    {
        $domains = [];
        $allDomains = DB::table('subdomain_manager_domains')->get();
        $subdomains = DB::table('subdomain_manager_subdomains')->where('server_id', '=', $server->id)->get();
        $allocation = DB::table('allocations')->where('id', '=', $server->allocation_id)->get();

        foreach ($allDomains as $domain) {
            $egg_ids = explode(',', $domain->egg_ids);

            foreach ($egg_ids as $egg_id) {
                if ($server->egg_id == $egg_id) {
                    $domains[] = [
                        'id' => $domain->id,
                        'domain' => $domain->domain,
                    ];
                }
            }
        }

        foreach ($subdomains as $key => $subdomain) {
            foreach ($domains as $domain) {
                if ($subdomain->domain_id == $domain['id']) {
                    $subdomains[$key]->domain = $domain['domain'];
                }
            }
        }

        return [
            'success' => true,
            'data' => [
                'domains' => $domains,
                'subdomains' => $subdomains,
                'ipAlias' => $allocation[0]->ip_alias,
            ],
        ];
    }

    /**
     * @param SubdomainRequest $request
     * @param Server $server
     * @return array
     * @throws DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(SubdomainRequest $request, Server $server): array
    {
        $this->validate($request, [
            'subdomain' => 'required|min:2|max:32',
            'domainId' => 'required|integer'
        ]);

        $subdomain = strtolower(trim(strip_tags($request->input('subdomain'))));
        $domain_id = (int) $request->input('domainId');

        $domain = DB::table('subdomain_manager_domains')->where('id', '=', $domain_id)->get();
        if (count($domain) < 1) {
            throw new DisplayException('Domain not found.');
        }

        $protocol = unserialize($domain[0]->protocol);
        $protocol = $protocol[$server->egg_id];

        $type = unserialize($domain[0]->protocol_types);
        $type = empty($type[$server->egg_id]) || !isset($type[$server->egg_id]) ? 'tcp' : $type[$server->egg_id];

        $subdomainCount = DB::table('subdomain_manager_subdomains')->where('server_id', '=', $server->id)->get();
        if (count($subdomainCount) >= $this->settingsRepository->get('settings::subdomain::max_subdomain', 1)) {
            throw new DisplayException('You can create maximum ' . $this->settingsRepository->get('settings::subdomain::max_subdomain', 1) . ' Subdomain.');
        }

        if(preg_match("/^[a-zA-Z0-9]+$/", $subdomain) != 1) {
            throw new DisplayException('Invalid domain name format. The domain only contains: [a-z] [A-Z] [0-9]');
        }

        $subdomainIsset = DB::table('subdomain_manager_subdomains')->where('domain_id', '=', $domain_id)->where('subdomain', '=', $subdomain)->get();
        if (count($subdomainIsset) > 0) {
            throw new DisplayException('This subdomain is already taken: ' . $subdomain);
        }

        $allocation = DB::table('allocations')->where('id', '=', $server->allocation_id)->get();

        try {
            $key = new \Cloudflare\API\Auth\APIKey(
                $this->settingsRepository->get('settings::subdomain::cf_email', ''),
                $this->settingsRepository->get('settings::subdomain::cf_api_key', '')
            );
            $adapter = new \Cloudflare\API\Adapter\Guzzle($key);
            $zones = new \Cloudflare\API\Endpoints\Zones($adapter);
            $dns = new \Cloudflare\API\Endpoints\DNS($adapter);

            $zoneID = $zones->getZoneID($domain[0]->domain);
        } catch (\Exception $e) {
            throw new DisplayException('Failed to connect to cloudflare server.');
        }

        if (empty($protocol)) {
            $subdomain_all = $subdomain . '.' . $domain[0]->domain;

            $result = $dns->listRecords($zoneID, 'CNAME', $subdomain_all)->result;

            if (count($result) > 0) {
                throw new DisplayException('This subdomain is already taken: ' . $subdomain);
            }

            $data = array(
                'type' => 'CNAME',
                'name' => $subdomain,
                'content' => $allocation[0]->ip_alias,
                'proxiable' => false,
                'proxied' => false,
                'ttl' => 120
            );
        } else {
            $subdomain_all = $protocol . '._' . $type . '.' . $subdomain . '.' . $domain[0]->domain;

            $result = $dns->listRecords($zoneID, 'SRV', $subdomain_all)->result;

            if (count($result) > 0) {
                throw new DisplayException('This subdomain is already taken: ' . $subdomain);
            }

            $data = array(
                'type' => 'SRV',
                'data' => array(
                    "name" => $subdomain,
                    "ttl" => 120,
                    "service" => $protocol,
                    "proto" => "_" . $type,
                    "weight" => 1,
                    "port" => $allocation[0]->port,
                    "priority" => 1,
                    "target" => $allocation[0]->ip_alias,
                )
            );
        }

        try {
            if ($dns->addRecord($zoneID, $data) !== true) {
                throw new DisplayException('Failed to create subdomain.');
            }
        } catch (\Exception $e) {
            throw new DisplayException('Failed to create subdomain.');
        }

        DB::table('subdomain_manager_subdomains')->insert([
            'server_id' => $server->id,
            'domain_id' => $domain_id,
            'subdomain' => $subdomain,
            'port' => $allocation[0]->port,
            'record_type' => empty($protocol) ? 'CNAME' : 'SRV'
        ]);

        return ['success' => true];
    }

    /**
     * @param SubdomainRequest $request
     * @param Server $server
     * @param $id
     * @return array
     * @throws DisplayException
     */
    public function delete(SubdomainRequest $request, Server $server, $id): array
    {
        $id = (int) $id;

        $subdomain = DB::table('subdomain_manager_subdomains')->where('id', '=', $id)->where('server_id', '=', $server->id)->get();
        if (count($subdomain) < 1) {
            throw new DisplayException('Subdomain not found.');
        }

        $domain = DB::table('subdomain_manager_domains')->where('id', '=', $subdomain[0]->domain_id)->get();
        if (count($domain) < 1) {
            throw new DisplayException('Domain not found.');
        }

        $protocol = unserialize($domain[0]->protocol);
        $protocol = $protocol[$server->egg_id];

        $type = unserialize($domain[0]->protocol_types);
        $type = empty($type[$server->egg_id]) || !isset($type[$server->egg_id]) ? 'tcp' : $type[$server->egg_id];

        try {
            $key = new \Cloudflare\API\Auth\APIKey(
                $this->settingsRepository->get('settings::subdomain::cf_email', ''),
                $this->settingsRepository->get('settings::subdomain::cf_api_key', '')
            );
            $adapter = new \Cloudflare\API\Adapter\Guzzle($key);
            $zones = new \Cloudflare\API\Endpoints\Zones($adapter);
            $dns = new \Cloudflare\API\Endpoints\DNS($adapter);

            $zoneID = $zones->getZoneID($domain[0]->domain);
        } catch (\Exception $e) {
            throw new DisplayException('Failed to connect to cloudflare server.');
        }

        if (empty($protocol)) {
            $subdomain_all = $subdomain[0]->subdomain . '.' . $domain[0]->domain;

            $result = $dns->listRecords($zoneID, 'CNAME', $subdomain_all)->result;

            if (count($result) < 1) {
                throw new DisplayException('Failed to delete Subdomain.');
            }

            $recordId = $result[0]->id;
        } else {
            $subdomain_all = $protocol . '._' . $type . '.' . $subdomain[0]->subdomain . '.' . $domain[0]->domain;

            $result = $dns->listRecords($zoneID, 'SRV', $subdomain_all)->result;

            if (count($result) < 1) {
                throw new DisplayException('Failed to delete Subdomain.');
            }

            $recordId = $result[0]->id;
        }

        try {
            if ($dns->deleteRecord($zoneID, $recordId) !== true) {
                throw new DisplayException('Failed to delete Subdomain.');
            }
        } catch (\Exception $e) {
            throw new DisplayException('Failed to delete Subdomain.');
        }

        DB::table('subdomain_manager_subdomains')->where('id', '=', $id)->where('server_id', '=', $server->id)->delete();

        return ['success' => true];
    }
}
