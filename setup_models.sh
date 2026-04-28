#!/bin/bash

set -e

echo "========================================="
echo " Génération modèles + migrations"
echo "========================================="

# Modèles principaux
php artisan make:model Specialite -m -f
php artisan make:model Phase -m -f
php artisan make:model Matiere -m -f
php artisan make:model ProgrammeMatiere -m -f
php artisan make:model Promotion -m -f
php artisan make:model Eleve -m -f
php artisan make:model Note -m -f
php artisan make:model Classement -m -f

echo "========================================="
echo " Génération des Policies"
echo "========================================="

php artisan make:policy SpecialitePolicy --model=Specialite
php artisan make:policy PhasePolicy --model=Phase
php artisan make:policy MatierePolicy --model=Matiere
php artisan make:policy ProgrammeMatierePolicy --model=ProgrammeMatiere
php artisan make:policy PromotionPolicy --model=Promotion
php artisan make:policy ElevePolicy --model=Eleve
php artisan make:policy NotePolicy --model=Note
php artisan make:policy ClassementPolicy --model=Classement

echo "========================================="
echo " Génération terminée avec succès"
echo "========================================="
