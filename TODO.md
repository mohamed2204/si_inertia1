- [x] Add navigationGroup 'Scolarité' and navigationLabel 'Phases' to PhaseResource
- [x] Complete the form schema with TextInput for 'nom' and Toggle for 'ordre'
- [x] Complete the table columns with TextColumn for 'nom' and BooleanColumn for 'ordre'

# 1. Définir l'utilisateur actuel comme propriétaire et www-data comme groupe
sudo chown -R $USER:www-data .

# 2. Dossiers en 775 (lecture/écriture pour vous et le serveur)
find . -type d -exec chmod 775 {} \;

# 3. Fichiers en 664 (lecture/écriture pour vous et le serveur)
find . -type f -exec chmod 664 {} \;

# 4. Permissions spéciales pour le stockage et le cache (indispensable pour Laravel)
sudo chmod -R g+s storage bootstrap/cache
