import React, { useState } from 'react';
import Modal from '@/components/elements/Modal';
import { Form, Formik, FormikHelpers } from 'formik';
import Field from '@/components/elements/Field';
import { object, string } from 'yup';
import createServerDatabase from '@/api/server/databases/createServerDatabase';
import { ServerContext } from '@/state/server';
import { httpErrorToHuman } from '@/api/http';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import Button from '@/components/elements/Button';
import tw from 'twin.macro';

interface Values {
    databaseName: string;
    connectionsFrom: string;
}

const schema = object().shape({
    databaseName: string()
        .required('Es muss ein Datenbankname angegeben werden.')
        .min(3, 'Der Datenbankname muss mindestens 3 Zeichen lang sein.')
        .max(48, 'Der Datenbankname darf 48 Zeichen nicht überschreiten.')
        .matches(/^[A-Za-z0-9_\-.]{3,48}$/, 'Der Datenbankname sollte nur alphanumerische Zeichen, Unterstriche, Bindestriche und/oder Punkte enthalten.'),
    connectionsFrom: string()
        .required('A connection value must be provided.')
        .matches(/^([0-9]{1,3}|%)(\.([0-9]{1,3}|%))?(\.([0-9]{1,3}|%))?(\.([0-9]{1,3}|%))?$/, 'A valid connection address must be provided.'),
});

export default () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { addError, clearFlashes } = useFlash();
    const [ visible, setVisible ] = useState(false);

    const appendDatabase = ServerContext.useStoreActions(actions => actions.databases.appendDatabase);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('database:create');
        createServerDatabase(uuid, { ...values })
            .then(database => {
                appendDatabase(database);
                setVisible(false);
            })
            .catch(error => {
                addError({ key: 'database:create', message: httpErrorToHuman(error) });
                setSubmitting(false);
            });
    };

    return (
        <>
            <Formik
                onSubmit={submit}
                initialValues={{ databaseName: '', connectionsFrom: '%' }}
                validationSchema={schema}
            >
                {
                    ({ isSubmitting, resetForm }) => (
                        <Modal
                            visible={visible}
                            dismissable={!isSubmitting}
                            showSpinnerOverlay={isSubmitting}
                            onDismissed={() => {
                                resetForm();
                                setVisible(false);
                            }}
                        >
                            <FlashMessageRender byKey={'database:create'} css={tw`mb-6`}/>
                            <h2 css={tw`text-2xl mb-6`}>Eine neue Datenbank erstellen</h2>
                            <Form css={tw`m-0`}>
                                <Field
                                    type={'string'}
                                    id={'database_name'}
                                    name={'databaseName'}
                                    label={'Datenbank Name'}
                                    description={'Ein beschreibender Name für Deine neue Datenbank. '}
                                />
                                <div css={tw`hidden mt-6`}>
                                    <Field
                                        type={'hidden'}
                                        id={'connections_from'}
                                        name={'connectionsFrom'}
                                        label={'Connections From'}
                                        description={'Where connections should be allowed from. Use % for wildcards.'}
                                    />
                                </div>
                                <div css={tw`flex flex-wrap justify-end mt-6`}>
                                    <Button
                                        type={'button'}
                                        isSecondary
                                        css={tw`w-full sm:w-auto sm:mr-2`}
                                        onClick={() => setVisible(false)}
                                    >
                                        Cancel
                                    </Button>
                                    <Button css={tw`w-full mt-4 sm:w-auto sm:mt-0`} type={'submit'}>
                                        Datenbank erstellen
                                    </Button>
                                </div>
                            </Form>
                        </Modal>
                    )
                }
            </Formik>
            <Button onClick={() => setVisible(true)}>
                Neue Datenbank
            </Button>
        </>
    );
};
