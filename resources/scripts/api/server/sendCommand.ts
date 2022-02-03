import http from '@/api/http';

export default (uuid: string, command: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/command`, { command })
            .then(() => resolve())
            .catch(reject);
    });
};
