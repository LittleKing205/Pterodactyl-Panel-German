import React, { useContext, useEffect } from 'react';
import { Schedule, Task } from '@/api/server/schedules/getServerSchedules';
import { Field as FormikField, Form, Formik, FormikHelpers, useField } from 'formik';
import { ServerContext } from '@/state/server';
import createOrUpdateScheduleTask from '@/api/server/schedules/createOrUpdateScheduleTask';
import { httpErrorToHuman } from '@/api/http';
import Field from '@/components/elements/Field';
import FlashMessageRender from '@/components/FlashMessageRender';
import { boolean, number, object, string } from 'yup';
import useFlash from '@/plugins/useFlash';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import tw from 'twin.macro';
import Label from '@/components/elements/Label';
import { Textarea } from '@/components/elements/Input';
import Button from '@/components/elements/Button';
import Select from '@/components/elements/Select';
import ModalContext from '@/context/ModalContext';
import asModal from '@/hoc/asModal';
import FormikSwitch from '@/components/elements/FormikSwitch';

interface Props {
    schedule: Schedule;
    // If a task is provided we can assume we're editing it. If not provided,
    // we are creating a new one.
    task?: Task;
}

interface Values {
    action: string;
    payload: string;
    timeOffset: string;
    continueOnFailure: boolean;
}

const schema = object().shape({
    action: string().required().oneOf([ 'command', 'power', 'backup' ]),
    payload: string().when('action', {
        is: v => v !== 'backup',
        then: string().required('Es muss ein Befhel eingegeben werden.'),
        otherwise: string(),
    }),
    continueOnFailure: boolean(),
    timeOffset: number().typeError('Der Zeitversatz muss eine gültige Zahl zwischen 0 und 900 sein. ')
        .required('Es muss ein Zeitversatzwert angegeben werden.')
        .min(0, 'Der Zeitversatz muss mindestens 0 Sekunden betragen.')
        .max(900, 'Der Zeitversatz muss kleiner als 900 Sekunden sein.'),
});

const ActionListener = () => {
    const [ { value }, { initialValue: initialAction } ] = useField<string>('action');
    const [ , { initialValue: initialPayload }, { setValue, setTouched } ] = useField<string>('payload');

    useEffect(() => {
        if (value !== initialAction) {
            setValue(value === 'power' ? 'start' : '');
            setTouched(false);
        } else {
            setValue(initialPayload || '');
            setTouched(false);
        }
    }, [ value ]);

    return null;
};

const TaskDetailsModal = ({ schedule, task }: Props) => {
    const { dismiss } = useContext(ModalContext);
    const { clearFlashes, addError } = useFlash();

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const appendSchedule = ServerContext.useStoreActions(actions => actions.schedules.appendSchedule);
    const backupLimit = ServerContext.useStoreState(state => state.server.data!.featureLimits.backups);

    useEffect(() => {
        return () => {
            clearFlashes('schedule:task');
        };
    }, []);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('schedule:task');
        if (backupLimit === 0 && values.action === 'backup') {
            setSubmitting(false);
            addError({ message: 'Eine Backupaufgabe kann nicht erstellt werden, wenn das Backuplimit des Servers auf 0 gesetzt ist. ', key: 'schedule:task' });
        } else {
            createOrUpdateScheduleTask(uuid, schedule.id, task?.id, values)
                .then(task => {
                    let tasks = schedule.tasks.map(t => t.id === task.id ? task : t);
                    if (!schedule.tasks.find(t => t.id === task.id)) {
                        tasks = [ ...tasks, task ];
                    }

                    appendSchedule({ ...schedule, tasks });
                    dismiss();
                })
                .catch(error => {
                    console.error(error);
                    setSubmitting(false);
                    addError({ message: httpErrorToHuman(error), key: 'schedule:task' });
                });
        }
    };

    return (
        <Formik
            onSubmit={submit}
            validationSchema={schema}
            initialValues={{
                action: task?.action || 'command',
                payload: task?.payload || '',
                timeOffset: task?.timeOffset.toString() || '0',
                continueOnFailure: task?.continueOnFailure || false,
            }}
        >
            {({ isSubmitting, values }) => (
                <Form css={tw`m-0`}>
                    <FlashMessageRender byKey={'schedule:task'} css={tw`mb-4`}/>
                    <h2 css={tw`text-2xl mb-6`}>{task ? 'Aufgabe bearbeiten' : 'Aufgabe erstellen'}</h2>
                    <div css={tw`flex`}>
                        <div css={tw`mr-2 w-1/3`}>
                            <Label>Action</Label>
                            <ActionListener/>
                            <FormikFieldWrapper name={'action'}>
                                <FormikField as={Select} name={'action'}>
                                    <option value={'command'}>Befehl senden</option>
                                    <option value={'power'}>Sende Power zustand</option>
                                    <option value={'backup'}>Backup erstellen</option>
                                </FormikField>
                            </FormikFieldWrapper>
                        </div>
                        <div css={tw`flex-1 ml-6`}>
                            <Field
                                name={'timeOffset'}
                                label={'Zeitversatz (in Sekunden)'}
                                description={'Die Wartezeit nach der Ausführung der vorherigen Aufgabe, bevor diese ausgeführt wird. Wenn dies die erste Aufgabe in einem Zeitplan ist, wird dies nicht angewendet.'}
                            />
                        </div>
                    </div>
                    <div css={tw`mt-6`}>
                        {values.action === 'command' ?
                            <div>
                                <Label>Befehl</Label>
                                <FormikFieldWrapper name={'payload'}>
                                    <FormikField as={Textarea} name={'payload'} rows={6}/>
                                </FormikFieldWrapper>
                            </div>
                            :
                            values.action === 'power' ?
                                <div>
                                    <Label>Befehl</Label>
                                    <FormikFieldWrapper name={'payload'}>
                                        <FormikField as={Select} name={'payload'}>
                                            <option value={'start'}>Server starten</option>
                                            <option value={'restart'}>Server neustarten</option>
                                            <option value={'stop'}>Server Stoppen</option>
                                            <option value={'kill'}>Server killen</option>
                                        </FormikField>
                                    </FormikFieldWrapper>
                                </div>
                                :
                                <div>
                                    <Label>Ignorierte Dateien</Label>
                                    <FormikFieldWrapper
                                        name={'payload'}
                                        description={'Optional. Trage hier die Dateien und Ordner ein, die nicht im Backp mit eingeschlossen werden sollen. Standardmäßig wird der Inhalt Ihrer .pteroignore-Datei verwendet. Wenn Dein Backup-Limit erreicht wurde, wird das älteste Backup rotiert.'}
                                    >
                                        <FormikField as={Textarea} name={'payload'} rows={6}/>
                                    </FormikFieldWrapper>
                                </div>
                        }
                    </div>
                    <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                        <FormikSwitch
                            name={'continueOnFailure'}
                            description={'Zukünftige Aufgaben werden ausgeführt, wenn diese Aufgabe fehlschlägt.'}
                            label={'Weiter bei Fehler'}
                        />
                    </div>
                    <div css={tw`flex justify-end mt-6`}>
                        <Button type={'submit'} disabled={isSubmitting}>
                            {task ? 'Änderungen speichern' : 'Aufgabe erstellen'}
                        </Button>
                    </div>
                </Form>
            )}
        </Formik>
    );
};

export default asModal<Props>()(TaskDetailsModal);
