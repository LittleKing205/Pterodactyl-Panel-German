import React, { useEffect } from "react";

//! Components
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import Spinner from "@/components/elements/Spinner";
import PlayersRow from "./PlayersRow";
import PlayersBanRow from "./PlayersBanRow";
import PlayersOperatorsRow from "./PlayersOperatorsRow";
import Fade from "@/components/elements/Fade";
import Button from "@/components/elements/Button";
import MessageBox from '@/components/MessageBox';

//! Plugins
import useFlash from '@/plugins/useFlash';

//! API
import getPlayers from "@/api/server/getPlayers";

//! State Management
import { ServerContext } from "@/state/server";

//! Vendors
import tw from "twin.macro";
import useSWR from "swr";
import isEqual from 'react-fast-compare';

export interface PlayersResponse {
    error?: string,
    info: ServerInformation,
    players: Player[],
    online_players: number,
    max_players: number,
}

export interface ServerInformation {
    dedicated: boolean,
    hostname: string,
    map: string,
    queued?: number,
    joining?: number,
    entities?: number,
    framerate?: number,
    uptime?: number,
    players: number,
    maxplayers: number,
    bans?: Bans[],
    ops?: Operators[],
    version?: string,
    password: boolean,
}

export interface Bans {
    uuid: string,
    name: string,
    created: Date,
    source: string,
    expires: string,
    reason: string,
}

export interface Operators {
    uuid: string,
    name: string,
    level: number,
    bypassesPlayerLimit: boolean,
}

export interface Player {
    id: number,
    name: string,
    steamid?: string,
    health?: number,
    score?: number,
    time?: Date,
    ping?: number,
}

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const eggFeatures = ServerContext.useStoreState(state => state.server.data!.eggFeatures, isEqual);

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data, error, mutate } = useSWR<PlayersResponse>([uuid, '/'], key => getPlayers(key), {
        revalidateOnFocus: true,
        errorRetryCount: 7,
        refreshInterval: 60000,
    });

    useEffect(() => {
        if (!error) {
            clearFlashes('players');
        } else {
            clearAndAddHttpError({ key: 'players', error });
        }
    }, [error]);

    return (
        <ServerContentBlock title={'Players'} showFlashKey={'players'}>
            {!data ?
                <Spinner size={'large'} centered />
                :
                <Fade timeout={150}>
                    {!data.error ? (
                        <>
                            <Button onClick={() => mutate()} size="small" css={tw`w-full mt-4 sm:w-auto sm:mt-0`}>
                                Refresh
                            </Button>
                            <p css={tw`text-sm text-neutral-400 mt-2 mb-4`}>
                                Zur zeit sind {data.online_players} von {data.max_players} Spielern Online.
                            </p>

                            <div css={tw`md:flex`}>

                                <div css={tw`flex-1`}>
                                    <p css={tw`text-sm text-neutral-400 mt-2 mb-4`}>Players List</p>
                                    {data.players.length > 0 ?
                                        data.players.map((player, index) => {
                                            const admin = data.info.ops?.find((client) => client.name === player.name) ? true : false;
                                            return (
                                                <PlayersRow
                                                    key={index}
                                                    uuid={uuid}
                                                    admin={admin}
                                                    player={player}
                                                    className={index > 0 ? 'mt-1' : undefined}
                                                    refresh={mutate}
                                                />
                                            )
                                        })
                                        :
                                        <p css={tw`text-center text-sm text-neutral-400`}>
                                            Es sieht so aus, als auf diesem Server gerade keine Spieler online sind.
                                        </p>
                                    }
                                </div>

                                {eggFeatures.includes('eula') && (
                                    <div>
                                        <div css={tw`flex-1 lg:flex-none lg:w-full mt-8 md:mt-0 md:ml-10`}>
                                            <p css={tw`text-sm text-neutral-400 mt-2 mb-4`}>Bans</p>
                                            {data.info.bans?.map((ban) => (
                                                <PlayersBanRow uuid={uuid} bans={ban} refresh={mutate} />
                                            ))}
                                        </div>

                                        <div css={tw`flex-1 lg:flex-none lg:w-full mt-8 md:mt-0 md:ml-10`}>
                                            <p css={tw`text-sm text-neutral-400 mt-4 mb-4`}>Operators</p>
                                            {data.info.ops?.map((admin) => (
                                                <PlayersOperatorsRow uuid={uuid} ops={admin} refresh={mutate} />
                                            ))}
                                        </div>
                                    </div>
                                )}

                            </div>

                        </>
                    ) : (
                        <MessageBox type={"error"} title={"ERROR:"}>
                            {data.error}
                        </MessageBox>
                    )}
                </Fade>
            }
        </ServerContentBlock >
    )
};