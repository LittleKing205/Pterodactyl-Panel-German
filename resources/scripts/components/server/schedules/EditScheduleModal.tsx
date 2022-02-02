import React, { useContext, useEffect } from 'react';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import Field from '@/components/elements/Field';
import { Form, Formik, FormikHelpers } from 'formik';
import FormikSwitch from '@/components/elements/FormikSwitch';
import createOrUpdateSchedule from '@/api/server/schedules/createOrUpdateSchedule';
import { ServerContext } from '@/state/server';
import { httpErrorToHuman } from '@/api/http';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import ModalContext from '@/context/ModalContext';
import asModal from '@/hoc/asModal';

interface Props {
    schedule?: Schedule;
}

interface Values {
    name: string;
    dayOfWeek: string;
    month: string;
    dayOfMonth: string;
    hour: string;
    minute: string;
    enabled: boolean;
    onlyWhenOnline: boolean;
}

const EditScheduleModal = ({ schedule }: Props) => {
    const { addError, clearFlashes } = useFlash();
    const { dismiss } = useContext(ModalContext);

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const appendSchedule = ServerContext.useStoreActions(actions => actions.schedules.appendSchedule);

    useEffect(() => {
        return () => {
            clearFlashes('schedule:edit');
        };
    }, []);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('schedule:edit');
        createOrUpdateSchedule(uuid, {
            id: schedule?.id,
            name: values.name,
            cron: {
                minute: values.minute,
                hour: values.hour,
                dayOfWeek: values.dayOfWeek,
                month: values.month,
                dayOfMonth: values.dayOfMonth,
            },
            onlyWhenOnline: values.onlyWhenOnline,
            isActive: values.enabled,
        })
            .then(schedule => {
                setSubmitting(false);
                appendSchedule(schedule);
                dismiss();
            })
            .catch(error => {
                console.error(error);

                setSubmitting(false);
                addError({ key: 'schedule:edit', message: httpErrorToHuman(error) });
            });
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                name: schedule?.name || '',
                minute: schedule?.cron.minute || '*/5',
                hour: schedule?.cron.hour || '*',
                dayOfMonth: schedule?.cron.dayOfMonth || '*',
                month: schedule?.cron.month || '*',
                dayOfWeek: schedule?.cron.dayOfWeek || '*',
                enabled: schedule?.isActive ?? true,
                onlyWhenOnline: schedule?.onlyWhenOnline ?? true,
            } as Values}
        >
            {({ isSubmitting }) => (
                <Form>
                    <h3 css={tw`text-2xl mb-6`}>{schedule ? 'Zeitplan bearbeiten' : 'Neuen Zeitplan erstellen'}</h3>
                    <FlashMessageRender byKey={'schedule:edit'} css={tw`mb-6`}/>
                    <Field
                        name={'name'}
                        label={'Zeitplan Name'}
                        description={'Eine für Menschen lesbare Kennung für diesen Zeitplan. '}
                    />
                    <div css={tw`grid grid-cols-2 sm:grid-cols-5 gap-4 mt-6`}>
                        <Field name={'minute'} label={'Minute'}/>
                        <Field name={'hour'} label={'Stunde'}/>
                        <Field name={'dayOfMonth'} label={'Tag des Monats'}/>
                        <Field name={'month'} label={'Monat'}/>
                        <Field name={'dayOfWeek'} label={'Tag der Woche'}/>
                    </div>
                    <p css={tw`text-neutral-400 text-xs mt-2`}>
                        Das Zeitplansystem unterstützt die Verwendung der Cronjob-Syntax beim Definieren, wann Aufgaben ausgeführt werden sollen.
                        Verwende die Felder oben, um anzugeben, wann diese Tasks ausgeführt werden sollen. 
                    </p>
                    <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                        <FormikSwitch
                            name={'onlyWhenOnline'}
                            description={'Führe diesen Zeitplan nur aus, wenn der Server ausgeführt wird.'}
                            label={'Nur wenn der Server online ist'}
                        />
                    </div>
                    <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                        <FormikSwitch
                            name={'enabled'}
                            description={'Dieser Zeitplan wird automatisch ausgeführt, wenn er aktiviert ist.'}
                            label={'Zeitplan Aktiv'}
                        />
                    </div>
                    <div css={tw`mt-6 text-right`}>
                        <Button css={tw`w-full sm:w-auto`} type={'submit'} disabled={isSubmitting}>
                            {schedule ? 'Änderungen speichern' : 'Zeitplan erstellen'}
                        </Button>
                    </div>
                </Form>
            )}
        </Formik>
    );
};

export default asModal<Props>()(EditScheduleModal);
