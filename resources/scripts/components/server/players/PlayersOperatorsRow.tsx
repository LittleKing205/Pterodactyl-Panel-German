import React from 'react';

//! Interfaces
import { Operators } from "./PlayersContainer";

//! Components
import GreyRowBox from '@/components/elements/GreyRowBox';
import Button from '@/components/elements/Button';
import Can from '@/components/elements/Can';

//! Vendors
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faUserMinus } from '@fortawesome/free-solid-svg-icons';
import tw from "twin.macro";

//! States
import sendCommand from '@/api/server/sendCommand';


interface Props {
    uuid: string,
    ops: Operators,
    className?: string,
    refresh?: () => void,
}

const PlayersOperatorsRow = ({ uuid, ops, className, refresh }: Props) => {
    const handleClick = () => {
        sendCommand(uuid, `deop ${ops.name}`);
        if (refresh) {
            setTimeout(() => refresh(), 4000);
        }
    }
    return (
        <GreyRowBox $hoverable={false} className={className} css={tw`mb-2`}>
            <div css={tw`hidden md:block`}>
                <img width={30} height={30} src={`https://mc-heads.net/avatar/${ops.name}/30/nohelm`} alt="" />
            </div>

            <div css={tw`flex-1 ml-4`}>
                <p css={tw`text-lg`}>{ops.name || "Noname"}</p>
            </div>

            <div css={tw`ml-8`}>
                <Can action={'players.deop'}>
                    <Button color={'red'} isSecondary css={tw`mr-2`} onClick={() => handleClick()}>
                        <FontAwesomeIcon icon={faUserMinus} fixedWidth />
                    </Button>
                </Can>
            </div>
        </GreyRowBox>
    )
};

export default PlayersOperatorsRow;
