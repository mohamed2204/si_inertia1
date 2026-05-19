import axios from 'axios';
import Swal from 'sweetalert2';

const apiClient = axios.create({
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
});

const cache = new Map();

// INTERCEPTEUR D'ERREURS
apiClient.interceptors.response.use(
    response => response,
    error => {
        const url = error.config.url;
        if (cache.has(url)) cache.delete(url); // Nettoyage du cache en cas d'erreur

        const status = error.response?.status;
        if (status === 403) {
            Swal.fire('Sécurité', 'Action non autorisée (Policy).', 'error');
        } else if (status === 401) {
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

const api = {
    get: async (url, useCache = true) => {
        if (useCache && cache.has(url)) return cache.get(url);

        const { data } = await apiClient.get(url);
        if (useCache) cache.set(url, data);
        return data;
    },

    // Appels centralisés
    getSousDepts: (deptId) => api.get(`/api/departments/${deptId}/sous-departments`),
    getLabs: (sousDeptId) => api.get(`/api/sous-departments/${sousDeptId}/labs`),
    getLabDays: (labId) => api.get(`/api/labs/${labId}/days`),

    // Recherche (Toujours sans cache pour l'autocomplétion)
    searchMembers: (labId, query) => apiClient.get(`/api/labs/${labId}/search-members`, {
        params: { q: query }
    }),

    saveAll: (payload) => apiClient.post('/api/designations/store', payload),

    // Pour l'index, on évite le cache global pour avoir des données fraîches
    // mais on utilise l'apiClient pour la sécurité
    getDesignationsIndex: (params) => apiClient.get('/designations-list', { params }),

    // --- NOUVELLE MÉTHODE AJOUTÉE ---
    /**
     * Supprime une désignation via l'apiClient
     * @param {number|string} id - L'identifiant de la désignation
     */
    deleteDesignation: async (id) => {
        const { data } = await apiClient.delete(`/api/designations/${id}`);

        // OPTIONNEL : Si vous avez un cache global sur la liste des désignations,
        // c'est le moment idéal pour faire un `cache.clear()` ou retirer la clé spécifique
        // afin de forcer le front-end à recharger des données propres.
        if (typeof cache !== 'undefined' && typeof cache.clear === 'function') {
            cache.clear();
        }

        return data;
    },
};

export default api;