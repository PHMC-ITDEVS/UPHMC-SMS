import * as API from "./API";

var route = 'audit-trail';

export default {
    async list(payload) {
        return API.apiClient.get(`${route}/all`, {
            params: payload
        });
    },

    async get(id) {
        return API.apiClient.get(`${route}/data/${id}`);
    },
};
