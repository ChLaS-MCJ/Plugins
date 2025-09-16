<?php
// ==========================================
// FICHIER: admin/admin-gaz-residentiel.php CORRIGÉ
// ==========================================
/**
 * Onglet Gaz Résidentiel - Interface d'administration CORRIGÉE
 * Conforme aux données Excel exactes
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

// Communes exactes de l'Excel avec bons types
$communes_gaz = isset($gaz_residentiel['communes_gaz']) ? $gaz_residentiel['communes_gaz'] : array(
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
    <?php settings_fields('htic_simulateur_gaz_residentiel'); ?>
    
    <h2>🔥 Tarifs Gaz Résidentiel (TTC) - Données Excel Exactes</h2>
    <p class="description">Configuration conforme au fichier Excel "Conso Gaz Résidentiel" - Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>
    
    <h4>🗺️ Gestion des Communes (19 communes Excel)</h4>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50%;">Commune</th>
                <th style="width: 30%;">Type de gaz</th>
                <th style="width: 20%;">Actions</th>
            </tr>
        </thead>
        <tbody id="communes-list">
            <?php foreach ($communes_gaz as $commune => $type_gaz): ?>
            <tr data-commune="<?php echo esc_attr($commune); ?>">
                <td>
                    <input type="text" 
                           name="htic_simulateur_gaz_residentiel_data[communes_gaz][<?php echo esc_attr($commune); ?>]" 
                           value="<?php echo esc_attr($commune); ?>" 
                           class="commune-nom" 
                           style="width: 100%;" />
                    <input type="hidden" 
                           name="htic_simulateur_gaz_residentiel_data[communes_types][<?php echo esc_attr($commune); ?>]" 
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
                    <button type="button" id="ajouter-commune" class="button button-primary">➕ Ajouter une commune</button>
                    <span class="description" style="margin-left: 15px;">
                        7 communes gaz naturel + 12 communes gaz propane selon Excel
                    </span>
                </td>
            </tr>
        </tfoot>
    </table>

    <h4>💰 Tarification Gaz Naturel (2 tranches GOM0/GOM1)</h4>
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
                        name="htic_simulateur_gaz_residentiel_data[gaz_naturel_gom0_abo]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_naturel_gom0_abo'] ?? 8.92); ?>" 
                        style="width: 100px;" />
                </td>
                <td>
                    <input type="number" step="0.0001" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_naturel_gom0_kwh]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_naturel_gom0_kwh'] ?? 0.1265); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Petits consommateurs</em></td>
            </tr>
            <tr style="background-color: #f0f8e8;">
                <td><strong>GOM1</strong></td>
                <td>≥ 4 000</td>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_naturel_gom1_abo]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_naturel_gom1_abo'] ?? 22.42); ?>" 
                        style="width: 100px;" />
                </td>
                <td>
                    <input type="number" step="0.0001" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_naturel_gom1_kwh]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_naturel_gom1_kwh'] ?? 0.0978); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Gros consommateurs</em></td>
            </tr>
        </tbody>
    </table>

    <h4>⛽ Tarification Gaz Propane (5 tranches P0 à P4)</h4>
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
                        name="htic_simulateur_gaz_residentiel_data[gaz_propane_p0_abo]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_propane_p0_abo'] ?? 4.64); ?>" 
                        style="width: 100px;" />
                </td>
                <td>
                    <input type="number" step="0.00001" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_propane_p0_kwh]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_propane_p0_kwh'] ?? 0.12479); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Très petits consommateurs</em></td>
            </tr>
            <tr>
                <td><strong>P1</strong></td>
                <td>1 000 - 5 999</td>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_propane_p1_abo]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_propane_p1_abo'] ?? 5.26); ?>" 
                        style="width: 100px;" />
                </td>
                <td>
                    <input type="number" step="0.00001" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_propane_p1_kwh]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_propane_p1_kwh'] ?? 0.11852); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Petits consommateurs</em></td>
            </tr>
            <tr style="background-color: #fff3cd;">
                <td><strong>P2 ⭐</strong></td>
                <td>6 000 - 29 999</td>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_propane_p2_abo]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_propane_p2_abo'] ?? 16.06); ?>" 
                        style="width: 100px;" />
                </td>
                <td>
                    <input type="number" step="0.00001" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_propane_p2_kwh]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_propane_p2_kwh'] ?? 0.11305); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Consommateurs Moyen</em></td>
            </tr>
            <tr>
                <td><strong>P3</strong></td>
                <td>30 000 - 349 999</td>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_propane_p3_abo]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_propane_p3_abo'] ?? 34.56); ?>" 
                        style="width: 100px;" />
                </td>
                <td>
                    <input type="number" step="0.00001" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_propane_p3_kwh]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_propane_p3_kwh'] ?? 0.10273); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Gros consommateurs</em></td>
            </tr>
            <tr>
                <td><strong>P4</strong></td>
                <td>≥ 350 000</td>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_propane_p4_abo]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_propane_p4_abo'] ?? 311.01); ?>" 
                        style="width: 100px;" />
                </td>
                <td>
                    <input type="number" step="0.00001" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_propane_p4_kwh]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_propane_p4_kwh'] ?? 0.10064); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Très gros consommateurs</em></td>
            </tr>
        </tbody>
    </table>

    <h4>⚡ Consommations par Usage (selon Excel K28/K29)</h4>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Usage</th>
                <th>Facteur (kWh/pers/an)</th>
                <th>Formule Excel</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>🍳 Cuisson</strong></td>
                <td>
                    <input type="number" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_cuisson_par_personne]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_cuisson_par_personne'] ?? 50); ?>" 
                        style="width: 100px;" />
                    <small>kWh/pers/an</small>
                </td>
                <td><code>nb_personnes × 50</code></td>
               
            </tr>
            <tr>
                <td><strong>🚿 Eau chaude</strong></td>
                <td>
                    <input type="number" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_eau_chaude_par_personne]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_eau_chaude_par_personne'] ?? 400); ?>" 
                        style="width: 100px;" />
                    <small>kWh/pers/an</small>
                </td>
                <td><code>nb_personnes × 400</code></td>
               
            </tr>
        </tbody>
    </table>

    <h4>🏠 Chauffage selon Isolation (niveaux Excel G28:H31)</h4>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Niveau Excel</th>
                <th>Description utilisateur</th>
                <th>Consommation (kWh/m²/an)</th>
                <th>Période indicative</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background-color: #fee;">
                <td><strong>Niveau 1</strong></td>
                <td>Très mal isolé</td>
                <td>
                    <input type="number" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_chauffage_niveau_1]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_chauffage_niveau_1'] ?? 160); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Avant 1980</em></td>
            </tr>
            <tr style="background-color: #fff3cd;">
                <td><strong>Niveau 2</strong></td>
                <td>Mal isolé</td>
                <td>
                    <input type="number" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_chauffage_niveau_2]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_chauffage_niveau_2'] ?? 70); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>1980 à 2000</em></td>
            </tr>
            <tr style="background-color: #d1ecf1;">
                <td><strong>Niveau 3 ⭐</strong></td>
                <td>Bien isolé</td>
                <td>
                    <input type="number" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_chauffage_niveau_3]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_chauffage_niveau_3'] ?? 110); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Après 2000</em></td>
            </tr>
            <tr style="background-color: #d4edda;">
                <td><strong>Niveau 4</strong></td>
                <td>Très bien isolé</td>
                <td>
                    <input type="number" 
                        name="htic_simulateur_gaz_residentiel_data[gaz_chauffage_niveau_4]" 
                        value="<?php echo esc_attr($gaz_residentiel['gaz_chauffage_niveau_4'] ?? 20); ?>" 
                        style="width: 100px;" />
                </td>
                <td><em>Rénovation récente</em></td>
            </tr>
        </tbody>
    </table>

    <h4>🏠 Coefficients par Type de Logement</h4>
    <table class="form-table">
        <tr>
            <th scope="row">Coefficient Maison</th>
            <td>
                <input type="number" step="0.01" 
                    name="htic_simulateur_gaz_residentiel_data[coefficient_maison]" 
                    value="<?php echo esc_attr($gaz_residentiel['coefficient_maison'] ?? 1.0); ?>" 
                    style="width: 100px;" />
                <span class="description">Multiplicateur pour les maisons individuelles</span>
            </td>
        </tr>
        <tr>
            <th scope="row">Coefficient Appartement</th>
            <td>
                <input type="number" step="0.01" 
                    name="htic_simulateur_gaz_residentiel_data[coefficient_appartement]" 
                    value="<?php echo esc_attr($gaz_residentiel['coefficient_appartement'] ?? 0.8); ?>" 
                    style="width: 100px;" />
                <span class="description">Multiplicateur pour les appartements</span>
            </td>
        </tr>
    </table>

    <h4>🧮 Seuils de Tranches (Excel)</h4>
    <table class="form-table">
        <tr>
            <th scope="row">Seuil GOM0/GOM1 (kWh/an)</th>
            <td>
                <input type="number" 
                    name="htic_simulateur_gaz_residentiel_data[seuil_gom_naturel]" 
                    value="<?php echo esc_attr($gaz_residentiel['seuil_gom_naturel'] ?? 4000); ?>" 
                    style="width: 100px;" />
            </td>
        </tr>
    </table>

    <h4>ℹ️ Paramètres Divers</h4>
    <table class="form-table">
        <tr>
            <th scope="row">Nombre de personnes minimum</th>
            <td>
                <input type="number" 
                    name="htic_simulateur_gaz_residentiel_data[nb_personnes_min]" 
                    value="<?php echo esc_attr($gaz_residentiel['nb_personnes_min'] ?? 1); ?>" 
                    style="width: 100px;" />
            </td>
        </tr>
    </table>
    
    <?php submit_button('💾 Sauvegarder les tarifs gaz résidentiel'); ?>
</form>

<!-- JavaScript pour gestion communes -->
<script>
jQuery(document).ready(function($) {
    
    // Gestion modification type de gaz
    $(document).on('change', '.commune-type', function() {
        const commune = $(this).data('commune');
        const type = $(this).val();
        $(`input.commune-type-hidden[name*="[${commune}]"]`).val(type);
    });
    
    // Compteur pour les nouvelles communes
    let communeCounter = 0;
    
    // Ajouter une nouvelle commune
    $('#ajouter-commune').on('click', function() {
        communeCounter++;
        const newCommune = 'NOUVELLE_COMMUNE_' + communeCounter;
        
        const newRow = `
        <tr data-commune="${newCommune}">
            <td>
                <input type="text" 
                       name="htic_simulateur_gaz_residentiel_data[communes_gaz][${newCommune}][nom]" 
                       value="${newCommune}" 
                       class="commune-nom" 
                       style="width: 100%;" 
                       placeholder="Nom de la commune" />
            </td>
            <td>
                <select name="htic_simulateur_gaz_residentiel_data[communes_gaz][${newCommune}][type]" 
                        class="commune-type" 
                        style="width: 100%;">
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
        
        $('#communes-list').append(newRow);
        
        // Focus sur le nouveau champ
        $('#communes-list tr:last-child input.commune-nom').focus().select();
    });
    
    // Supprimer une commune
    $(document).on('click', '.supprimer-commune', function() {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette commune ?')) {
            $(this).closest('tr').remove();
        }
    });
    
    // Mettre à jour le nom de l'attribut name quand on modifie le nom de la commune
    $(document).on('blur', '.commune-nom', function() {
        const $row = $(this).closest('tr');
        const oldCommune = $row.data('commune');
        const newCommune = $(this).val().toUpperCase().replace(/[^A-Z0-9\s\-']/g, '');
        
        if (newCommune && newCommune !== oldCommune) {
            // Mettre à jour l'attribut data
            $row.data('commune', newCommune);
            
            // Mettre à jour les noms des champs
            $row.find('input.commune-nom').attr('name', 
                `htic_simulateur_gaz_residentiel_data[communes_gaz][${newCommune}][nom]`);
            $row.find('select.commune-type').attr('name', 
                `htic_simulateur_gaz_residentiel_data[communes_gaz][${newCommune}][type]`);
            
            // Mettre à jour la valeur affichée
            $(this).val(newCommune);
        }
    });
    
    // Auto-majuscules pour les noms de communes
    $(document).on('input', '.commune-nom', function() {
        $(this).val($(this).val().toUpperCase());
    });
    
});
</script>
