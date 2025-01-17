import React, { useEffect, useState } from 'react';
import ContentBox from '@/components/elements/ContentBox';
import CreateApiKeyForm from '@/components/dashboard/forms/CreateApiKeyForm';
import getApiKeys, { ApiKey } from '@/api/account/getApiKeys';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faKey, faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import deleteApiKey from '@/api/account/deleteApiKey';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import FlashMessageRender from '@/components/FlashMessageRender';
import { httpErrorToHuman } from '@/api/http';
import { format } from 'date-fns';
import PageContentBlock from '@/components/elements/PageContentBlock';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';

export default () => {
    const [ deleteIdentifier, setDeleteIdentifier ] = useState('');
    const [ keys, setKeys ] = useState<ApiKey[]>([]);
    const [ loading, setLoading ] = useState(true);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    useEffect(() => {
        clearFlashes('account');
        getApiKeys()
            .then(keys => setKeys(keys))
            .then(() => setLoading(false))
            .catch(error => {
                console.error(error);
                addError({ key: 'account', message: httpErrorToHuman(error) });
            });
    }, []);

    const doDeletion = (identifier: string) => {
        setLoading(true);
        clearFlashes('account');
        deleteApiKey(identifier)
            .then(() => setKeys(s => ([
                ...(s || []).filter(key => key.identifier !== identifier),
            ])))
            .catch(error => {
                console.error(error);
                addError({ key: 'account', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    };

    return (
        <PageContentBlock title={'Konto API'}>
            <FlashMessageRender byKey={'account'}/>
            <div css={tw`md:flex flex-nowrap my-10`}>
                <ContentBox title={'Erstellung eines API Schlüssels'} css={tw`flex-none w-full md:w-1/2`}>
                    <CreateApiKeyForm onKeyCreated={key => setKeys(s => ([ ...s!, key ]))}/>
                </ContentBox>
                <ContentBox title={'API Schlüssel'} css={tw`flex-1 overflow-hidden mt-8 md:mt-0 md:ml-8`}>
                    <SpinnerOverlay visible={loading}/>
                    <ConfirmationModal
                        visible={!!deleteIdentifier}
                        title={'Löschen des API Schlüssels bestätigen'}
                        buttonText={'Ja, Schlüssel löschen'}
                        onConfirmed={() => {
                            doDeletion(deleteIdentifier);
                            setDeleteIdentifier('');
                        }}
                        onModalDismissed={() => setDeleteIdentifier('')}
                    >
                        Möchtest du diesen API-Schlüssel wirklich löschen? Alle Anfragen, die es verwenden, werden sofort ungültig und schlagen fehl.
                    </ConfirmationModal>
                    {
                        keys.length === 0 ?
                            <p css={tw`text-center text-sm`}>
                                {loading ? 'Lädt...' : 'Es existieren keine API Schlüssel für dieses Konto'}
                            </p>
                            :
                            keys.map((key, index) => (
                                <GreyRowBox
                                    key={key.identifier}
                                    css={[ tw`bg-neutral-600 flex items-center`, index > 0 && tw`mt-2` ]}
                                >
                                    <FontAwesomeIcon icon={faKey} css={tw`text-neutral-300`}/>
                                    <div css={tw`ml-4 flex-1 overflow-hidden`}>
                                        <p css={tw`text-sm break-words`}>{key.description}</p>
                                        <p css={tw`text-2xs text-neutral-300 uppercase`}>
                                            Last used:&nbsp;
                                            {key.lastUsedAt ? format(key.lastUsedAt, 'MMM do, yyyy HH:mm') : 'Never'}
                                        </p>
                                    </div>
                                    <p css={tw`text-sm ml-4 hidden md:block`}>
                                        <code css={tw`font-mono py-1 px-2 bg-neutral-900 rounded`}>
                                            {key.identifier}
                                        </code>
                                    </p>
                                    <button
                                        css={tw`ml-4 p-2 text-sm`}
                                        onClick={() => setDeleteIdentifier(key.identifier)}
                                    >
                                        <FontAwesomeIcon
                                            icon={faTrashAlt}
                                            css={tw`text-neutral-400 hover:text-red-400 transition-colors duration-150`}
                                        />
                                    </button>
                                </GreyRowBox>
                            ))
                    }
                </ContentBox>
            </div>
        </PageContentBlock>
    );
};
