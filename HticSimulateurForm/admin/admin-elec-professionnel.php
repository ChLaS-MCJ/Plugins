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
        
        <h4>Tableau 1 : TRV PRO (en HT)</h4>
        <table class="form-table">
            <tr>
                <th scope="row">Tarifs Réglementés de Vente pour professionnels éligibles </th>
                <td>BT <
                    <input type="number" 
                        name="htic_simulateur_elec_professionnel_data[pro_trv_max_kva]" 
                        value="<?php echo esc_attr($elec_professionnel['pro_trv_max_kva'] ?? 36); ?>"
                        style="width: 60px;"> kVA
                </td>
            </tr>
        </table>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th rowspan="2">PS (kVA)</th>
                    <th colspan="2" style="text-align:center; background:#e8f4f8;">BASE</th>
                    <th colspan="3" style="text-align:center; background:#f0f8e8;">Heures Creuses</th>
                </tr>
                <tr>
                    <th style="background:#e8f4f8;">Abo (€/mois)</th>
                    <th style="background:#e8f4f8;">kWh (€)</th>
                    <th style="background:#f0f8e8;">Abo (€/mois)</th>
                    <th style="background:#f0f8e8;">HP (€/kWh)</th>
                    <th style="background:#f0f8e8;">HC (€/kWh)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $puissances_trv = [3, 6, 9, 12, 15, 18, 24, 30, 36];
                foreach ($puissances_trv as $p): 
                ?>
                <tr>
                    <td><strong><?php echo $p; ?></strong></td>
                    <td>
                        <input type="number" 
                               step="0.01" 
                               name="htic_simulateur_elec_professionnel_data[pro_trv_base_abo_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_trv_base_abo_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_trv_base_kwh_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_trv_base_kwh_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.01" 
                               name="htic_simulateur_elec_professionnel_data[pro_trv_hc_abo_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_trv_hc_abo_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_trv_hc_hp_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_trv_hc_hp_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_trv_hc_hc_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_trv_hc_hc_'.$p] ?? ''); ?>" />
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h4>Tableau 2 : Tempo PRO (en HT)</h4>
        <table class="form-table">
            <tr>
                <th scope="row">Jours rouges</th>
                <td>
                    <input type="number" 
                        name="htic_simulateur_elec_professionnel_data[pro_tempo_jours_rouges]" 
                        value="<?php echo esc_attr($elec_professionnel['pro_tempo_jours_rouges'] ?? 22); ?>"
                        style="width: 100px;"> jours
                </td>
            </tr>
            <tr>
                <th scope="row">Jours blancs</th>
                <td>
                    <input type="number" 
                        name="htic_simulateur_elec_professionnel_data[pro_tempo_jours_blancs]" 
                        value="<?php echo esc_attr($elec_professionnel['pro_tempo_jours_blancs'] ?? 43); ?>"
                        style="width: 100px;"> jours
                </td>
            </tr>
            <tr>
                <th scope="row">Jours bleus</th>
                <td>
                    <input type="number" 
                        name="htic_simulateur_elec_professionnel_data[pro_tempo_jours_bleus]" 
                        value="<?php echo esc_attr($elec_professionnel['pro_tempo_jours_bleus'] ?? 300); ?>"
                        style="width: 100px;"> jours
                </td>
            </tr>
            <tr>
                <th scope="row">Disponible à partir de</th>
                <td>
                    <input type="number" 
                        name="htic_simulateur_elec_professionnel_data[pro_tempo_min_kva]" 
                        value="<?php echo esc_attr($elec_professionnel['pro_tempo_min_kva'] ?? 9); ?>"
                        style="width: 100px;"> kVA
                </td>
            </tr>
        </table>
   
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th rowspan="2">PS (kVA)</th>
                    <th rowspan="2">Abo (€/mois)</th>
                    <th colspan="2" style="text-align:center; background:#ffe8e8;">Jours Rouges</th>
                    <th colspan="2" style="text-align:center; background:#fff8e8;">Jours Blancs</th>
                    <th colspan="2" style="text-align:center; background:#e8f0ff;">Jours Bleus</th>
                </tr>
                <tr>
                    <th style="background:#ffe8e8;">HP (€/kWh)</th>
                    <th style="background:#ffe8e8;">HC (€/kWh)</th>
                    <th style="background:#fff8e8;">HP (€/kWh)</th>
                    <th style="background:#fff8e8;">HC (€/kWh)</th>
                    <th style="background:#e8f0ff;">HP (€/kWh)</th>
                    <th style="background:#e8f0ff;">HC (€/kWh)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $puissances_tempo = [9, 12, 15, 18, 24, 30, 36];
                foreach ($puissances_tempo as $p): 
                ?>
                <tr>
                    <td><strong><?php echo $p; ?></strong></td>
                    <td>
                        <input type="number" 
                               step="0.01" 
                               name="htic_simulateur_elec_professionnel_data[pro_tempo_abo_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_tempo_abo_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_tempo_rouge_hp_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_tempo_rouge_hp_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_tempo_rouge_hc_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_tempo_rouge_hc_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_tempo_blanc_hp_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_tempo_blanc_hp_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_tempo_blanc_hc_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_tempo_blanc_hc_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_tempo_bleu_hp_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_tempo_bleu_hp_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_tempo_bleu_hc_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_tempo_bleu_hc_'.$p] ?? ''); ?>" />
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h4>Tableau 3 : 
            <input type="text" 
                   name="htic_simulateur_elec_professionnel_data[pro_nom_offre_francaise]" 
                   value="<?php echo esc_attr($elec_professionnel['pro_nom_offre_francaise'] ?? 'Offre 100% française'); ?>"
                   style="width: 300px; display: inline;">
            (en HT)
        </h4>
        <table class="form-table">
            <tr>
                <th scope="row">Tarifs pour l'offre verte/française </th>
                <td>généralement + 
                    <input type="number" 
                        name="htic_simulateur_elec_professionnel_data[pro_offre_fr_majoration]" 
                        value="<?php echo esc_attr($elec_professionnel['pro_offre_fr_majoration'] ?? 5); ?>"
                        style="width: 60px;"> % sur TRV
                </td>
            </tr>
        </table>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th rowspan="2">PS (kVA)</th>
                    <th colspan="2" style="text-align:center; background:#e8f8e8;">BASE</th>
                    <th colspan="3" style="text-align:center; background:#f0f8f0;">Heures Creuses</th>
                </tr>
                <tr>
                    <th style="background:#e8f8e8;">Abo (€/mois)</th>
                    <th style="background:#e8f8e8;">kWh (€)</th>
                    <th style="background:#f0f8f0;">Abo (€/mois)</th>
                    <th style="background:#f0f8f0;">HP (€/kWh)</th>
                    <th style="background:#f0f8f0;">HC (€/kWh)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($puissances_trv as $p): 
                ?>
                <tr>
                    <td><strong><?php echo $p; ?></strong></td>
                    <td>
                        <input type="number" 
                               step="0.01" 
                               name="htic_simulateur_elec_professionnel_data[pro_offre_fr_base_abo_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_offre_fr_base_abo_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_offre_fr_base_kwh_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_offre_fr_base_kwh_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.01" 
                               name="htic_simulateur_elec_professionnel_data[pro_offre_fr_hc_abo_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_offre_fr_hc_abo_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_offre_fr_hc_hp_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_offre_fr_hc_hp_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_offre_fr_hc_hc_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_offre_fr_hc_hc_'.$p] ?? ''); ?>" />
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h4>Tableau 4 : 
            <input type="text" 
                   name="htic_simulateur_elec_professionnel_data[pro_nom_autre_offre]" 
                   value="<?php echo esc_attr($elec_professionnel['pro_nom_autre_offre'] ?? 'Autre offre'); ?>"
                   style="width: 300px; display: inline;">
            (en HT)
        </h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th rowspan="2">PS (kVA)</th>
                    <th colspan="2" style="text-align:center; background:#f8f8e8;">BASE</th>
                    <th colspan="3" style="text-align:center; background:#f8f0f8;">Heures Creuses</th>
                </tr>
                <tr>
                    <th style="background:#f8f8e8;">Abo (€/mois)</th>
                    <th style="background:#f8f8e8;">kWh (€)</th>
                    <th style="background:#f8f0f8;">Abo (€/mois)</th>
                    <th style="background:#f8f0f8;">HP (€/kWh)</th>
                    <th style="background:#f8f0f8;">HC (€/kWh)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($puissances_trv as $p): 
                ?>
                <tr>
                    <td><strong><?php echo $p; ?></strong></td>
                    <td>
                        <input type="number" 
                               step="0.01" 
                               name="htic_simulateur_elec_professionnel_data[pro_autre_offre_base_abo_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_autre_offre_base_abo_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_autre_offre_base_kwh_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_autre_offre_base_kwh_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.01" 
                               name="htic_simulateur_elec_professionnel_data[pro_autre_offre_hc_abo_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_autre_offre_hc_abo_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_autre_offre_hc_hp_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_autre_offre_hc_hp_'.$p] ?? ''); ?>" />
                    </td>
                    <td>
                        <input type="number" 
                               step="0.0001" 
                               name="htic_simulateur_elec_professionnel_data[pro_autre_offre_hc_hc_<?php echo $p; ?>]"
                               value="<?php echo esc_attr($elec_professionnel['pro_autre_offre_hc_hc_'.$p] ?? ''); ?>" />
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h4>Paramètres d'éligibilité et calculs</h4>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label>Seuil nombre de salariés pour TRV</label>
                </th>
                <td>
                    <input type="number" 
                           name="htic_simulateur_elec_professionnel_data[pro_seuil_salaries]"
                           value="<?php echo esc_attr($elec_professionnel['pro_seuil_salaries'] ?? 10); ?>"
                           style="width: 80px;">
                    <p class="description">Maximum de salariés pour être éligible au TRV</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label>Seuil CA pour TRV (€)</label>
                </th>
                <td>
                    <input type="number" 
                           name="htic_simulateur_elec_professionnel_data[pro_seuil_ca]"
                           value="<?php echo esc_attr($elec_professionnel['pro_seuil_ca'] ?? 3000000); ?>"
                           style="width: 150px;">
                    <p class="description">Chiffre d'affaires maximum pour être éligible au TRV</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label>Ratio HP/HC par défaut</label>
                </th>
                <td>
                    <input type="number" 
                           min="0" 
                           max="100"
                           name="htic_simulateur_elec_professionnel_data[pro_ratio_hp_defaut]"
                           value="<?php echo esc_attr($elec_professionnel['pro_ratio_hp_defaut'] ?? 60); ?>"
                           style="width: 80px;"> % en Heures Pleines
                    <p class="description">Répartition par défaut pour l'option Heures Creuses</p>
                </td>
            </tr>
        </table>
        
        <h4>Taxes et contributions</h4>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label>CSPE (€/MWh)</label>
                </th>
                <td>
                    <input type="number" 
                           step="0.1" 
                           name="htic_simulateur_elec_professionnel_data[pro_cspe]"
                           value="<?php echo esc_attr($elec_professionnel['pro_cspe'] ?? 22.5); ?>"
                           style="width: 80px;">
                    <p class="description">Contribution au Service Public de l'Électricité</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label>TCFE (€/MWh)</label>
                </th>
                <td>
                    <input type="number" 
                           step="0.1" 
                           name="htic_simulateur_elec_professionnel_data[pro_tcfe]"
                           value="<?php echo esc_attr($elec_professionnel['pro_tcfe'] ?? 9.5); ?>"
                           style="width: 80px;">
                    <p class="description">Taxe sur la Consommation Finale d'Électricité (varie selon commune)</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label>CTA (%)</label>
                </th>
                <td>
                    <input type="number" 
                           step="0.01" 
                           name="htic_simulateur_elec_professionnel_data[pro_cta]"
                           value="<?php echo esc_attr($elec_professionnel['pro_cta'] ?? 2.71); ?>"
                           style="width: 80px;"> %
                    <p class="description">Contribution Tarifaire d'Acheminement</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label>TVA (%)</label>
                </th>
                <td>
                    <input type="number" 
                           step="0.1" 
                           name="htic_simulateur_elec_professionnel_data[pro_tva]"
                           value="<?php echo esc_attr($elec_professionnel['pro_tva'] ?? 20); ?>"
                           style="width: 80px;"> %
                </td>
            </tr>
        </table>
        
    </div>
    
    <?php submit_button('💾 Sauvegarder les tarifs électricité professionnel'); ?>
</form>