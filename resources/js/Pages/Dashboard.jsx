import Layout from '@/Layouts/layout'; // Vérifiez bien le chemin vers votre fichier layout

import { usePage } from '@inertiajs/react';
import { hasRole } from '@/Utils/Permissions';

const Dashboard = () => {

    const { auth } = usePage().props;

    return (
        <Layout>
            <div className="card">
                <h5>Bienvenue dans votre système de gestion d'école</h5>
                <p>Le template Sakai est maintenant fonctionnel sous Laravel + Inertia !</p>
            </div>
            <div className="card">
                {/* Exemple d'utilisation */}
                {auth.user ? (
                    <span>Bienvenue, {auth.user.name} ({auth.user.roles.join(', ')})</span>
                ) : (
                    <span>Invité</span>
                )}
                <h5>Informations de l'utilisateur connecté</h5>
                <p><strong>Nom :</strong> {auth.user.name}</p>
                <p><strong>Email :</strong> {auth.user.email}</p>

                {hasRole(auth, 'admin') && (
                    <input type='button' value='Supprimer' className='p-button-danger' />
                )}
            </div>
        </Layout>
    );
};

export default Dashboard;