#!/bin/bash

echo "🔄 Synchronisation Filament Shield..."

php artisan shield:generate

php artisan optimize:clear

echo "✅ Permissions Shield synchronisées avec succès."
