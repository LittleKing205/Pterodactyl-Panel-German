import React, { useState } from 'react';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import tw from 'twin.macro';
import Icon from '@/components/elements/Icon';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import { ServerContext } from '@/state/server';
import deleteServerAllocation from '@/api/server/network/deleteServerAllocation';
import getServerAllocations from '@/api/swr/getServerAllocations';
import useFlash from '@/plugins/useFlash';

interface Props {
    allocation: number;
}

const DeleteAllocationButton = ({ allocation }: Props) => {
    const [ confirm, setConfirm ] = useState(false);

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const setServerFromState = ServerContext.useStoreActions(actions => actions.server.setServerFromState);

    const { mutate } = getServerAllocations();
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const deleteAllocation = () => {
        clearFlashes('server:network');

        mutate(data => data?.filter(a => a.id !== allocation), false);
        setServerFromState(s => ({ ...s, allocations: s.allocations.filter(a => a.id !== allocation) }));

        deleteServerAllocation(uuid, allocation)
            .catch(error => clearAndAddHttpError({ key: 'server:network', error }));
    };

    return (
        <>
            <ConfirmationModal
                visible={confirm}
                title={'Diese zuweisung löschen?'}
                buttonText={'Löschen'}
                onConfirmed={deleteAllocation}
                onModalDismissed={() => setConfirm(false)}
            >
                Diese Zuordnung wird sofort von Ihrem Server entfernt. Bist du dir sicher, dass du fortfahren willst?
            </ConfirmationModal>
            <button
                css={tw`text-neutral-400 px-2 py-1 mr-2 transition-colors duration-150 hover:text-red-400`}
                type={'button'}
                onClick={() => setConfirm(true)}
            >
                <Icon icon={faTrashAlt} css={tw`w-3 h-auto`}/>
            </button>
        </>
    );
};

export default DeleteAllocationButton;
