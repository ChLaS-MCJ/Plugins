<?php
// ==========================================
// FICHIER: admin/admin-gaz-residentiel.php
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
    
    <h2>🔥 Tarifs Gaz Naturel Résidentiel (TTC)</h2>
    <p class="description">Configuration des tarifs et consommations pour les particuliers au gaz naturel - Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>
    
    <div class="htic-simulateur-section">
        <h3>💰 Tarification Gaz Naturel</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Abonnement mensuel (€ TTC)</th>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_abo_mensuel]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_abo_mensuel'] ?? 102.12); ?>" 
                        style="width: 120px;" />
                    <span class="description">Tarif fixe mensuel d'abonnement</span>
                </td>
            </tr>
            <tr>
                <th scope="row">Prix du kWh (€ TTC)</th>
                <td>
                    <input type="number" step="0.0001" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_prix_kwh]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_prix_kwh'] ?? 0.0878); ?>" 
                        style="width: 120px;" />
                    <span class="description">Prix unitaire du kWh gaz naturel</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="htic-simulateur-section">
        <h3>🏠 Consommations Moyennes par Usage (kWh/an)</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Usage</th>
                    <th>Consommation annuelle (kWh)</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Cuisson</strong></td>
                    <td>
                        <input type="number" 
                            name="htic_simulateur_gaz_residentiel_data[gaz_cuisson_annuel]" 
                            value="<?php echo esc_attr($gaz_residentiel['gaz_cuisson_annuel'] ?? 250); ?>" 
                            style="width: 100px;" />
                        <small>kWh/an</small>
                    </td>
                    <td><em>Consommation moyenne pour la cuisson (plaques, four gaz)</em></td>
                </tr>
                <tr>
                    <td><strong>Eau chaude sanitaire</strong></td>
                    <td>
                        <input type="number" 
                            name="htic_simulateur_gaz_residentiel_data[gaz_eau_chaude_base]" 
                            value="<?php echo esc_attr($gaz_residentiel['gaz_eau_chaude_base'] ?? 2000); ?>" 
                            style="width: 100px;" />
                        <small>kWh/an</small>
                    </td>
                    <td><em>Consommation de base pour l'eau chaude sanitaire</em></td>
                </tr>
                <tr>
                    <td><strong>Eau chaude par personne</strong></td>
                    <td>
                        <input type="number" 
                            name="htic_simulateur_gaz_residentiel_data[gaz_eau_chaude_par_personne]" 
                            value="<?php echo esc_attr($gaz_residentiel['gaz_eau_chaude_par_personne'] ?? 400); ?>" 
                            style="width: 100px;" />
                        <small>kWh/an/pers</small>
                    </td>
                    <td><em>Consommation supplémentaire d'eau chaude par personne</em></td>
                </tr>
                <tr>
                    <td><strong>Cuisson par personne</strong></td>
                    <td>
                        <input type="number" 
                            name="htic_simulateur_gaz_residentiel_data[gaz_cuisson_par_personne]" 
                            value="<?php echo esc_attr($gaz_residentiel['gaz_cuisson_par_personne'] ?? 50); ?>" 
                            style="width: 100px;" />
                        <small>kWh/an/pers</small>
                    </td>
                    <td><em>Consommation supplémentaire de cuisson par personne</em></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="htic-simulateur-section">
        <h3>🏠 Chauffage selon l'Isolation du Logement (kWh/m²/an)</h3>
        <p class="description">Consommation de chauffage gaz par m² selon la période de construction et l'isolation</p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Niveau d'isolation</th>
                    <th>Période de construction</th>
                    <th>Consommation (kWh/m²/an)</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background-color: #fee;">
                    <td><span style="color: #dc3545;">⚡ Niveau 1 - Très mal isolé</span></td>
                    <td><strong>Avant 1980</strong></td>
                    <td>
                        <input type="number" 
                            name="htic_simulateur_gaz_residentiel_data[gaz_chauffage_avant_1980]" 
                            value="<?php echo esc_attr($gaz_residentiel['gaz_chauffage_avant_1980'] ?? 160); ?>" 
                            style="width: 90px;" />
                    </td>
                    <td><em>Logements anciens sans isolation</em></td>
                </tr>
                <tr style="background-color: #fff3cd;">
                    <td><span style="color: #fd7e14;">🔥 Niveau 2 - Mal isolé</span></td>
                    <td><strong>1980 - 2000</strong></td>
                    <td>
                        <input type="number" 
                            name="htic_simulateur_gaz_residentiel_data[gaz_chauffage_1980_2000]" 
                            value="<?php echo esc_attr($gaz_residentiel['gaz_chauffage_1980_2000'] ?? 70); ?>" 
                            style="width: 90px;" />
                    </td>
                    <td><em>Isolation basique, premières normes</em></td>
                </tr>
                <tr style="background-color: #d1ecf1;">
                    <td><span style="color: #0c5460;">🏠 Niveau 3 - Bien isolé</span></td>
                    <td><strong>Après 2000</strong></td>
                    <td>
                        <input type="number" 
                            name="htic_simulateur_gaz_residentiel_data[gaz_chauffage_apres_2000]" 
                            value="<?php echo esc_attr($gaz_residentiel['gaz_chauffage_apres_2000'] ?? 110); ?>" 
                            style="width: 90px;" />
                    </td>
                    <td><em>Normes thermiques RT2000, RT2005</em></td>
                </tr>
                <tr style="background-color: #d4edda;">
                    <td><span style="color: #155724;">✅ Niveau 4 - Très bien isolé</span></td>
                    <td><strong>Rénovation récente</strong></td>
                    <td>
                        <input type="number" 
                            name="htic_simulateur_gaz_residentiel_data[gaz_chauffage_renovation]" 
                            value="<?php echo esc_attr($gaz_residentiel['gaz_chauffage_renovation'] ?? 20); ?>" 
                            style="width: 90px;" />
                    </td>
                    <td><em>Rénovation BBC, RT2012+, bâtiment passif</em></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="htic-simulateur-section">
        <h3>🏠 Coefficients par Type de Logement</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Coefficient Maison</th>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_residentiel_data[coefficient_maison]" 
                        value="<?php echo esc_attr($gaz_residentiel['coefficient_maison'] ?? 1.0); ?>" 
                        style="width: 100px;" />
                    <span class="description">Multiplicateur pour les maisons individuelles (défaut: 1.0)</span>
                </td>
            </tr>
            <tr>
                <th scope="row">Coefficient Appartement</th>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_residentiel_data[coefficient_appartement]" 
                        value="<?php echo esc_attr($gaz_residentiel['coefficient_appartement'] ?? 0.85); ?>" 
                        style="width: 100px;" />
                    <span class="description">Multiplicateur pour les appartements (défaut: 0.85)</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="htic-simulateur-section">
        <h3>🌡️ Paramètres de Température</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Température de référence (°C)</th>
                <td>
                    <input type="number" step="0.5" 
                        name="htic_simulateur_gaz_residentiel_data[temperature_reference]" 
                        value="<?php echo esc_attr($gaz_residentiel['temperature_reference'] ?? 19.0); ?>" 
                        style="width: 100px;" />
                    <span class="description">Température de consigne pour le calcul (défaut: 19°C)</span>
                </td>
            </tr>
            <tr>
                <th scope="row">Majoration par degré supplémentaire (%)</th>
                <td>
                    <input type="number" step="0.1" 
                        name="htic_simulateur_gaz_residentiel_data[majoration_par_degre]" 
                        value="<?php echo esc_attr($gaz_residentiel['majoration_par_degre'] ?? 7.0); ?>" 
                        style="width: 100px;" />
                    <span class="description">% d'augmentation de consommation par degré supplémentaire</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="htic-simulateur-section">
        <h3>ℹ️ Paramètres Divers</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Surface minimum pour le chauffage (m²)</th>
                <td>
                    <input type="number" 
                        name="htic_simulateur_gaz_residentiel_data[surface_min_chauffage]" 
                        value="<?php echo esc_attr($gaz_residentiel['surface_min_chauffage'] ?? 15); ?>" 
                        style="width: 100px;" />
                    <span class="description">Surface minimum pour activer le chauffage</span>
                </td>
            </tr>
            <tr>
                <th scope="row">Nombre de personnes minimum</th>
                <td>
                    <input type="number" 
                        name="htic_simulateur_gaz_residentiel_data[nb_personnes_min]" 
                        value="<?php echo esc_attr($gaz_residentiel['nb_personnes_min'] ?? 1); ?>" 
                        style="width: 100px;" />
                    <span class="description">Nombre minimum d'occupants pour les calculs</span>
                </td>
            </tr>
        </table>
    </div>
    
    <?php submit_button('🔥 Sauvegarder les tarifs gaz résidentiel'); ?>
</form>