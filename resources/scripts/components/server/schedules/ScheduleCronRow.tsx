import React from 'react';
import tw from 'twin.macro';
import { Schedule } from '@/api/server/schedules/getServerSchedules';

interface Props {
    cron: Schedule['cron'];
    className?: string;
}

const ScheduleCronRow = ({ cron, className }: Props) => (
    <div css={tw`flex`} className={className}>
        <div css={tw`w-1/5 sm:w-auto text-center`}>
            <p css={tw`font-medium`}>{cron.minute}</p>
            <p css={tw`text-2xs text-neutral-500 uppercase`}>Minute</p>
        </div>
        <div css={tw`w-1/5 sm:w-auto text-center ml-4`}>
            <p css={tw`font-medium`}>{cron.hour}</p>
            <p css={tw`text-2xs text-neutral-500 uppercase`}>Stunde</p>
        </div>
        <div css={tw`w-1/5 sm:w-auto text-center ml-4`}>
            <p css={tw`font-medium`}>{cron.dayOfMonth}</p>
            <p css={tw`text-2xs text-neutral-500 uppercase`}>Tag (Monat)</p>
        </div>
        <div css={tw`w-1/5 sm:w-auto text-center ml-4`}>
            <p css={tw`font-medium`}>{cron.month}</p>
            <p css={tw`text-2xs text-neutral-500 uppercase`}>Monat</p>
        </div>
        <div css={tw`w-1/5 sm:w-auto text-center ml-4`}>
            <p css={tw`font-medium`}>{cron.dayOfWeek}</p>
            <p css={tw`text-2xs text-neutral-500 uppercase`}>Tag (Woche)</p>
        </div>
    </div>
);

export default ScheduleCronRow;
