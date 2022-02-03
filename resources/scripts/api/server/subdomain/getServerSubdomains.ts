import http from '@/api/http';
import { SubdomainResponse } from '@/components/server/subdomain/SubdomainContainer';

export default async (uuid: string): Promise<SubdomainResponse> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/subdomain`);

    return (data.data || []);
};
