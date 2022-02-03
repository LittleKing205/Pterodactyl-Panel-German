import http from '@/api/http';
import { PlayersResponse } from "@/components/server/players/PlayersContainer";

export default async (uuid: string): Promise<PlayersResponse> => {
    //const { data } = await http.get(`/api/client/servers/${uuid}/players`);
    //return (data.data || []);

    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/players`)
            .then(({ data }) => resolve(data.data))
            .catch(reject);
    });
};