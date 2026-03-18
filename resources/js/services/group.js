import * as API from "./API";

var route = 'group';

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

    async addContacts(id, payload) {
        return API.apiClient.post(`${route}/${id}/contacts/add`, payload);
    },

    async removeContact(id, contactId) {
        return API.apiClient.post(`${route}/${id}/contacts/${contactId}/remove`);
    },

    async create (payload) {
        return API.apiClient.post(`${route}/create`, payload);
    },

    async update (payload, id) {
        return API.apiClient.post(`${route}/update/${id}`, payload);
    },

    async remove (id) {
        return API.apiClient.post(`${route}/delete/${id}`);
    },

    async update_profile(payload){
        return API.apiClient.post(`profile/update`, payload);
    }
};
