import React, { useEffect } from 'react'

//! Icons
import { faUsers } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

//! Plugins
import useFlash from '@/plugins/useFlash';

//! API
import { PlayersResponse } from './PlayersContainer';
import getPlayers from "@/api/server/getPlayers";

//! Vendors
import tw from "twin.macro";
import useSWR from "swr";

const PlayersCounter = ({ uuid }: any) => {
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data, error } = useSWR<PlayersResponse>([uuid, '/players'], key => getPlayers(key), {
        revalidateOnFocus: true,
        errorRetryCount: 7,
        refreshInterval: 0,
    });
    useEffect(() => {
        if (!error) {
            clearFlashes('players');
        } else {
            clearAndAddHttpError({ key: 'players', error });
        }
    }, [error]);
    return (
        <>
            {!data ? (
                <div css={tw`flex-1 ml-4 sm:block hidden`}>
                    <div css={tw`flex justify-center`}>
                        <FontAwesomeIcon css={tw`text-neutral-500`} icon={faUsers} />
                        <p css={tw`text-sm ml-2 text-neutral-500`}>
                            0
                        </p>
                    </div>
                    <p css={tw`text-xs text-neutral-600 text-center mt-1`}>of 0</p>
                </div>
            ) : (
                <div css={tw`flex-1 ml-4 sm:block hidden`}>
                    <div css={tw`flex justify-center`}>
                        <FontAwesomeIcon css={tw`text-neutral-500`} icon={faUsers} />
                        <p css={tw`text-sm ml-2 text-neutral-500`}>
                            {data?.online_players ?? 0}
                        </p>
                    </div>
                    <p css={tw`text-xs text-neutral-600 text-center mt-1`}>von {data?.max_players ?? 0}</p>
                </div>
            )}
        </>
    )
}

export default PlayersCounter;