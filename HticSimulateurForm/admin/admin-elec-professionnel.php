<?php
// ==========================================
// FICHIER 3: admin/admin-elec-professionnel.php
// ==========================================
/**
 * Onglet Électricité Professionnel - Interface d'administration
 * Fichier: admin/admin-elec-professionnel.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les données existantes
$elec_professionnel = get_option('htic_simulateur_elec_professionnel_data', array());

// Si les données sont vides, utiliser les valeurs par défaut
if (empty($elec_professionnel)) {
    $plugin_instance = new HticSimulateurEnergieAdmin();
    $elec_professionnel = $plugin_instance->get_default_elec_professionnel();
}
?>

<form method="post" action="options.php" class="htic-simulateur-form">
    <?php settings_fields('htic_simulateur_elec_professionnel'); ?>
    
    <h2>🏢 Tarifs Électricité Professionnel (TTC)</h2>
    <p class="description">Configuration des tarifs et consommations électriques pour les entreprises - Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>
    
    <div class="htic-simulateur-section">
        <!-- Ici mettre tout le contenu de l'onglet 3 Électricité Professionnel -->
        <!-- Tarifs pro, consommations par activité (bureau, commerce, restaurant, etc.) -->
    </div>
    
    <?php submit_button('💾 Sauvegarder les tarifs électricité professionnel'); ?>
</form>