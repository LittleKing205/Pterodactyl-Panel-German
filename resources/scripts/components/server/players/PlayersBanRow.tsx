import React from 'react';

//! Interfaces
import { Bans } from "./PlayersContainer";

//! Components
import GreyRowBox from '@/components/elements/GreyRowBox';
import Button from '@/components/elements/Button';
import Can from '@/components/elements/Can';

//! Vendors
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faGavel } from '@fortawesome/free-solid-svg-icons';
import tw from "twin.macro";

//! States
import sendCommand from '@/api/server/sendCommand';

interface Props {
    uuid: string,
    bans: Bans,
    className?: string,
    refresh?: () => void,
}

const PlayersRow = ({ uuid, bans, className, refresh }: Props) => {
    const handleClick = () => {
        sendCommand(uuid, `pardon ${bans.name}`);
        if (refresh) {
            setTimeout(() => refresh(), 4000);
        }
    }

    return (
        <GreyRowBox $hoverable={false} className={className} css={tw`mb-2`}>
            <div css={tw`hidden md:block`}>
                <img width={30} height={30} src={`https://mc-heads.net/avatar/${bans.name}/30/nohelm`} alt="" />
            </div>

            <div css={tw`flex-1 ml-4`}>
                <p css={tw`text-lg`}>{bans.name || "Noname"}</p>
            </div>

            <div css={tw`hidden flex-1 ml-4 md:block`}>
                <p css={tw`text-lg`}>{bans.expires}</p>
            </div>

            <div css={tw`hidden flex-1 ml-4 md:block`}>
                <p css={tw`text-lg`}>{bans.reason}</p>
            </div>

            <div css={tw`ml-8`}>
                <Can action={'players.unban'}>
                    <Button color={'green'} isSecondary onClick={() => handleClick()}>
                        <FontAwesomeIcon icon={faGavel} fixedWidth />
                    </Button>
                </Can>
            </div>
        </GreyRowBox>
    )
};

export default PlayersRow;
