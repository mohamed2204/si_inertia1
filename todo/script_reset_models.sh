#!/bin/bash

echo "=== RESET MODELES / MIGRATIONS / FILAMENT ==="

php artisan migrate:fresh --force

# === MODELES METIER ===
php artisan make:model Specialite -mf
php artisan make:model Phase -mf
php artisan make:model Promotion -mf
php artisan make:model PromotionPhase -mf
php artisan make:model Eleve -mf
php artisan make:model Matiere -mf
php artisan make:model ProgrammeMatiere -mf
php artisan make:model Note -mf

# Entité métier clé
php artisan make:model PromotionSpecialitePhase -mf

# === POLICIES ===
php artisan make:policy SpecialitePolicy --model=Specialite
php artisan make:policy PhasePolicy --model=Phase
php artisan make:policy PromotionPolicy --model=Promotion
php artisan make:policy ElevePolicy --model=Eleve
php artisan make:policy MatierePolicy --model=Matiere
php artisan make:policy NotePolicy --model=Note
php artisan make:policy PromotionSpecialitePhasePolicy --model=PromotionSpecialitePhase
php artisan make:policy ProgrammeMatierePolicy --model=ProgrammeMatiere

# === FILAMENT RESOURCES ===
php artisan make:filament-resource Specialite
php artisan make:filament-resource Phase
php artisan make:filament-resource Promotion
php artisan make:filament-resource Eleve
php artisan make:filament-resource PromotionSpecialitePhase
php artisan make:filament-resource Matiere
php artisan make:filament-resource Note
php artisan make:filament-resource ProgrammeMatiere


# ===== SEEDERS =====

php artisan make:seeder SpecialiteSeeder
php artisan make:seeder PhaseSeeder
php artisan make:seeder PromotionSeeder
php artisan make:seeder PromotionSpecialitePhaseSeeder
php artisan make:seeder MatiereSeeder
php artisan make:seeder EleveSeeder
php artisan make:seeder ProgrammeMatiereSeeder
php artisan make:seeder NoteSeeder


echo "=== TERMINÉ ==="
