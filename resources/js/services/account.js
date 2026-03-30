import * as API from "./API";

var route = 'account';

export default {
    async list(payload) {
        return API.apiClient.get(`${route}/all`, {
            params: payload
        });
    },

    async validate(payload) {
        return API.apiClient.post(`${route}/validate`,payload);
    },

    async get(id) {
        return API.apiClient.get(`${route}/data/${id}`);
    },

    async create (payload) {
        return API.apiClient.post(`${route}/create`, payload);
    },

    async update (payload, id) {
        return API.apiClient.post(`${route}/update/${id}`, payload);
    },

    async regenerate_password(id) {
        return API.apiClient.post(`${route}/regenerate-password/${id}`);
    },

    async remove (id) {
        return API.apiClient.post(`${route}/delete/${id}`);
    },

    async update_profile(payload){
        return API.apiClient.post(`profile/update`, payload);
    },

    async update_password(payload) {
        return API.apiClient.put('password', payload);
    },
};
