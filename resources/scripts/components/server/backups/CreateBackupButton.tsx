import React, { useEffect, useState } from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Field as FormikField, Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import { boolean, object, string } from 'yup';
import Field from '@/components/elements/Field';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import useFlash from '@/plugins/useFlash';
import createServerBackup from '@/api/server/backups/createServerBackup';
import FlashMessageRender from '@/components/FlashMessageRender';
import Button from '@/components/elements/Button';
import tw from 'twin.macro';
import { Textarea } from '@/components/elements/Input';
import getServerBackups from '@/api/swr/getServerBackups';
import { ServerContext } from '@/state/server';
import FormikSwitch from '@/components/elements/FormikSwitch';
import Can from '@/components/elements/Can';

interface Values {
    name: string;
    ignored: string;
    isLocked: boolean;
}

const ModalContent = ({ ...props }: RequiredModalProps) => {
    const { isSubmitting } = useFormikContext<Values>();

    return (
        <Modal {...props} showSpinnerOverlay={isSubmitting}>
            <Form>
                <FlashMessageRender byKey={'backups:create'} css={tw`mb-4`}/>
                <h2 css={tw`text-2xl mb-6`}>Erstelle Server Backup</h2>
                <Field
                    name={'name'}
                    label={'Backup Name'}
                    description={'Falls angegeben, der Name, der verwendet werden soll, um auf diese Sicherung zu verweisen.'}
                />
                <div css={tw`mt-6`}>
                    <FormikFieldWrapper
                        name={'ignored'}
                        label={'Ignorierte Dateien und Ordner'}
                        description={`
                            Geben Sie die Dateien oder Ordner ein, die beim Erstellen dieser Sicherung ignoriert werden sollen. Lassen Sie das Feld leer, um den Inhalt der Datei .pteroignore im Stammverzeichnis des Servers zu verwenden, falls vorhanden. Der Wildcard-Abgleich von Dateien und Ordnern wird zusätzlich zum Negieren einer Regel unterstützt, indem dem Pfad ein Ausrufezeichen vorangestellt wird.
                        `}
                    >
                        <FormikField as={Textarea} name={'ignored'} rows={6}/>
                    </FormikFieldWrapper>
                </div>
                <Can action={'backup.delete'}>
                    <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                        <FormikSwitch
                            name={'isLocked'}
                            label={'Gesperrt'}
                            description={'Verhindert, dass dieses Backup gelöscht wird, bis es explizit entsperrt wird.'}
                        />
                    </div>
                </Can>
                <div css={tw`flex justify-end mt-6`}>
                    <Button type={'submit'} disabled={isSubmitting}>
                        Backup Starten
                    </Button>
                </div>
            </Form>
        </Modal>
    );
};

export default () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const [ visible, setVisible ] = useState(false);
    const { mutate } = getServerBackups();

    useEffect(() => {
        clearFlashes('backups:create');
    }, [ visible ]);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('backups:create');
        createServerBackup(uuid, values)
            .then(backup => {
                mutate(data => ({ ...data, items: data.items.concat(backup), backupCount: data.backupCount + 1 }), false);
                setVisible(false);
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'backups:create', error });
                setSubmitting(false);
            });
    };

    return (
        <>
            {visible &&
            <Formik
                onSubmit={submit}
                initialValues={{ name: '', ignored: '', isLocked: false }}
                validationSchema={object().shape({
                    name: string().max(191),
                    ignored: string(),
                    isLocked: boolean(),
                })}
            >
                <ModalContent appear visible={visible} onDismissed={() => setVisible(false)}/>
            </Formik>
            }
            <Button css={tw`w-full sm:w-auto`} onClick={() => setVisible(true)}>
                Backup erstellen
            </Button>
        </>
    );
};
