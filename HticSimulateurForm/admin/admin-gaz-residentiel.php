<?php
// ==========================================
// FICHIER 2: admin/admin-gaz-residentiel.php
// ==========================================
/**
 * Onglet Gaz Résidentiel - Interface d'administration
 * Fichier: admin/admin-gaz-residentiel.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les données existantes
$gaz_residentiel = get_option('htic_simulateur_gaz_residentiel_data', array());

// Si les données sont vides, utiliser les valeurs par défaut
if (empty($gaz_residentiel)) {
    $plugin_instance = new HticSimulateurEnergieAdmin();
    $gaz_residentiel = $plugin_instance->get_default_gaz_residentiel();
}
?>

<form method="post" action="options.php" class="htic-simulateur-form">
    <?php settings_fields('htic_simulateur_gaz_residentiel'); ?>
    
    <h2>🔥 Tarifs Gaz Résidentiel (TTC)</h2>
    <p class="description">Configuration des tarifs et consommations gaz pour les particuliers - Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>
    
    <div class="htic-simulateur-section">
        <!-- Ici mettre tout le contenu de l'onglet 2 Gaz Résidentiel -->
        <!-- Tarifs gaz, consommations chauffage, eau chaude, cuisson, etc. -->
    </div>
    
    <?php submit_button('💾 Sauvegarder les tarifs gaz résidentiel'); ?>
</form>