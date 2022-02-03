import React from 'react';

//! Interfaces
import { Player, Operators } from "./PlayersContainer";

//! Components
import CopyOnClick from '@/components/elements/CopyOnClick';
import GreyRowBox from '@/components/elements/GreyRowBox';
import Button from '@/components/elements/Button';
import Can from '@/components/elements/Can';

//! Vendors
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faUser, faGavel, faSignOutAlt, faUserMinus, faUserPlus } from '@fortawesome/free-solid-svg-icons';
import tw from "twin.macro";

//! States
import { ServerContext } from '@/state/server';
import isEqual from 'react-fast-compare';
import sendCommand from '@/api/server/sendCommand';


interface Props {
    uuid: string,
    admin: Operators | boolean,
    player: Player,
    className?: string,
    refresh?: () => void,
}

type HandleAction = 'deop' | 'op' | 'kick' | 'ban' | 'unban';

const PlayersRow = ({ uuid, admin, player, className, refresh }: Props) => {
    const eggFeatures = ServerContext.useStoreState(state => state.server.data!.eggFeatures, isEqual);

    const handleClick = (type: HandleAction) => {
        switch (type) {
            case 'deop':
                sendCommand(uuid, `deop ${player.name}`);
                break;
            case 'op':
                sendCommand(uuid, `op ${player.name}`);
                break;

            case 'kick':
                sendCommand(uuid, `kick ${player.name}`);
                break;

            case 'ban':
                sendCommand(uuid, `ban ${player.name}`);
                break;

            case 'unban':
                sendCommand(uuid, `pardon ${player.name}`);
                break;
        }
        if (refresh) {
            setTimeout(() => refresh(), 4000);
        }
    }

    return (
        <GreyRowBox $hoverable={false} className={className} css={tw`mb-2`}>
            <div css={tw`hidden md:block`}>
                {eggFeatures.includes('eula') ? (
                    <img src={`https://mc-heads.net/avatar/${player.name}/30/nohelm`} alt="" />
                ) : (
                    <FontAwesomeIcon icon={faUser} fixedWidth />
                )}

            </div>

            <div css={tw`flex-1 ml-4`}>
                <p css={tw`text-lg`}>
                    {player?.steamid != null ? (
                        <a href={`https://steamcommunity.com/profiles/${player.steamid}`} target="_blank">{player.name || "Noname"}</a>
                    ) : (
                        <>{player.name || "Noname"} {admin ? (<span css={tw`text-neutral-400`}>(ADMIN)</span>) : ""}</>
                    )}
                </p>
            </div>

            {player?.health != null && (
                <div css={tw`ml-8 text-center hidden md:block`}>
                    <p css={tw`text-sm`}>{Math.round(player.health)}</p>
                    <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>Gesundheit</p>
                </div>
            )}

            {player?.steamid != null && (
                <div css={tw`ml-8 text-center hidden md:block`}>
                    <CopyOnClick text={`${player.steamid}`}>
                        <p css={tw`text-sm`}>{player.steamid}</p>
                    </CopyOnClick>
                    <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>SteamID</p>
                </div>
            )}

            {player?.score != null && (
                <div css={tw`ml-8 text-center hidden md:block`}>
                    <p css={tw`text-sm`}>{player.score}</p>
                    <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>Frags</p>
                </div>
            )}

            {player?.time != null && (
                <div css={tw`ml-8 text-center hidden md:block`}>
                    <p css={tw`text-sm`}>
                        {
                            //@ts-ignore
                            new Date(player?.time * 1000).toISOString().substr(11, 8)
                        }
                    </p>
                    <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>Spielzeit</p>
                </div>
            )}

            {player?.ping != null && (
                <div css={tw`ml-8 text-center hidden md:block`}>
                    <p css={tw`text-sm`}>{player.ping}</p>
                    <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>Ping</p>
                </div>
            )}

            {eggFeatures.includes("eula") && (
                <div css={tw`ml-8`}>
                    <Can action={'players.op'}>
                        {admin ? (
                            <Button color={'red'} isSecondary css={tw`mr-2`} onClick={() => handleClick('deop')}>
                                <FontAwesomeIcon icon={faUserMinus} fixedWidth />
                            </Button>
                        ) : (
                            <Button color={'green'} isSecondary css={tw`mr-2`} onClick={() => handleClick('op')}>
                                <FontAwesomeIcon icon={faUserPlus} fixedWidth />
                            </Button>
                        )}
                    </Can>

                    <Can action={'players.kick'}>
                        <Button color={'grey'} isSecondary css={tw`mr-2`} onClick={() => handleClick('kick')}>
                            <FontAwesomeIcon icon={faSignOutAlt} fixedWidth />
                        </Button>
                    </Can>

                    <Can action={'players.ban'}>
                        <Button color={'red'} isSecondary onClick={() => handleClick('ban')}>
                            <FontAwesomeIcon icon={faGavel} fixedWidth />
                        </Button>
                    </Can>
                </div>
            )}
        </GreyRowBox>
    )
};

export default PlayersRow;
