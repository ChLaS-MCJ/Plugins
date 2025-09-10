<?php
// ==========================================
// FICHIER 4: admin/admin-gaz-professionnel.php
// ==========================================
/**
 * Onglet Gaz Professionnel - Interface d'administration
 * Fichier: admin/admin-gaz-professionnel.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les données existantes
$gaz_professionnel = get_option('htic_simulateur_gaz_professionnel_data', array());

// Si les données sont vides, utiliser les valeurs par défaut
if (empty($gaz_professionnel)) {
    $plugin_instance = new HticSimulateurEnergieAdmin();
    $gaz_professionnel = $plugin_instance->get_default_gaz_professionnel();
}
?>

<form method="post" action="options.php" class="htic-simulateur-form">
    <?php settings_fields('htic_simulateur_gaz_professionnel'); ?>
    
    <h2>🏭 Tarifs Gaz Professionnel (TTC)</h2>
    <p class="description">Configuration des tarifs et consommations gaz pour les entreprises - Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>
    
    <div class="htic-simulateur-section">
        <!-- Ici mettre tout le contenu de l'onglet 4 Gaz Professionnel -->
        <!-- Tarifs gaz pro, consommations par activité (bureau, commerce, restaurant, industrie, etc.) -->
    </div>
    
    <?php submit_button('💾 Sauvegarder les tarifs gaz professionnel'); ?>
</form>