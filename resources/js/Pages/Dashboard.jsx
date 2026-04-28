import Layout from '@/Layouts/layout'; // Vérifiez bien le chemin vers votre fichier layout

const Dashboard = () => {
    return (
        <Layout>
            <div className="card">
                <h5>Bienvenue dans votre système de gestion d'école</h5>
                <p>Le template Sakai est maintenant fonctionnel sous Laravel + Inertia !</p>
            </div>
        </Layout>
    );
};

export default Dashboard;