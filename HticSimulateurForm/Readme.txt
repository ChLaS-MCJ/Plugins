# HTIC Simulateur Énergie - Plugin WordPress

## 📁 Structure des fichiers

Votre dossier `HticSimulateurForm` doit contenir la structure suivante :

```
HticSimulateurForm/
├── simulateur-energie.php          (Fichier principal du plugin)
├── css/
│   ├── admin.css                   (Styles pour l'administration)
│   └── frontend.css                (Styles pour le formulaire front)
├── js/
│   ├── admin.js                    (Scripts pour l'administration)
│   └── frontend.js                 (Scripts pour le formulaire front)
└── templates/
    └── simulateur-form.php         (Template du formulaire)
```

## 🚀 Installation

### 1. Placer les fichiers
- Copiez le contenu du fichier principal dans `simulateur-energie.php`
- Placez le CSS d'administration dans `css/admin.css`
- Placez le JavaScript d'administration dans `js/admin.js`

### 2. Activer le plugin
1. Allez dans **WordPress Admin → Extensions**
2. Trouvez "HTIC Simulateur Consommation Énergie"
3. Cliquez sur **Activer**

### 3. Configurer les données
1. Dans l'admin WordPress, allez dans **Simulateur Énergie**
2. Configurez les 4 onglets avec vos tarifs :
   - ⚡ **Électricité Résidentiel**
   - 🔥 **Gaz Résidentiel**  
   - 🏢 **Électricité Professionnel**
   - 🏭 **Gaz Professionnel**

## ⚙️ Configuration par défaut

Le plugin s'installe avec les valeurs de votre fichier Excel :

### Électricité Résidentiel
- **Tarifs BASE** : 9,69€ à 44,43€/mois selon puissance
- **Tarifs HC** : HP 0,27€/kWh, HC 0,2068€/kWh
- **Consommations** :
  - Chauffage : 70-215 kWh/m²/an selon isolation
  - Eau chaude : 1800 kWh/an
  - Électroménagers : 1497 kWh/an
  - Éclairage : 750 kWh/an

### Coefficients multiplicateurs
- **Maison** : 1.0 / **Appartement** : 0.8
- **Personnes** : 0.7 (1 pers) à 1.3 (5+ pers)

## 🎛️ Interface d'administration

### Fonctionnalités disponibles :
- ✅ **4 onglets** pour configurer tous les tarifs
- ✅ **Sauvegarde automatique** (2 secondes après modification)
- ✅ **Export/Import** de configuration en JSON
- ✅ **Validation** en temps réel des champs
- ✅ **Recherche** dans les champs
- ✅ **Raccourcis clavier** (Ctrl+S, Ctrl+E)
- ✅ **Réinitialisation** aux valeurs par défaut

### Raccourcis clavier :
- **Ctrl+S** : Sauvegarder
- **Ctrl+E** : Exporter la configuration
- **Échap** : Effacer la recherche

## 🔧 Utilisation sur le site

### Shortcode principal :
```php
[htic_simulateur_energie type="elec_residentiel"]
```

### Types disponibles :
- `elec_residentiel` - Électricité particuliers
- `gaz_residentiel` - Gaz particuliers  
- `elec_professionnel` - Électricité professionnels
- `gaz_professionnel` - Gaz professionnels

### Exemple d'intégration :
```php
// Dans une page ou un article
[htic_simulateur_energie type="elec_residentiel" theme="default"]

// Dans un template PHP
<?php echo do_shortcode('[htic_simulateur_energie type="elec_residentiel"]'); ?>
```

## 📊 Données stockées

Les données sont sauvegardées dans les options WordPress :
- `htic_simulateur_elec_residentiel_data`
- `htic_simulateur_gaz_residentiel_data`
- `htic_simulateur_elec_professionnel_data`
- `htic_simulateur_gaz_professionnel_data`

## 🔒 Sécurité

- ✅ **Vérification des permissions** (manage_options)
- ✅ **Nonces** pour toutes les actions AJAX
- ✅ **Sanitisation** des données
- ✅ **Échappement** des sorties
- ✅ **Protection** contre l'accès direct

## 🎨 Personnalisation

### CSS personnalisé :
Ajoutez dans votre thème ou via l'admin WordPress :

```css
/* Personnaliser les couleurs du simulateur */
.htic-simulateur-section {
    border-left-color: #votre-couleur;
}

.htic-simulateur-tabs .nav-tab.nav-tab-active {
    color: #votre-couleur;
}
```

### Hooks disponibles :
```php
// Filtrer les données avant sauvegarde
add_filter('htic_simulateur_before_save', 'my_custom_filter');

// Action après sauvegarde
add_action('htic_simulateur_after_save', 'my_custom_action');
```

## 📝 Prochaines étapes

1. **Créer les fichiers frontend** (`css/frontend.css`, `js/frontend.js`)
2. **Créer le template** (`templates/simulateur-form.php`)
3. **Tester l'interface** d'administration
4. **Personnaliser** selon vos besoins
5. **Ajouter** les calculateurs professionnels

## 🐛 Débogage

Activez le mode debug en ajoutant `?debug=1` à l'URL d'administration.

Fonctions debug disponibles dans la console :
```javascript
// Obtenir les données de l'onglet actuel
hticSimulateurDebug.getCurrentData()

// Exporter la configuration
hticSimulateurDebug.exportData()

// Sauvegarder manuellement
hticSimulateurDebug.saveTab()
```

## 📞 Support

- Vérifiez que tous les fichiers sont bien placés
- Activez les logs WordPress pour voir les erreurs
- Testez sur un environnement de développement d'abord

## 🔄 Mise à jour

Pour mettre à jour le plugin :
1. **Exportez** votre configuration actuelle
2. Remplacez les fichiers
3. **Réimportez** votre configuration

---

**Version** : 1.0.0  
**Auteur** : HTIC  
**Licence** : Propriétaire