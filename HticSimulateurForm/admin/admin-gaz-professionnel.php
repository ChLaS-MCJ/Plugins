<?php
/**
 * Onglet Gaz Professionnel - Interface d'administration
 * Conforme aux données Excel "Conso Gaz Professionnel"
 * IMPORTANT: Toutes les clés sont préfixées avec "pro_" pour éviter les conflits
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les données existantes
$gaz_pro = get_option('htic_simulateur_gaz_professionnel_data', array());

// Si les données sont vides, utiliser les valeurs par défaut
if (empty($gaz_pro)) {
    $plugin_instance = new HticSimulateurEnergieAdmin();
    $gaz_pro = $plugin_instance->get_default_gaz_professionnel();
}

// Communes (identiques au résidentiel mais avec clé pro_communes_gaz)
$communes_gaz = isset($gaz_pro['pro_communes_gaz']) ? $gaz_pro['pro_communes_gaz'] : array(
    'AIRE SUR L\'ADOUR' => 'naturel',
    'BARCELONNE DU GERS' => 'naturel', 
    'BASCONS' => 'propane',
    'BENESSE LES DAX' => 'propane',
    'CAMPAGNE' => 'propane',
    'CARCARES SAINTE CROIX' => 'propane',
    'GAAS' => 'naturel',
    'GEAUNE' => 'propane',
    'LABATUT' => 'naturel',
    'LALUQUE' => 'naturel',
    'MAZEROLLES' => 'propane',
    'MEILHAN' => 'propane',
    'MISSON' => 'naturel',
    'PONTONX SUR L\'ADOUR' => 'propane',
    'POUILLON' => 'naturel',
    'SAINT MAURICE' => 'propane',
    'SOUPROSSE' => 'propane',
    'TETHIEU' => 'propane',
    'YGOS SAINT SATURNIN' => 'propane'
);
?>

<form method="post" action="options.php" class="htic-simulateur-form">
    <?php settings_fields('htic_simulateur_gaz_professionnel'); ?>
    
    <h2>🏢 Tarifs Gaz Professionnel (TTC) - Données Excel</h2>
    <p class="description">Configuration conforme au fichier Excel "Conso Gaz Professionnel" - Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>
    
    <h4>🗺️ Gestion des Communes (19 communes - identiques au résidentiel)</h4>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50%;">Commune</th>
                <th style="width: 30%;">Type de gaz</th>
                <th style="width: 20%;">Actions</th>
            </tr>
        </thead>
        <tbody id="communes-list-pro">
            <?php foreach ($communes_gaz as $commune => $type_gaz): ?>
            <tr data-commune="<?php echo esc_attr($commune); ?>">
                <td>
                    <input type="text" 
                           name="htic_simulateur_gaz_professionnel_data[pro_communes_gaz][<?php echo esc_attr($commune); ?>]" 
                           value="<?php echo esc_attr($commune); ?>" 
                           class="commune-nom" 
                           style="width: 100%;" />
                    <input type="hidden" 
                           name="htic_simulateur_gaz_professionnel_data[pro_communes_types][<?php echo esc_attr($commune); ?>]" 
                           value="<?php echo esc_attr($type_gaz); ?>" 
                           class="commune-type-hidden" />
                </td>
                <td>
                    <select class="commune-type" style="width: 100%;" data-commune="<?php echo esc_attr($commune); ?>">
                        <option value="naturel" <?php selected($type_gaz, 'naturel'); ?>>🔥 Gaz Naturel</option>
                        <option value="propane" <?php selected($type_gaz, 'propane'); ?>>⛽ Gaz Propane</option>
                    </select>
                </td>
                <td>
                    <button type="button" class="button button-secondary supprimer-commune" 
                            title="Supprimer cette commune">❌ Supprimer</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">
                    <button type="button" id="ajouter-commune-pro" class="button button-primary">➕ Ajouter une commune</button>
                    <span class="description" style="margin-left: 15px;">
                        Communes identiques au résidentiel
                    </span>
                </td>
            </tr>
        </tfoot>
    </table>

    <h4>💰 Tarification Gaz Naturel Professionnel (3 tranches)</h4>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Tranche</th>
                <th>Seuil (kWh/an)</th>
                <th>Abonnement (€/mois)</th>
                <th>Prix kWh (€ TTC)</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background-color: #e8f4fd;">
                <td><strong>GOM0</strong></td>
                <td>< 4 000</td>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_naturel_gom0_abo]" 
                        value="<?php echo esc_attr($gaz_pro['pro_gaz_naturel_gom0_abo'] ?? 8.92); ?>" 
                        style="width: 100px;" />
                </td>
                <td>
                    <input type="number" step="0.0001" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_naturel_gom0_kwh]" 
                        value="<?php echo esc_attr($gaz_pro['pro_gaz_naturel_gom0_kwh'] ?? 0.1265); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Petits consommateurs pro</em></td>
            </tr>
            <tr style="background-color: #f0f8e8;">
                <td><strong>GOM1</strong></td>
                <td>4 000 - 35 000</td>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_naturel_gom1_abo]" 
                        value="<?php echo esc_attr($gaz_pro['pro_gaz_naturel_gom1_abo'] ?? 22.4175); ?>" 
                        style="width: 100px;" />
                </td>
                <td>
                    <input type="number" step="0.0001" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_naturel_gom1_kwh]" 
                        value="<?php echo esc_attr($gaz_pro['pro_gaz_naturel_gom1_kwh'] ?? 0.0978); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Moyens consommateurs pro</em></td>
            </tr>
            <tr style="background-color: #fff3cd;">
                <td><strong>GOM2</strong></td>
                <td>≥ 35 000</td>
                <td colspan="2" style="text-align: center;">
                    <strong>Nous vous contacterons</strong>
                    <input type="hidden" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_naturel_gom2_abo]" 
                        value="0" />
                    <input type="hidden" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_naturel_gom2_kwh]" 
                        value="0" />
                </td>
                <td><em>Gros consommateurs (devis personnalisé)</em></td>
            </tr>
        </tbody>
    </table>

    <h4>⛽ Tarification Gaz Propane Professionnel (5 tranches)</h4>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Tranche</th>
                <th>Seuil (kWh/an)</th>
                <th>Abonnement (€/mois)</th>
                <th>Prix kWh (€ TTC)</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>P0</strong></td>
                <td>< 1 000</td>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_propane_p0_abo]" 
                        value="<?php echo esc_attr($gaz_pro['pro_gaz_propane_p0_abo'] ?? 4.64); ?>" 
                        style="width: 100px;" />
                </td>
                <td>
                    <input type="number" step="0.00001" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_propane_p0_kwh]" 
                        value="<?php echo esc_attr($gaz_pro['pro_gaz_propane_p0_kwh'] ?? 0.12479); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Très petits consommateurs pro</em></td>
            </tr>
            <tr>
                <td><strong>P1</strong></td>
                <td>1 000 - 5 999</td>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_propane_p1_abo]" 
                        value="<?php echo esc_attr($gaz_pro['pro_gaz_propane_p1_abo'] ?? 5.26); ?>" 
                        style="width: 100px;" />
                </td>
                <td>
                    <input type="number" step="0.00001" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_propane_p1_kwh]" 
                        value="<?php echo esc_attr($gaz_pro['pro_gaz_propane_p1_kwh'] ?? 0.11852); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Petits consommateurs pro</em></td>
            </tr>
            <tr style="background-color: #fff3cd;">
                <td><strong>P2</strong></td>
                <td>6 000 - 29 999</td>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_propane_p2_abo]" 
                        value="<?php echo esc_attr($gaz_pro['pro_gaz_propane_p2_abo'] ?? 16.06); ?>" 
                        style="width: 100px;" />
                </td>
                <td>
                    <input type="number" step="0.00001" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_propane_p2_kwh]" 
                        value="<?php echo esc_attr($gaz_pro['pro_gaz_propane_p2_kwh'] ?? 0.11305); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Consommateurs moyens pro</em></td>
            </tr>
            <tr>
                <td><strong>P3</strong></td>
                <td>30 000 - 349 999</td>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_propane_p3_abo]" 
                        value="<?php echo esc_attr($gaz_pro['pro_gaz_propane_p3_abo'] ?? 34.56); ?>" 
                        style="width: 100px;" />
                </td>
                <td>
                    <input type="number" step="0.00001" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_propane_p3_kwh]" 
                        value="<?php echo esc_attr($gaz_pro['pro_gaz_propane_p3_kwh'] ?? 0.10273); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Gros consommateurs pro</em></td>
            </tr>
            <tr>
                <td><strong>P4</strong></td>
                <td>≥ 350 000</td>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_propane_p4_abo]" 
                        value="<?php echo esc_attr($gaz_pro['pro_gaz_propane_p4_abo'] ?? 311.01); ?>" 
                        style="width: 100px;" />
                </td>
                <td>
                    <input type="number" step="0.00001" 
                        name="htic_simulateur_gaz_professionnel_data[pro_gaz_propane_p4_kwh]" 
                        value="<?php echo esc_attr($gaz_pro['pro_gaz_propane_p4_kwh'] ?? 0.10064); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Très gros consommateurs pro</em></td>
            </tr>
        </tbody>
    </table>

    <h4>🧮 Seuils de Tranches</h4>
    <table class="form-table">
        <tr>
            <th scope="row">Seuil GOM0/GOM1 (kWh/an)</th>
            <td>
                <input type="number" 
                    name="htic_simulateur_gaz_professionnel_data[pro_seuil_gom0_gom1]" 
                    value="<?php echo esc_attr($gaz_pro['pro_seuil_gom0_gom1'] ?? 4000); ?>" 
                    style="width: 100px;" />
                <span class="description">Passage de GOM0 à GOM1</span>
            </td>
        </tr>
        <tr>
            <th scope="row">Seuil GOM1/GOM2 (kWh/an)</th>
            <td>
                <input type="number" 
                    name="htic_simulateur_gaz_professionnel_data[pro_seuil_gom1_gom2]" 
                    value="<?php echo esc_attr($gaz_pro['pro_seuil_gom1_gom2'] ?? 35000); ?>" 
                    style="width: 100px;" />
                <span class="description">Au-delà : devis personnalisé</span>
            </td>
        </tr>
    </table>

    <h4>ℹ️ Paramètres de validation</h4>
    <table class="form-table">
        <tr>
            <th scope="row">Consommation minimum (kWh/an)</th>
            <td>
                <input type="number" 
                    name="htic_simulateur_gaz_professionnel_data[pro_conso_min]" 
                    value="<?php echo esc_attr($gaz_pro['pro_conso_min'] ?? 100); ?>" 
                    style="width: 100px;" />
            </td>
        </tr>
        <tr>
            <th scope="row">Consommation maximum (kWh/an)</th>
            <td>
                <input type="number" 
                    name="htic_simulateur_gaz_professionnel_data[pro_conso_max]" 
                    value="<?php echo esc_attr($gaz_pro['pro_conso_max'] ?? 1000000); ?>" 
                    style="width: 100px;" />
            </td>
        </tr>
    </table>
    
    <?php submit_button('💾 Sauvegarder les tarifs gaz professionnel'); ?>
</form>

<!-- JavaScript pour gestion communes pro -->
<script>
jQuery(document).ready(function($) {
    
    // Gestion modification type de gaz
    $(document).on('change', '#communes-list-pro .commune-type', function() {
        const commune = $(this).data('commune');
        const type = $(this).val();
        $(this).closest('tr').find('.commune-type-hidden').val(type);
    });
    
    // Compteur pour les nouvelles communes
    let communeProCounter = 0;
    
    // Ajouter une nouvelle commune
    $('#ajouter-commune-pro').on('click', function() {
        communeProCounter++;
        const newCommune = 'NOUVELLE_COMMUNE_PRO_' + communeProCounter;
        
        const newRow = `
        <tr data-commune="${newCommune}">
            <td>
                <input type="text" 
                       name="htic_simulateur_gaz_professionnel_data[pro_communes_gaz][${newCommune}]" 
                       value="" 
                       class="commune-nom" 
                       style="width: 100%;" 
                       placeholder="Nom de la commune" />
                <input type="hidden" 
                       name="htic_simulateur_gaz_professionnel_data[pro_communes_types][${newCommune}]" 
                       value="naturel" 
                       class="commune-type-hidden" />
            </td>
            <td>
                <select class="commune-type" style="width: 100%;" data-commune="${newCommune}">
                    <option value="naturel" selected>🔥 Gaz Naturel</option>
                    <option value="propane">⛽ Gaz Propane</option>
                </select>
            </td>
            <td>
                <button type="button" class="button button-secondary supprimer-commune" 
                        title="Supprimer cette commune">
                    ❌ Supprimer
                </button>
            </td>
        </tr>`;
        
        $('#communes-list-pro').append(newRow);
        
        // Focus sur le nouveau champ
        $('#communes-list-pro tr:last-child input.commune-nom').focus().select();
    });
    
    // Supprimer une commune
    $(document).on('click', '#communes-list-pro .supprimer-commune', function() {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette commune ?')) {
            $(this).closest('tr').remove();
        }
    });
    
    // Auto-majuscules pour les noms de communes
    $(document).on('input', '#communes-list-pro .commune-nom', function() {
        $(this).val($(this).val().toUpperCase());
    });
    
});
</script>