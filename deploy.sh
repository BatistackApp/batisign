#!/bin/bash
set -e

echo "Début du déploiement..."

# Mise en mode maintenance
(php artisan down) || echo "Le mode maintenance est déjà activé."

# Mise à jour du code (optionnel si géré par un outil tiers)
# git pull origin main

# Installation des dépendances PHP
composer install --no-interaction --prefer-dist --optimize-autoloader

# Exécution des migrations de la base de données
php artisan migrate --force

# Optimisation du cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Installation des dépendances Node et compilation des assets
npm install
npm run build

# Fin du mode maintenance
php artisan up

echo "Déploiement terminé avec succès !"
