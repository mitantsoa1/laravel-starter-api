# Variables
DOCKER_COMPOSE = docker-compose
EXEC_APP = $(DOCKER_COMPOSE) exec app
EXEC_NODE = $(DOCKER_COMPOSE) exec node
EXEC_DB = $(DOCKER_COMPOSE) exec db

# Couleurs pour les messages
RED=\033[0;31m
GREEN=\033[0;32m
YELLOW=\033[0;33m
NC=\033[0m # No Color

# Commandes principales
.PHONY: help up down build restart logs shell artisan composer npm

help: ## Affiche cette aide
	@echo "Commandes disponibles:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'
	@echo ""

up: ## Démarre les conteneurs en arrière-plan
	@echo "$(YELLOW)Démarrage des conteneurs...$(NC)"
	$(DOCKER_COMPOSE) up -d
	@echo "$(GREEN)Conteneurs démarrés$(NC)"

down: ## Arrête et supprime les conteneurs
	@echo "$(YELLOW)Arrêt des conteneurs...$(NC)"
	$(DOCKER_COMPOSE) down
	@echo "$(GREEN)Conteneurs arrêtés$(NC)"

stop: ## Arrête les conteneurs sans les supprimer
	@echo "$(YELLOW)Arrêt des conteneurs...$(NC)"
	$(DOCKER_COMPOSE) stop
	@echo "$(GREEN)Conteneurs arrêtés$(NC)"

build: ## Reconstruit les images Docker
	@echo "$(YELLOW)Construction des images...$(NC)"
	$(DOCKER_COMPOSE) build --no-cache
	@echo "$(GREEN)Images construites$(NC)"

restart: ## Redémarre les conteneurs
	@echo "$(YELLOW)Redémarrage des conteneurs...$(NC)"
	$(DOCKER_COMPOSE) restart
	@echo "$(GREEN)Conteneurs redémarrés$(NC)"

logs: ## Affiche les logs des conteneurs
	$(DOCKER_COMPOSE) logs -f

logs-app: ## Affiche les logs du conteneur app
	$(DOCKER_COMPOSE) logs -f app

logs-db: ## Affiche les logs du conteneur db
	$(DOCKER_COMPOSE) logs -f db

logs-node: ## Affiche les logs du conteneur node
	$(DOCKER_COMPOSE) logs -f node

shell: ## Ouvre un shell dans le conteneur app
	$(EXEC_APP) sh

shell-root: ## Ouvre un shell root dans le conteneur app
	$(EXEC_APP) sh -c "su -"

db: ## Ouvre une connexion MySQL
	$(EXEC_DB) mysql -u root -p

# Commandes Laravel - Artisan
artisan: ## Exécute une commande artisan (ex: make artisan cache:clear)
	$(EXEC_APP) php artisan $(filter-out $@,$(MAKECMDGOALS))

migrate: ## Exécute les migrations
	@echo "$(YELLOW)Exécution des migrations...$(NC)"
	$(EXEC_APP) php artisan migrate
	@echo "$(GREEN)Migrations terminées$(NC)"

migrate-fresh: ## Réinitialise la base de données et exécute les migrations
	@echo "$(YELLOW)Réinitialisation de la base de données...$(NC)"
	$(EXEC_APP) php artisan migrate:fresh
	@echo "$(GREEN)Base de données réinitialisée$(NC)"

migrate-fresh-seed: ## Réinitialise la base et exécute les seeders
	@echo "$(YELLOW)Réinitialisation et peuplement de la base...$(NC)"
	$(EXEC_APP) php artisan migrate:fresh --seed
	@echo "$(GREEN)Base de données réinitialisée et peuplée$(NC)"

seed: ## Exécute les seeders
	@echo "$(YELLOW)Peuplement de la base de données...$(NC)"
	$(EXEC_APP) php artisan db:seed
	@echo "$(GREEN)Peuplement terminé$(NC)"

clear: ## Vide tous les caches
	@echo "$(YELLOW)Vidage des caches...$(NC)"
	$(EXEC_APP) php artisan optimize:clear
	@echo "$(GREEN)Caches vidés$(NC)"

cache-clear: ## Vide le cache
	$(EXEC_APP) php artisan cache:clear

config-clear: ## Vide le cache de configuration
	$(EXEC_APP) php artisan config:clear

route-clear: ## Vide le cache des routes
	$(EXEC_APP) php artisan route:clear

view-clear: ## Vide le cache des vues
	$(EXEC_APP) php artisan view:clear

optimize: ## Optimise l'application
	@echo "$(YELLOW)Optimisation de l'application...$(NC)"
	$(EXEC_APP) php artisan optimize
	@echo "$(GREEN)Application optimisée$(NC)"

key-generate: ## Génère une nouvelle clé d'application
	@echo "$(YELLOW)Génération de la clé d'application...$(NC)"
	$(EXEC_APP) php artisan key:generate
	@echo "$(GREEN)Clé générée$(NC)"

tinker: ## Ouvre Tinker
	$(EXEC_APP) php artisan tinker

# Commandes Composer
composer: ## Exécute une commande composer (ex: make composer require package)
	$(EXEC_APP) composer $(filter-out $@,$(MAKECMDGOALS))

install: ## Installe les dépendances PHP
	@echo "$(YELLOW)Installation des dépendances PHP...$(NC)"
	$(EXEC_APP) composer install
	@echo "$(GREEN)Dépendances PHP installées$(NC)"

update: ## Met à jour les dépendances PHP
	@echo "$(YELLOW)Mise à jour des dépendances PHP...$(NC)"
	$(EXEC_APP) composer update
	@echo "$(GREEN)Dépendances PHP mises à jour$(NC)"

dump: ## Regénère l'autoloader
	$(EXEC_APP) composer dump-autoload

# Commandes NPM/Node
npm: ## Exécute une commande npm (ex: make npm install)
	$(EXEC_NODE) npm $(filter-out $@,$(MAKECMDGOALS))

npm-install: ## Installe les dépendances Node.js
	@echo "$(YELLOW)Installation des dépendances Node.js...$(NC)"
	$(EXEC_NODE) npm install
	@echo "$(GREEN)Dépendances Node.js installées$(NC)"

npm-dev: ## Compile les assets pour le développement
	@echo "$(YELLOW)Compilation des assets (dev)...$(NC)"
	$(EXEC_NODE) npm run dev
	@echo "$(GREEN)Assets compilés$(NC)"

npm-watch: ## Surveille les changements et compile les assets
	$(EXEC_NODE) npm run watch

npm-prod: ## Compile les assets pour la production
	@echo "$(YELLOW)Compilation des assets (prod)...$(NC)"
	$(EXEC_NODE) npm run prod
	@echo "$(GREEN)Assets compilés pour la production$(NC)"

# Commandes de test
test: ## Exécute les tests PHPUnit
	@echo "$(YELLOW)Exécution des tests...$(NC)"
	$(EXEC_APP) php artisan test
	@echo "$(GREEN)Tests terminés$(NC)"

test-pest: ## Exécute les tests Pest
	$(EXEC_APP) ./vendor/bin/pest

# Commandes de base de données
db-backup: ## Crée une sauvegarde de la base de données
	@mkdir -p backups
	$(DOCKER_COMPOSE) exec db mysqldump -u root -proot laravel > backups/backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)Sauvegarde créée$(NC)"

db-import: ## Importe le dernier fichier de sauvegarde
	@latest=$$(ls -t backups/*.sql | head -1); \
	if [ -f "$$latest" ]; then \
		echo "$(YELLOW)Importation de $$latest...$(NC)"; \
		$(DOCKER_COMPOSE) exec -T db mysql -u root -proot laravel < $$latest; \
		echo "$(GREEN)Importation terminée$(NC)"; \
	else \
		echo "$(RED)Aucune sauvegarde trouvée$(NC)"; \
	fi

# Commandes système
ps: ## Liste les conteneurs
	$(DOCKER_COMPOSE) ps

volumes: ## Liste les volumes
	docker volume ls

clean: ## Nettoie les conteneurs, images et volumes non utilisés
	@echo "$(YELLOW)Nettoyage Docker...$(NC)"
	docker system prune -f
	@echo "$(GREEN)Nettoyage terminé$(NC)"

# Cette règle permet de passer des arguments aux commandes artisan, composer, npm
%:
	@: