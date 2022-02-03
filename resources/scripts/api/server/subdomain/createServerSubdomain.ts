import http from '@/api/http';

export default (uuid: string, subdomain?: string, domainId?: number): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/subdomain/create`, {
            subdomain, domainId,
        }).then((data) => {
            resolve(data.data || []);
        }).catch(reject);
    });
};
