// Exemple rapide dans Editapi.jsx
const [formData, setFormData] = useState(null);
const [options, setOptions] = useState({ departments: [], config_types: [] });

useEffect(() => {
    const fetchEditData = async () => {
        try {
            // L'URL contient l'ID de la désignation à modifier
            const response = await axios.get(`/api/designations/${id}/edit`);
            setFormData(response.data.designation);
            setOptions({
                departments: response.data.departments,
                config_types: response.data.config_types
            });
        } catch (error) {
            console.error("Erreur lors du chargement des données d'édition");
        }
    };
    fetchEditData();
}, [id]);