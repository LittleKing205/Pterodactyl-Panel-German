<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

//! Controller
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

//! Laravel
use Pterodactyl\Models\Server;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;

//! Vendors
use Scai\RustRcon;

//! Multiple types of games
use GameQ\GameQ;

//! Minecraft
use xPaw\MinecraftPing;
use xPaw\MinecraftQuery;
use xPaw\MinecraftPingException;
use xPaw\MinecraftQueryException;

//! Source
use xPaw\SourceQuery\SourceQuery;

class PlayersController extends ClientApiController
{
    protected const GAMES = [
        "aa3", "aapg", "arkse", "arma", "arma3", "armedassault2oa", "arma3", "ase", "atlas", "batt1944", "bf1942", "bf2", "bf3", "bf4", "bfbc2", "bfh", "brink", "cod", "cod2", "cod4", "codmw3", "coduo", "codwaw", "conanexiles", "contagion", "crysis", "crysis2", "crysiswars", "cs15", "cs16", "cs2d", "cscz", "csgo", "css", "dal", "dayz", "dayzmod", "dod", "dods", "dow", "eco", "egs", "et", "etqw", "ffe", "ffow", "gamespy", "gamespy2", "gamespy3", "gamespy3", "gmod", "grav", "gta5m", "gtan", "hl2dm", "hurtworld", "insurgency", "insurgencysand", "jediacademy", "jedioutcast", "justcause2", "justcause3", "killing floor", "killing floor 2", "l4d", "l4d2", "lhmp", "minecraft", "minecraftpe", "mohaa", "mordhau", "mta", "mumble", "ns2", "pixark", "projectrealitybf2", "quake2", "quake3", "quakelive", "redorchestra2", "redorchestraostfront", "rising storm 2", "rust", "samp", "serioussam", "sevendaystodie", "ship", "sof2", "soldat", "source", "spaceengineers", "squad", "starmade", "swat4", "teamspeak2", "teamspeak3", "teeworlds", "terraria", "tf2", "theforrest", "tibia", "tshock", "unreal2", "unturned", "urbanterror", "ut", "ut2004", "ut3", "ventrilo", "warsow", "won", "wurm"
    ];

    protected const MINECRAFT = 1;
    protected const SOURCE = 2;
    protected const VOICE = 3;
    protected const RUST = 4;
    protected const OTHERS = 7;

    private DaemonFileRepository $repository;

    public function __construct(
        DaemonFileRepository $repository
    ) {
        $this->repository = $repository;
    }

    public function index(GetServerRequest $request, Server $server) : array {
        
        $supportedGames = config('egg_features.query');
        
        $error = "";
        $server_ip = !filter_var($server->allocation->ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) ? $server->allocation->ip_alias : $server->allocation->ip;
        $server_port = $server->allocation->port;

        $game = $supportedGames[self::OTHERS][$server->egg_id] ?? null;
        $game_name = $game['name'] ?? "";
        $game_query = $game['queryPort'] ?? $server_port;

        $players = [];
        $info = (object)[
            'hostname' => "",
            'dedicated' => false,
            'map' => "",
            'players' => 0,
            'maxplayers' => 0,
            'password' => false,
            'version' => false,
        ];

        $online_players = 0;
        $max_players = 0;

        if(
            in_array($server->egg_id, $supportedGames[self::MINECRAFT])
        ) {
            $ping = null;
            try {                
                $ping = new MinecraftPing($server_ip, $server_port, 1);
                $result = $ping->Query();

                try {
                    $bans = json_decode($this->repository->setServer($server)->getContent('banned-players.json'), true);
                    $ops = json_decode($this->repository->setServer($server)->getContent('ops.json'), true);
                } catch (\Exception $err) {
                    $bans = [];
                    $ops = [];
                }

                if($ping && $result) {
                    $players = $result['players']['sample'] ?? [];
                    $online_players = $result['players']['online'] ?? 0;
                    $max_players = $result['players']['max'] ?? 0;

                    $info = (object)[
                        'hostname' => gettype($result['description']) == "array" ? $result['description']['text'] : $result['description'],
                        'players' => $online_players,
                        'maxplayers' => $max_players,
                        'bans' => $bans,
                        'ops' => $ops,
                        'version' => $result['version']['name'],
                    ];

                    foreach($players as $key => $player) {
                        $player = (object)$player;
                        $players[$key] = [
                            'id' => $player->id,
                            'name' => $player->name,
                        ];
                    }
                }

                if(!$result) {
                    $url = "https://api.mcsrvstat.us/2/{$server_ip}:{$server_port}";
                    $request = json_decode(file_get_contents($url), true);

                    if($request["debug"]["ping"] === true) {                    
                        $players = $request["players"]["list"];
                        $online_players = $request["players"]["online"];
                        $max_players = $request["players"]["max"];

                        $info = (object)[
                            'players' => $online_players,
                            'maxplayers' => $max_players,
                            'version' => "{$request['software']} {$request['version']}",
                        ];

                        foreach($players as $key => $player) {
                            $players[$key] = [
                                'id' => $request["players"]["uuid"][$request["players"]["list"][$key]],
                                'name' => $player,
                            ];
                        }
                    }
                }
            } catch (\Exception $err) {
                $error = $err->getMessage();
            } finally {
                if($ping) {
                    $ping->Close();
                }
            }
        } else if(
            in_array($server->egg_id, $supportedGames[self::SOURCE])
        ) {
            $query = new SourceQuery();

            try {
                $query->Connect($server_ip, $server_port, 2, SourceQuery::SOURCE);

                $info = (object)$query->GetInfo();
                $info->GameID = isset($info->GameID) ? $info->GameID : 90;

                $players = $query->GetPlayers();
                $online_players = $info->Players;
                $max_players = $info->MaxPlayers;

                $info = (object)[
                    'hostname' => $info->HostName ?? "",
                    'dedicated' => $info->Dedicated === "d" ? true : false,
                    'map' => $info->Map ?? "",
                    'players' => $online_players,
                    'maxplayers' => $max_players,
                    'password' => $info->Password ?? false ,
                ];

                foreach ($players as $key => $player) {
                    $player = (object)$player;
                    $players[$key] = [
                        "id" => $key,
                        "name" => $player->Name,
                        "score" => $player->Frags,
                        "time" => $player->Time,
                    ];
                }
            } catch (\Exception $err) {
                $error = $err->getMessage();
            } finally {
                $query->Disconnect();
            }
        } else if (
            in_array($server->egg_id, $supportedGames[self::RUST])
        ) {
            $rcon_port = 0; $rcon_pass = "";
            foreach($server->variables as $var) {
                if($var->env_variable === "RCON_PORT") {
                    $rcon_port = $var->server_value;
                } else if($var->env_variable === "RCON_PASS") {
                    $rcon_pass = $var->server_value;
                }
            }

            try {
                $rcon = new RustRcon($server_ip, $rcon_port, $rcon_pass);
                
                $rcon->sendPacket("serverinfo");
                $data = $rcon->getMessage();
                
                $rcon->sendPacket("playerlist");
                $players = $rcon->getMessage();

                $online_players = $data["Players"];
                $max_players = $data["MaxPlayers"];

                $info = (object)[
                    'hostname' => $data["Hostname"],
                    'dedicated' => false,
                    'map' => $data["Map"],
                    'queued' => $data["Queued"],
                    'joining' => $data["Joining"],
                    'entities' => $data["EntityCount"],
                    'framerate' => $data["Framerate"],
                    'uptime' => $data["Uptime"],
                    'players' => $online_players,
                    'maxplayers' => $max_players,
                    'password' => false,
                ];

                foreach ($players as $key => $player) {
                    $player = (object)$player;
                    $players[$key] = [
                        "id" => $key,
                        "name" => $player->DisplayName,
                        "steamid" => $player->SteamID,
                        "health" => $player->Health,
                        "time" => $player->ConnectedSeconds,
                        "ping" => $player->Ping
                    ];
                }
            } catch(\Exception $err) {
                $error = $err->getMessage();
            }
        } else if(
            in_array($game_name, self::GAMES)
        ) {
            $host = "{$server_ip}:{$server_port}";
            $query = new GameQ();

            $options = [
                'query_port' => $game_query,
            ];

            $query->addServer([
                'type' => $game_name,
                'host' => $host,
                'options' => $server_port !== $game_query ? $options : [],
            ]);

            try {
                $res = (object)$query->process()[$host];

                $info = (object)[
                    'hostname' => $res->gq_hostname ?? "",
                    'dedicated' => property_exists($res, "gq_dedicated") ? ($res->gq_dedicated === "d" || $res->gq_dedicated === 1 ? true : false) : false,
                    'map' => $res->gq_mapname ?? "",
                    'players' => $res->gq_numplayers != null ? (int)$res->gq_numplayers : 0,
                    'maxplayers' => $res->gq_maxplayers != null ? (int)$res->gq_maxplayers : 0,
                    'password' => (bool)$res->gq_password ?? false ,
                ];
                
                if($game_name !== "gta5m") {
                    foreach ($res->players as $key => $player) {
                        $player = (object)$player;
                        $players[$key] = [
                            'id' => $key,
                            'name' => $player->gq_name ?? "",
                            'score' => $player->gq_score ?? 0,
                            'ping' => $player->gq_ping ?? 0,
                            'time' => $player->gq_time ?? 0,
                        ];
                    } 
                } else {
                    $url = "http://{$server_ip}:{$server_port}/players.json";
                    $players = json_decode(file_get_contents($url));
                    
                    foreach($players as $key => $player) {
                        $player = (object)$player;
                        $players[$key] = [
                            'id' => $key,
                            'name' => $player->name ?? "",
                            'ping' => $player->ping ?? 0,
                        ];
                    }
                }

                $online_players = $info->players;
                $max_players = $info->maxplayers;
            } catch (\Exception $err) {
                $error = $err->getMessage();
            }
        } else {
            $error = "This game isn't supported yet.";
        }

        if($error != '') {
            return [
                'success' => true,
                'data' => [
                    'error' => $error,
                ]
            ];
        }

        return [
            'success' => true,
            'data' => [
                'info' => $info,
                'players' => $players,

                'online_players' => $online_players,
                'max_players' => $max_players,
            ],
        ];
    }

    public function stats(GetServerRequest $request, Server $server) : array {
        return [];
    }
}
