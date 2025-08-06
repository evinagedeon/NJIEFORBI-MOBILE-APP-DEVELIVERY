# LUCOCHER - Réseau Commercial

LUCOCHER est une plateforme de commerce électronique innovante qui connecte les consommateurs avertis avec des producteurs et importateurs de qualité.

## 🚀 Fonctionnalités Principales

### Pour les Consommateurs
- **Courses en ligne** avec livraison à domicile
- **Ventes Flash** et promotions exclusives
- **Enchères** sur des produits sélectionnés
- **Forums communautaires** par centres d'intérêt
- **Marketplace** organisée par secteurs d'activité
- **Mur social** avec contenus sponsorisés
- **Système de favoris** et recommandations
- **Panier intelligent** avec suggestions

### Pour les Entreprises
- **Gestion des produits** avec images et descriptions
- **Système de validation** pour producteurs/importateurs
- **Outils promotionnels** et ventes flash
- **Mur social** pour promouvoir les produits
- **Système de sponsoring** pour plus de visibilité
- **Tableau de bord** avec statistiques de ventes
- **Gestion des commandes** et des stocks

### Fonctionnalités Système
- **Authentification sécurisée** avec types d'utilisateurs
- **Interface responsive** moderne
- **Notifications en temps réel**
- **Système de paiement** intégré
- **Gestion des abonnements** et droits d'accès
- **API REST** pour les interactions AJAX
- **Upload d'images** optimisé
- **Recherche avancée** avec filtres

## 🛠 Technologies Utilisées

- **Backend**: PHP 8.0+, MySQL 8.0+, PDO
- **Frontend**: HTML5, CSS3, JavaScript ES6+, Bootstrap 5.3
- **Icons**: Font Awesome 6.0
- **AJAX**: Fetch API native
- **Responsive**: Mobile-first design
- **Sécurité**: Password hashing, SQL injection protection, XSS prevention

## 📋 Prérequis

- PHP 8.0 ou supérieur
- MySQL 8.0 ou supérieur
- Serveur web (Apache/Nginx)
- Extensions PHP : PDO, GD, JSON, Session

## 🚀 Installation

1. **Cloner le projet**
   ```bash
   git clone [URL_DU_REPO]
   cd lucocher
   ```

2. **Configuration de la base de données**
   ```bash
   # Créer la base de données
   mysql -u root -p < database/schema.sql
   ```

3. **Configuration**
   - Modifier `config/database.php` avec vos paramètres de BDD
   - Créer les dossiers d'upload :
     ```bash
     mkdir -p uploads/products uploads/posts uploads/avatars
     chmod 755 uploads/
     ```

4. **Permissions**
   ```bash
   chmod 755 assets/ includes/ config/
   chmod 644 *.php
   ```

## 🏗 Structure du Projet

```
lucocher/
├── api/                    # APIs REST
│   ├── add-to-cart.php
│   ├── cart-count.php
│   └── social-posts.php
├── assets/                 # Ressources statiques
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
├── config/                 # Configuration
│   └── database.php
├── database/              # Scripts SQL
│   └── schema.sql
├── includes/              # Fonctions communes
│   └── functions.php
├── uploads/               # Fichiers uploadés
├── index.php             # Page d'accueil
├── login.php             # Connexion
├── register.php          # Inscription
├── dashboard.php         # Tableau de bord
└── logout.php            # Déconnexion
```

## 🗄 Base de Données

### Tables Principales

- **users**: Utilisateurs (consommateurs + entreprises)
- **consumer_profiles**: Profils consommateurs
- **business_profiles**: Profils entreprises
- **products**: Catalogue produits
- **orders**: Commandes
- **social_posts**: Mur social
- **forums**: Système de forums
- **flash_sales**: Ventes flash
- **auctions**: Système d'enchères

### Secteurs d'Activité
- Alimentation
- Mode & Beauté
- Maison & Jardin
- High-Tech
- Sport & Loisirs
- Santé & Bien-être
- Auto & Moto
- Livres & Culture

## 👥 Types d'Utilisateurs

### Consommateurs
- Inscription gratuite
- Accès à tous les produits
- Participation aux forums
- Abonnement premium optionnel

### Entreprises
- **Producteur**: Fabricant de produits
- **Importateur**: Importation de produits
- **Revendeur**: Revente de produits

### Validation Entreprise
Les entreprises doivent être validées selon ces critères :
- Statut de producteur ou importateur vérifié
- Innovation ou plus-value produit
- Produits en promotion lors des premiers mois
- Échantillons pour tests qualité

## 🔒 Sécurité

- Hashage des mots de passe avec `password_hash()`
- Protection contre les injections SQL avec PDO
- Échappement XSS avec `htmlspecialchars()`
- Validation des données côté serveur
- Sessions sécurisées
- Upload de fichiers contrôlé

## 📱 Responsive Design

L'interface s'adapte automatiquement :
- **Desktop**: Vue complète avec sidebar
- **Tablet**: Navigation adaptée
- **Mobile**: Interface optimisée tactile

## 🎨 Personnalisation

### Couleurs CSS Variables
```css
:root {
    --primary-color: #007bff;
    --secondary-color: #6c757d;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
}
```

### Ajout de Secteurs
Modifier `database/schema.sql` pour ajouter des secteurs :
```sql
INSERT INTO sectors (name, description, icon) VALUES
('Nouveau Secteur', 'Description', 'fas fa-icon');
```

## 🚀 Déploiement

### Environnement de Production
1. Activer le mode production dans `config/database.php`
2. Configurer HTTPS
3. Optimiser les images
4. Mettre en place la sauvegarde BDD
5. Configurer les logs d'erreur

### Performance
- Cache des requêtes fréquentes
- Compression des images
- Minification CSS/JS
- CDN pour les ressources statiques

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 📞 Support

Pour toute question ou support :
- Email: support@lucocher.com
- Documentation: [docs.lucocher.com]
- Issues: [GitHub Issues]

## 🎯 Roadmap

### Version 2.0
- [ ] Application mobile native
- [ ] Système de chat en temps réel
- [ ] Intelligence artificielle pour recommandations
- [ ] Marketplace international
- [ ] Système de points de fidélité
- [ ] Intégration réseaux sociaux avancée

### Version 1.1
- [ ] Module de livraison avancé
- [ ] Système d'avis clients
- [ ] Géolocalisation des points de vente
- [ ] Notifications push
- [ ] Mode sombre

---

**LUCOCHER** - Révolutionnons le commerce ensemble ! 🛒✨

