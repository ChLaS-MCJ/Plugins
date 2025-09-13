<?php
// ==========================================
// FICHIER 1: admin/admin-elec-residentiel.php
// ==========================================
/**
 * Onglet Électricité Résidentiel - Interface d'administration
 * Fichier: admin/admin-elec-residentiel.php
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les données existantes
$elec_residentiel = get_option('htic_simulateur_elec_residentiel_data', array());

// Si les données sont vides, utiliser les valeurs par défaut
if (empty($elec_residentiel)) {
    $plugin_instance = new HticSimulateurEnergieAdmin();
    $elec_residentiel = $plugin_instance->get_default_elec_residentiel();
}
?>

<form method="post" action="options.php" class="htic-simulateur-form">
    <?php settings_fields('htic_simulateur_elec_residentiel'); ?>
    
    <h2>⚡ Tarifs Électricité Résidentiel (TTC)</h2>
    <p class="description">Configuration des tarifs et consommations pour les particuliers - Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>
    
    <div class="htic-simulateur-section">
        <h3>Configuration Générale</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Puissance recommandée par défaut</th>
                <td>
                    <input type="number" name="htic_simulateur_elec_residentiel_data[puissance_defaut]" 
                            value="<?php echo esc_attr($elec_residentiel['puissance_defaut'] ?? 15); ?>" /> KVA
                </td>
            </tr>
        </table>
        
        <h4>Tarifs BASE</h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Puissance (KVA)</th>
                    <th>Abonnement (€/mois)</th>
                    <th>Prix kWh (€)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $puissances = [3, 6, 9, 12, 15, 18, 24, 30, 36];
                $tarifs_base = [
                    3 => ['abo' => 9.69, 'kwh' => 0.2516],
                    6 => ['abo' => 12.67, 'kwh' => 0.2516],
                    9 => ['abo' => 15.89, 'kwh' => 0.2516],
                    12 => ['abo' => 19.16, 'kwh' => 0.2516],
                    15 => ['abo' => 22.21, 'kwh' => 0.2516],
                    18 => ['abo' => 25.24, 'kwh' => 0.2516],
                    24 => ['abo' => 31.96, 'kwh' => 0.2516],
                    30 => ['abo' => 37.68, 'kwh' => 0.2516],
                    36 => ['abo' => 44.43, 'kwh' => 0.2516]
                ];
                foreach($puissances as $p): 
                ?>
                <tr>
                    <td><?php echo $p; ?></td>
                    <td>
                        <input type="number" step="0.01" 
                                name="htic_simulateur_elec_residentiel_data[base_abo_<?php echo $p; ?>]" 
                                value="<?php echo esc_attr($elec_residentiel['base_abo_'.$p] ?? $tarifs_base[$p]['abo']); ?>" />
                    </td>
                    <td>
                        <input type="number" step="0.0001" 
                                name="htic_simulateur_elec_residentiel_data[base_kwh_<?php echo $p; ?>]" 
                                value="<?php echo esc_attr($elec_residentiel['base_kwh_'.$p] ?? $tarifs_base[$p]['kwh']); ?>" />
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4>Tarifs Heures Creuses</h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Puissance (KVA)</th>
                    <th>Abonnement (€/mois)</th>
                    <th>HP (€/kWh)</th>
                    <th>HC (€/kWh)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $tarifs_heurescreuses = [
                    3 => ['abo' => 0.0, 'hp' => 0.27, 'hc' => 0.2068],
                    6 => ['abo' => 13.28, 'hp' => 0.27, 'hc' => 0.2068],
                    9 => ['abo' => 16.82, 'hp' => 0.27, 'hc' => 0.2068],
                    12 => ['abo' => 20.28, 'hp' => 0.27, 'hc' => 0.2068],
                    15 => ['abo' => 23.57, 'hp' => 0.27, 'hc' => 0.2068],
                    18 => ['abo' => 26.84, 'hp' => 0.27, 'hc' => 0.2068],
                    24 => ['abo' => 33.7, 'hp' => 0.27, 'hc' => 0.2068],
                    30 => ['abo' => 39.94, 'hp' => 0.27, 'hc' => 0.2068],
                    36 => ['abo' => 46.24, 'hp' => 0.27, 'hc' => 0.2068]
                ];
                foreach($puissances as $p): 
                ?>
                <tr>
                    <td><?php echo $p; ?></td>
                    <td>
                        <input type="number" step="0.01" 
                                name="htic_simulateur_elec_residentiel_data[hc_abo_<?php echo $p; ?>]" 
                                value="<?php echo esc_attr($elec_residentiel['hc_abo_'.$p] ?? $tarifs_heurescreuses[$p]['abo']); ?>" />
                    </td>
                    <td>
                        <input type="number" step="0.0001" 
                                name="htic_simulateur_elec_residentiel_data[hc_hp_<?php echo $p; ?>]" 
                                value="<?php echo esc_attr($elec_residentiel['hc_hp_'.$p] ?? $tarifs_heurescreuses[$p]['hp']); ?>" />
                    </td>
                    <td>
                        <input type="number" step="0.0001" 
                                name="htic_simulateur_elec_residentiel_data[hc_hc_<?php echo $p; ?>]" 
                                value="<?php echo esc_attr($elec_residentiel['hc_hc_'.$p] ?? $tarifs_heurescreuses[$p]['hc']); ?>" />
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Répartition HC -->
        <table class="form-table">
            <tr>
                <th scope="row">Répartition Heures Creuses</th>
                <td>
                    <label>HP : 
                        <input type="number" name="htic_simulateur_elec_residentiel_data[repartition_hp]" 
                                value="<?php echo esc_attr($elec_residentiel['repartition_hp'] ?? 60); ?>" 
                                style="width: 75px;" min="0" max="100" /> %
                    </label>
                    <label>HC : 
                        <input type="number" name="htic_simulateur_elec_residentiel_data[repartition_hc]" 
                                value="<?php echo esc_attr($elec_residentiel['repartition_hc'] ?? 40); ?>" 
                                style="width: 75px;" min="0" max="100" /> %
                    </label><br>
                </td>
            </tr>
        </table>
        
        <h4>Tarifs TEMPO</h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Puissance (KVA)</th>
                    <th>Abonnement (€/mois)</th>
                    <th>Rouge HP (€)</th>
                    <th>Rouge HC (€)</th>
                    <th>Blanc HP (€)</th>
                    <th>Blanc HC (€)</th>
                    <th>Bleu HP (€)</th>
                    <th>Bleu HC (€)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $tarifs_tempo = [
                    9 => ['abo' => 13.23, 'rouge_hp' => 0.7562, 'rouge_hc' => 0.1568, 'blanc_hp' => 0.1894, 'blanc_hc' => 0.1486, 'bleu_hp' => 0.1609, 'bleu_hc' => 0.1296],
                    12 => ['abo' => 16.55, 'rouge_hp' => 0.7562, 'rouge_hc' => 0.1568, 'blanc_hp' => 0.1894, 'blanc_hc' => 0.1486, 'bleu_hp' => 0.1609, 'bleu_hc' => 0.1296],
                    15 => ['abo' => 23.08, 'rouge_hp' => 0.7562, 'rouge_hc' => 0.1568, 'blanc_hp' => 0.1894, 'blanc_hc' => 0.1486, 'bleu_hp' => 0.1609, 'bleu_hc' => 0.1296],
                    18 => ['abo' => 26.18, 'rouge_hp' => 0.7562, 'rouge_hc' => 0.1568, 'blanc_hp' => 0.1894, 'blanc_hc' => 0.1486, 'bleu_hp' => 0.1609, 'bleu_hc' => 0.1296],
                    24 => ['abo' => 38.22, 'rouge_hp' => 0.7562, 'rouge_hc' => 0.1568, 'blanc_hp' => 0.1894, 'blanc_hc' => 0.1486, 'bleu_hp' => 0.1609, 'bleu_hc' => 0.1296],
                    30 => ['abo' => 39.5, 'rouge_hp' => 0.7562, 'rouge_hc' => 0.1568, 'blanc_hp' => 0.1894, 'blanc_hc' => 0.1486, 'bleu_hp' => 0.1609, 'bleu_hc' => 0.1296],
                    36 => ['abo' => 45.87, 'rouge_hp' => 0.7562, 'rouge_hc' => 0.1568, 'blanc_hp' => 0.1894, 'blanc_hc' => 0.1486, 'bleu_hp' => 0.1609, 'bleu_hc' => 0.1296]
                ];
                foreach($puissances as $p): 
                ?>
                <tr>
                    <td><?php echo $p; ?></td>
                    <td>
                        <input type="number" step="0.01" 
                                name="htic_simulateur_elec_residentiel_data[tempo_abo_<?php echo $p; ?>]" 
                                value="<?php echo esc_attr($elec_residentiel['tempo_abo_'.$p] ?? $tarifs_tempo[$p]['abo']); ?>" />
                    </td>
                    <td>
                        <input type="number" step="0.0001" 
                                name="htic_simulateur_elec_residentiel_data[tempo_rouge_hp_<?php echo $p; ?>]" 
                                value="<?php echo esc_attr($elec_residentiel['tempo_rouge_hp_'.$p] ?? $tarifs_tempo[$p]['rouge_hp']); ?>" />
                    </td>
                    <td>
                        <input type="number" step="0.0001" 
                                name="htic_simulateur_elec_residentiel_data[tempo_rouge_hc_<?php echo $p; ?>]" 
                                value="<?php echo esc_attr($elec_residentiel['tempo_rouge_hc_'.$p] ?? $tarifs_tempo[$p]['rouge_hc']); ?>" />
                    </td>
                    <td>
                        <input type="number" step="0.0001" 
                                name="htic_simulateur_elec_residentiel_data[tempo_blanc_hp_<?php echo $p; ?>]" 
                                value="<?php echo esc_attr($elec_residentiel['tempo_blanc_hp_'.$p] ?? $tarifs_tempo[$p]['blanc_hp']); ?>" />
                    </td>
                    <td>
                        <input type="number" step="0.0001" 
                                name="htic_simulateur_elec_residentiel_data[tempo_blanc_hc_<?php echo $p; ?>]" 
                                value="<?php echo esc_attr($elec_residentiel['tempo_blanc_hc_'.$p] ?? $tarifs_tempo[$p]['blanc_hc']); ?>" />
                    </td>
                    <td>
                        <input type="number" step="0.0001" 
                                name="htic_simulateur_elec_residentiel_data[tempo_bleu_hp_<?php echo $p; ?>]" 
                                value="<?php echo esc_attr($elec_residentiel['tempo_bleu_hp_'.$p] ?? $tarifs_tempo[$p]['bleu_hp']); ?>" />
                    </td>
                    <td>
                        <input type="number" step="0.0001" 
                                name="htic_simulateur_elec_residentiel_data[tempo_bleu_hc_<?php echo $p; ?>]" 
                                value="<?php echo esc_attr($elec_residentiel['tempo_bleu_hc_'.$p] ?? $tarifs_tempo[$p]['bleu_hc']); ?>" />
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4>Configuration Tempo - Nombre de jours</h4>
        <table class="wp-list-table">
            <tr>
                <td>Jours Bleus:</td>
                <td><input type="number" name="htic_simulateur_elec_residentiel_data[tempo_jours_bleus]" 
                        value="<?php echo esc_attr($elec_residentiel['tempo_jours_bleus'] ?? 300); ?>" /></td>
            </tr>
            <tr>
                <td>Jours Blancs:</td>
                <td><input type="number" name="htic_simulateur_elec_residentiel_data[tempo_jours_blancs]" 
                        value="<?php echo esc_attr($elec_residentiel['tempo_jours_blancs'] ?? 43); ?>" /></td>
            </tr>
            <tr>
                <td>Jours Rouges:</td>
                <td><input type="number" name="htic_simulateur_elec_residentiel_data[tempo_jours_rouges]" 
                        value="<?php echo esc_attr($elec_residentiel['tempo_jours_rouges'] ?? 22); ?>" /></td>
            </tr>
        </table>

        <!-- Répartition TEMPO -->
        <table class="form-table">
            <tr>
                <th scope="row">Répartition TEMPO (%)</th>
                <td>
                    <strong>Jours BLEUS :</strong><br>
                    <label>HP Bleu : 
                        <input type="number" name="htic_simulateur_elec_residentiel_data[tempo_bleu_hp_pct]" 
                                value="<?php echo esc_attr($elec_residentiel['tempo_bleu_hp_pct'] ?? 50); ?>" 
                                style="width: 75px;" min="0" max="100" />%
                    </label>
                    <label>HC Bleu : 
                        <input type="number" name="htic_simulateur_elec_residentiel_data[tempo_bleu_hc_pct]" 
                                value="<?php echo esc_attr($elec_residentiel['tempo_bleu_hc_pct'] ?? 25); ?>" 
                                style="width: 75px;" min="0" max="100" />%
                    </label><br><br>
                    
                    <strong>Jours BLANCS :</strong><br>
                    <label>HP Blanc : 
                        <input type="number" name="htic_simulateur_elec_residentiel_data[tempo_blanc_hp_pct]" 
                                value="<?php echo esc_attr($elec_residentiel['tempo_blanc_hp_pct'] ?? 10); ?>" 
                                style="width: 75px;" min="0" max="100" />%
                    </label>
                    <label>HC Blanc : 
                        <input type="number" name="htic_simulateur_elec_residentiel_data[tempo_blanc_hc_pct]" 
                                value="<?php echo esc_attr($elec_residentiel['tempo_blanc_hc_pct'] ?? 5); ?>" 
                                style="width: 75px;" min="0" max="100" />%
                    </label><br><br>
                    
                    <strong>Jours ROUGES :</strong><br>
                    <label>HP Rouge : 
                        <input type="number" name="htic_simulateur_elec_residentiel_data[tempo_rouge_hp_pct]" 
                                value="<?php echo esc_attr($elec_residentiel['tempo_rouge_hp_pct'] ?? 5); ?>" 
                                style="width: 75px;" min="0" max="100" />%
                    </label>
                    <label>HC Rouge : 
                        <input type="number" name="htic_simulateur_elec_residentiel_data[tempo_rouge_hc_pct]" 
                                value="<?php echo esc_attr($elec_residentiel['tempo_rouge_hc_pct'] ?? 5); ?>" 
                                style="width: 75px;" min="0" max="100" />%
                    </label>
                </td>
            </tr>
        </table>

        <h4>Équipements et consommations</h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 40%;">Équipement</th>
                    <th style="width: 20%;">Consommation (kWh/an)</th>
                    <th style="width: 20%;">Puissance équipement (W)</th>
                    <th style="width: 20%;">Taux simultanéité (%)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Chauffe eau</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[chauffe_eau]" 
                                value="<?php echo esc_attr($elec_residentiel['chauffe_eau'] ?? 900); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[chauffe_eau_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['chauffe_eau_puissance'] ?? 2400); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[chauffe_eau_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['chauffe_eau_simultaneite'] ?? 30); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Lave linge</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[lave_linge]" 
                                value="<?php echo esc_attr($elec_residentiel['lave_linge'] ?? 100); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[lave_linge_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['lave_linge_puissance'] ?? 2000); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[lave_linge_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['lave_linge_simultaneite'] ?? 50); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Four</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[four]" 
                                value="<?php echo esc_attr($elec_residentiel['four'] ?? 125); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[four_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['four_puissance'] ?? 2000); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[four_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['four_simultaneite'] ?? 50); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Seche linge</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[seche_linge]" 
                                value="<?php echo esc_attr($elec_residentiel['seche_linge'] ?? 175); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[seche_linge_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['seche_linge_puissance'] ?? 2500); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[seche_linge_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['seche_linge_simultaneite'] ?? 50); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Lave vaisselle</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[lave_vaisselle]" 
                                value="<?php echo esc_attr($elec_residentiel['lave_vaisselle'] ?? 100); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[lave_vaisselle_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['lave_vaisselle_puissance'] ?? 1800); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[lave_vaisselle_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['lave_vaisselle_simultaneite'] ?? 50); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Cave à vin</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[cave_a_vin]" 
                                value="<?php echo esc_attr($elec_residentiel['cave_a_vin'] ?? 150); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[cave_a_vin_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['cave_a_vin_puissance'] ?? 1000); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[cave_vin_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['cave_a_vin_simultaneite'] ?? 50); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Refrigérateur</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[refrigerateur]" 
                                value="<?php echo esc_attr($elec_residentiel['refrigerateur'] ?? 125); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[refrigerateur_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['refrigerateur_puissance'] ?? 150); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[refrigerateur_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['refrigerateur_simultaneite'] ?? 80); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Congélateur</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[congelateur]" 
                                value="<?php echo esc_attr($elec_residentiel['congelateur'] ?? 125); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[congelateur_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['congelateur_puissance'] ?? 200); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[congelateur_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['congelateur_simultaneite'] ?? 80); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Plaque induction</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[plaque_induction]" 
                                value="<?php echo esc_attr($elec_residentiel['plaque_induction'] ?? 180); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[plaque_induction_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['plaque_induction_puissance'] ?? 3500); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[plaque_induction_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['plaque_induction_simultaneite'] ?? 30); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Plaque vitrocéramique</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[plaque_vitroceramique]" 
                                value="<?php echo esc_attr($elec_residentiel['plaque_vitroceramique'] ?? 250); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[plaque_vitroceramique_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['plaque_vitroceramique_puissance'] ?? 3000); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[plaque_vitroceramique_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['plaque_vitroceramique_simultaneite'] ?? 30); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>TV/PC/Box</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[tv_pc_box]" 
                                value="<?php echo esc_attr($elec_residentiel['tv_pc_box'] ?? 300); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[tv_pc_box_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['tv_pc_box_puissance'] ?? 500); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[tv_pc_box_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['tv_pc_box_simultaneite'] ?? 80); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Piscine</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[piscine]" 
                                value="<?php echo esc_attr($elec_residentiel['piscine'] ?? 1400); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[piscine_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['piscine_puissance'] ?? 2500); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[piscine_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['piscine_simultaneite'] ?? 80); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Piscine chauffée</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[piscine_chauffee]" 
                                value="<?php echo esc_attr($elec_residentiel['piscine_chauffee'] ?? 4000); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>-</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>Spa/Jacuzzi</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[spa_jacuzzi]" 
                                value="<?php echo esc_attr($elec_residentiel['spa_jacuzzi'] ?? 2000); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[spa_jacuzzi_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['spa_jacuzzi_puissance'] ?? 2000); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[spa_jacuzzi_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['spa_jacuzzi_simultaneite'] ?? 50); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Aquarium</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[aquarium]" 
                                value="<?php echo esc_attr($elec_residentiel['aquarium'] ?? 240); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[aquarium_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['aquarium_puissance'] ?? 100); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[aquarium_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['aquarium_simultaneite'] ?? 80); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Voiture Electrique</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[voiture_electrique]" 
                                value="<?php echo esc_attr($elec_residentiel['voiture_electrique'] ?? 1500); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[voiture_electrique_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['voiture_electrique_puissance'] ?? 7000); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[voiture_electrique_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['voiture_electrique_simultaneite'] ?? 30); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Climatiseur mobile</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[climatiseur_mobile]" 
                                value="<?php echo esc_attr($elec_residentiel['climatiseur_mobile'] ?? 150); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[climatiseur_mobile_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['climatiseur_mobile_puissance'] ?? 3000); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[climatiseur_mobile_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['climatiseur_mobile_simultaneite'] ?? 50); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Chauffage par m²</td>
                    <td>-</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[chauffage_m2_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['chauffage_m2_puissance'] ?? 50); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[chauffage_m2_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['chauffage_m2_simultaneite'] ?? 80); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
                <tr>
                    <td>Eclairage</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[eclairage]" 
                                value="<?php echo esc_attr($elec_residentiel['eclairage'] ?? 750); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[eclairage_puissance]" 
                                value="<?php echo esc_attr($elec_residentiel['eclairage_puissance'] ?? 500); ?>" 
                                style="width: 80px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[eclairage_simultaneite]" 
                                value="<?php echo esc_attr($elec_residentiel['eclairage_simultaneite'] ?? 80); ?>" 
                                style="width: 60px;" /> %
                    </td>
                </tr>
            </tbody>
        </table>


        <h4>Chauffage annuel par m² - Maison (coefficient 1)</h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Type de chauffage</th>
                    <th>Mauvaise isolation</th>
                    <th>Moyenne isolation</th>
                    <th>Bonne isolation</th>
                    <th>Très bonne isolation</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Convecteurs</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_convecteurs_mauvaise]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_convecteurs_mauvaise'] ?? 215); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_convecteurs_moyenne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_convecteurs_moyenne'] ?? 150); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_convecteurs_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_convecteurs_bonne'] ?? 75); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_convecteurs_tres_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_convecteurs_tres_bonne'] ?? 37.5); ?>" 
                                style="width: 100px;" />
                    </td>
                </tr>
                <tr>
                    <td>Inertie</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_inertie_mauvaise]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_inertie_mauvaise'] ?? 185); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_inertie_moyenne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_inertie_moyenne'] ?? 125); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_inertie_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_inertie_bonne'] ?? 65); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_inertie_tres_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_inertie_tres_bonne'] ?? 30); ?>" 
                                style="width: 100px;" />
                    </td>
                </tr>
                <tr>
                    <td>Clim réversible</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_clim_mauvaise]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_clim_mauvaise'] ?? 100); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_clim_moyenne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_clim_moyenne'] ?? 70); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_clim_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_clim_bonne'] ?? 45); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_clim_tres_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_clim_tres_bonne'] ?? 17.5); ?>" 
                                style="width: 100px;" />
                    </td>
                </tr>
                <tr>
                    <td>PAC</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_pac_mauvaise]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_pac_mauvaise'] ?? 80); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_pac_moyenne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_pac_moyenne'] ?? 52.5); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_pac_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_pac_bonne'] ?? 35); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_pac_tres_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_pac_tres_bonne'] ?? 12.5); ?>" 
                                style="width: 100px;" />
                    </td>
                </tr>
                <tr>
                    <td>Autres</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_autres_mauvaise]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_autres_mauvaise'] ?? 0); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_autres_moyenne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_autres_moyenne'] ?? 0); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_autres_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_autres_bonne'] ?? 0); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[maison_autres_tres_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['maison_autres_tres_bonne'] ?? 0); ?>" 
                                style="width: 100px;" />
                    </td>
                </tr>
            </tbody>
        </table>

        <h4>Chauffage annuel par m² - Appartement (coefficient 0.95)</h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Type de chauffage</th>
                    <th>Mauvaise isolation</th>
                    <th>Moyenne isolation</th>
                    <th>Bonne isolation</th>
                    <th>Très bonne isolation</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Convecteurs</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_convecteurs_mauvaise]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_convecteurs_mauvaise'] ?? 204.25); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_convecteurs_moyenne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_convecteurs_moyenne'] ?? 142.5); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_convecteurs_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_convecteurs_bonne'] ?? 71.25); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_convecteurs_tres_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_convecteurs_tres_bonne'] ?? 35.63); ?>" 
                                style="width: 100px;" />
                    </td>
                </tr>
                <tr>
                    <td>Inertie</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_inertie_mauvaise]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_inertie_mauvaise'] ?? 175.75); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_inertie_moyenne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_inertie_moyenne'] ?? 118.75); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_inertie_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_inertie_bonne'] ?? 61.75); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_inertie_tres_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_inertie_tres_bonne'] ?? 28.5); ?>" 
                                style="width: 100px;" />
                    </td>
                </tr>
                <tr>
                    <td>Clim réversible</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_clim_mauvaise]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_clim_mauvaise'] ?? 95); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_clim_moyenne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_clim_moyenne'] ?? 66.5); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_clim_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_clim_bonne'] ?? 42.75); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_clim_tres_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_clim_tres_bonne'] ?? 16.63); ?>" 
                                style="width: 100px;" />
                    </td>
                </tr>
                <tr>
                    <td>PAC</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_pac_mauvaise]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_pac_mauvaise'] ?? 76); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_pac_moyenne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_pac_moyenne'] ?? 49.88); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_pac_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_pac_bonne'] ?? 33.25); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_pac_tres_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_pac_tres_bonne'] ?? 11.88); ?>" 
                                style="width: 100px;" />
                    </td>
                </tr>
                <tr>
                    <td>Autres</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_autres_mauvaise]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_autres_mauvaise'] ?? 0); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_autres_moyenne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_autres_moyenne'] ?? 0); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_autres_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_autres_bonne'] ?? 0); ?>" 
                                style="width: 100px;" />
                    </td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[appartement_autres_tres_bonne]" 
                                value="<?php echo esc_attr($elec_residentiel['appartement_autres_tres_bonne'] ?? 0); ?>" 
                                style="width: 100px;" />
                    </td>
                </tr>
            </tbody>
        </table>

        <h4>Coefficients multiplicateurs par nombre de personnes</h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 20%;">Équipement</th>
                    <th style="width: 13%;">1 personne</th>
                    <th style="width: 13%;">2 personnes</th>
                    <th style="width: 13%;">3 personnes</th>
                    <th style="width: 13%;">4 personnes</th>
                    <th style="width: 13%;">5 personnes</th>
                    <th style="width: 15%;">6 personnes+</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Chauffe eau</strong></td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_chauffe_eau_1]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_chauffe_eau_1'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_chauffe_eau_2]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_chauffe_eau_2'] ?? 2); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_chauffe_eau_3]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_chauffe_eau_3'] ?? 2.8); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_chauffe_eau_4]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_chauffe_eau_4'] ?? 3.7); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_chauffe_eau_5]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_chauffe_eau_5'] ?? 3.9); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_chauffe_eau_6]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_chauffe_eau_6'] ?? 5.5); ?>" 
                                style="width: 90px;" />
                    </td>
                </tr>
                <tr>
                    <td><strong>Lave linge</strong></td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_lave_linge_1]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_lave_linge_1'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_lave_linge_2]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_lave_linge_2'] ?? 1.4); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_lave_linge_3]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_lave_linge_3'] ?? 1.8); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_lave_linge_4]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_lave_linge_4'] ?? 2.2); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_lave_linge_5]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_lave_linge_5'] ?? 2.6); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_lave_linge_6]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_lave_linge_6'] ?? 3); ?>" 
                                style="width: 90px;" />
                    </td>
                </tr>
                <tr>
                    <td><strong>Four</strong></td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_four_1]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_four_1'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_four_2]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_four_2'] ?? 1.4); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_four_3]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_four_3'] ?? 1.8); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_four_4]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_four_4'] ?? 2.2); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_four_5]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_four_5'] ?? 2.6); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_four_6]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_four_6'] ?? 3); ?>" 
                                style="width: 90px;" />
                    </td>
                </tr>
                <tr>
                    <td><strong>Seche linge</strong></td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_seche_linge_1]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_seche_linge_1'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_seche_linge_2]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_seche_linge_2'] ?? 1.6); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_seche_linge_3]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_seche_linge_3'] ?? 2.2); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_seche_linge_4]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_seche_linge_4'] ?? 2.8); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_seche_linge_5]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_seche_linge_5'] ?? 3.4); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_seche_linge_6]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_seche_linge_6'] ?? 4); ?>" 
                                style="width: 90px;" />
                    </td>
                </tr>
                <tr>
                    <td><strong>Lave vaisselle</strong></td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_lave_vaisselle_1]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_lave_vaisselle_1'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_lave_vaisselle_2]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_lave_vaisselle_2'] ?? 1.4); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_lave_vaisselle_3]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_lave_vaisselle_3'] ?? 1.8); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_lave_vaisselle_4]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_lave_vaisselle_4'] ?? 2.2); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_lave_vaisselle_5]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_lave_vaisselle_5'] ?? 2.6); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_lave_vaisselle_6]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_lave_vaisselle_6'] ?? 3); ?>" 
                                style="width: 90px;" />
                    </td>
                </tr>
                <tr>
                    <td><strong>Cave à vin</strong></td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_cave_vin_1]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_cave_vin_1'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_cave_vin_2]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_cave_vin_2'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_cave_vin_3]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_cave_vin_3'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_cave_vin_4]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_cave_vin_4'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_cave_vin_5]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_cave_vin_5'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_cave_vin_6]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_cave_vin_6'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                </tr>
                <tr>
                    <td><strong>Réfrigérateur</strong></td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_refrigerateur_1]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_refrigerateur_1'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_refrigerateur_2]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_refrigerateur_2'] ?? 1.4); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_refrigerateur_3]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_refrigerateur_3'] ?? 1.8); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_refrigerateur_4]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_refrigerateur_4'] ?? 2.2); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_refrigerateur_5]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_refrigerateur_5'] ?? 2.6); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_refrigerateur_6]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_refrigerateur_6'] ?? 3); ?>" 
                                style="width: 90px;" />
                    </td>
                </tr>
                <tr>
                    <td><strong>Congélateur</strong></td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_congelateur_1]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_congelateur_1'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_congelateur_2]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_congelateur_2'] ?? 1.4); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_congelateur_3]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_congelateur_3'] ?? 1.8); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_congelateur_4]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_congelateur_4'] ?? 2.2); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_congelateur_5]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_congelateur_5'] ?? 2.6); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_congelateur_6]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_congelateur_6'] ?? 3); ?>" 
                                style="width: 90px;" />
                    </td>
                </tr>
                <tr>
                <td><strong>Plaque induction</strong></td>
                <td>
                    <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_plaque_induction_1]" 
                            value="<?php echo esc_attr($elec_residentiel['coeff_plaque_induction_1'] ?? 1); ?>" 
                            style="width: 90px;" />
                </td>
                <td>
                    <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_plaque_induction_2]" 
                            value="<?php echo esc_attr($elec_residentiel['coeff_plaque_induction_2'] ?? 1.4); ?>" 
                            style="width: 90px;" />
                </td>
                <td>
                    <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_plaque_induction_3]" 
                            value="<?php echo esc_attr($elec_residentiel['coeff_plaque_induction_3'] ?? 1.8); ?>" 
                            style="width: 90px;" />
                </td>
                <td>
                    <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_plaque_induction_4]" 
                            value="<?php echo esc_attr($elec_residentiel['coeff_plaque_induction_4'] ?? 2); ?>" 
                            style="width: 90px;" />
                </td>
                <td>
                    <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_plaque_induction_5]" 
                            value="<?php echo esc_attr($elec_residentiel['coeff_plaque_induction_5'] ?? 2.2); ?>" 
                            style="width: 90px;" />
                </td>
                <td>
                    <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_plaque_induction_6]" 
                            value="<?php echo esc_attr($elec_residentiel['coeff_plaque_induction_6'] ?? 2.4); ?>" 
                            style="width: 90px;" />
                </td>
            </tr>
            <tr>
                <td><strong>Plaque vitrocéramique</strong></td>
                <td>
                    <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_plaque_vitroceramique_1]" 
                            value="<?php echo esc_attr($elec_residentiel['coeff_plaque_vitroceramique_1'] ?? 1); ?>" 
                            style="width: 90px;" />
                </td>
                <td>
                    <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_plaque_vitroceramique_2]" 
                            value="<?php echo esc_attr($elec_residentiel['coeff_plaque_vitroceramique_2'] ?? 1.4); ?>" 
                            style="width: 90px;" />
                </td>
                <td>
                    <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_plaque_vitroceramique_3]" 
                            value="<?php echo esc_attr($elec_residentiel['coeff_plaque_vitroceramique_3'] ?? 1.8); ?>" 
                            style="width: 90px;" />
                </td>
                <td>
                    <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_plaque_vitroceramique_4]" 
                            value="<?php echo esc_attr($elec_residentiel['coeff_plaque_vitroceramique_4'] ?? 2); ?>" 
                            style="width: 90px;" />
                </td>
                <td>
                    <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_plaque_vitroceramique_5]" 
                            value="<?php echo esc_attr($elec_residentiel['coeff_plaque_vitroceramique_5'] ?? 2.2); ?>" 
                            style="width: 90px;" />
                </td>
                <td>
                    <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_plaque_vitroceramique_6]" 
                            value="<?php echo esc_attr($elec_residentiel['coeff_plaque_vitroceramique_6'] ?? 2.4); ?>" 
                            style="width: 90px;" />
                </td>
            </tr>
                <tr>
                    <td><strong>TV/PC/Box</strong></td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_tv_pc_box_1]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_tv_pc_box_1'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_tv_pc_box_2]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_tv_pc_box_2'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_tv_pc_box_3]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_tv_pc_box_3'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_tv_pc_box_4]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_tv_pc_box_4'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_tv_pc_box_5]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_tv_pc_box_5'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_tv_pc_box_6]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_tv_pc_box_6'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                </tr>
                <tr>
                    <td><strong>Piscine</strong></td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_piscine_1]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_piscine_1'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_piscine_2]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_piscine_2'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_piscine_3]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_piscine_3'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_piscine_4]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_piscine_4'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_piscine_5]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_piscine_5'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_piscine_6]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_piscine_6'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                </tr>
                <tr>
                    <td><strong>Spa/Jacuzzi</strong></td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_spa_jacuzzi_1]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_spa_jacuzzi_1'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_spa_jacuzzi_2]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_spa_jacuzzi_2'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_spa_jacuzzi_3]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_spa_jacuzzi_3'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_spa_jacuzzi_4]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_spa_jacuzzi_4'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_spa_jacuzzi_5]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_spa_jacuzzi_5'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_spa_jacuzzi_6]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_spa_jacuzzi_6'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                </tr>
                <tr>
                    <td><strong>Aquarium</strong></td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_aquarium_1]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_aquarium_1'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_aquarium_2]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_aquarium_2'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_aquarium_3]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_aquarium_3'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_aquarium_4]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_aquarium_4'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_aquarium_5]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_aquarium_5'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_aquarium_6]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_aquarium_6'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                </tr>
                <tr>
                    <td><strong>Voiture Electrique</strong></td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_voiture_electrique_1]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_voiture_electrique_1'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_voiture_electrique_2]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_voiture_electrique_2'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_voiture_electrique_3]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_voiture_electrique_3'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_voiture_electrique_4]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_voiture_electrique_4'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_voiture_electrique_5]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_voiture_electrique_5'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_voiture_electrique_6]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_voiture_electrique_6'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                </tr>
                <tr>
                    <td><strong>Climatiseur mobile</strong></td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_climatiseur_mobile_1]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_climatiseur_mobile_1'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_climatiseur_mobile_2]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_climatiseur_mobile_2'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_climatiseur_mobile_3]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_climatiseur_mobile_3'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_climatiseur_mobile_4]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_climatiseur_mobile_4'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_climatiseur_mobile_5]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_climatiseur_mobile_5'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                    <td>
                        <input type="number" step="0.1" name="htic_simulateur_elec_residentiel_data[coeff_climatiseur_mobile_6]" 
                                value="<?php echo esc_attr($elec_residentiel['coeff_climatiseur_mobile_6'] ?? 1); ?>" 
                                style="width: 90px;" />
                    </td>
                </tr>
            </tbody>
        </table>

        <h4>Éclairage annuel par m²</h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Type d'éclairage</th>
                    <th>Consommation (kWh/m²/an)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>LED</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[eclairage_led_m2]" 
                                value="<?php echo esc_attr($elec_residentiel['eclairage_led_m2'] ?? 5); ?>" 
                                style="width: 80px;" />
                    </td>
                </tr>
                <tr>
                    <td>Incandescente ou halogène</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[eclairage_incandescent_m2]" 
                                value="<?php echo esc_attr($elec_residentiel['eclairage_incandescent_m2'] ?? 15); ?>" 
                                style="width: 80px;" />
                    </td>
                </tr>
            </tbody>
        </table>

        <h4>Forfait autres petits électroménagers</h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Consommation (kWh/an)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Forfait autres petits électroménagers</td>
                    <td>
                        <input type="number" name="htic_simulateur_elec_residentiel_data[forfait_petits_electromenagers]" 
                                value="<?php echo esc_attr($elec_residentiel['forfait_petits_electromenagers'] ?? 150); ?>" 
                                style="width: 80px;" />
                    </td>
                </tr>
            </tbody>
        </table>

        <h4>Coefficients de type de logement</h4>
        <table class="form-table">
            <tr>
                <th scope="row">Coefficient Maison</th>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_elec_residentiel_data[coefficient_maison]" 
                        value="<?php echo esc_attr($elec_residentiel['coefficient_maison'] ?? 1); ?>" 
                        style="width: 100px;" />
                    <span class="description">Multiplicateur pour les maisons (défaut: 1)</span>
                </td>
            </tr>
            <tr>
                <th scope="row">Coefficient Appartement</th>
                <td>
                    <input type="number" step="0.01" 
                        name="htic_simulateur_elec_residentiel_data[coefficient_appartement]" 
                        value="<?php echo esc_attr($elec_residentiel['coefficient_appartement'] ?? 0.95); ?>" 
                        style="width: 100px;" />
                    <span class="description">Multiplicateur pour les appartements (défaut: 0.95)</span>
                </td>
            </tr>
        </table>
    </div>
    
    <?php submit_button('💾 Sauvegarder les tarifs électricité résidentiel'); ?>
</form>