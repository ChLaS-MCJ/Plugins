<?php
/**
 * Calculateur pour l'électricité professionnelle
 * Gère les calculs de budget pour les professionnels (BT < 36 kVA, BT > 36 kVA, HTA)
 */

if (!defined('ABSPATH')) {
    exit;
}

class HticCalculatorElecProfessionnel {
    
    private $tarifs_data;
    
    public function __construct() {
        $this->tarifs_data = get_option('htic_simulateur_elec_professionnel_data', array());
    }
    
    /**
     * Calcule l'estimation complète pour un professionnel
     */
    public function calculate($userData) {
        try {
            // Validation des données entrantes
            if (!$this->validateUserData($userData)) {
                throw new Exception('Données invalides');
            }
            
            // Déterminer l'éligibilité TRV
            $eligibleTRV = $this->checkEligibiliteTRV($userData);
            
            // Récupérer les tarifs appropriés
            $tarifs = $this->getTarifsForUser($userData, $eligibleTRV);
            
            // Calculer les coûts
            $results = $this->calculateCosts($userData, $tarifs);
            
            // Ajouter les informations complémentaires
            $results['eligibleTRV'] = $eligibleTRV;
            $results['categorie'] = $userData['categorie'];
            $results['puissance'] = $userData['puissance'];
            $results['formule'] = $userData['formule_tarifaire'];
            
            return array(
                'success' => true,
                'data' => $results
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Vérifie l'éligibilité au TRV
     */
    private function checkEligibiliteTRV($userData) {
        // TRV uniquement pour BT < 36 kVA avec moins de 10 salariés et CA < 3M€
        if ($userData['categorie'] !== 'bt_inf_36') {
            return false;
        }
        
        $nbSalaries = isset($userData['nb_salaries']) ? intval($userData['nb_salaries']) : 0;
        $ca = isset($userData['chiffre_affaires']) ? floatval($userData['chiffre_affaires']) : 0;
        
        return ($nbSalaries < 10 && $ca < 3000000);
    }
    
    /**
     * Validation des données utilisateur
     */
    private function validateUserData($userData) {
        $required = ['categorie', 'puissance', 'consommation_annuelle'];
        
        foreach ($required as $field) {
            if (!isset($userData[$field]) || empty($userData[$field])) {
                return false;
            }
        }
        
        // Validation de la catégorie
        $categories_valides = ['bt_inf_36', 'bt_sup_36', 'hta'];
        if (!in_array($userData['categorie'], $categories_valides)) {
            return false;
        }
        
        // Validation de la puissance selon la catégorie
        if ($userData['categorie'] === 'bt_inf_36') {
            $puissances_valides = [3, 6, 9, 12, 15, 18, 24, 30, 36];
            if (!in_array(intval($userData['puissance']), $puissances_valides)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Récupère les tarifs appropriés selon le profil utilisateur
     */
    private function getTarifsForUser($userData, $eligibleTRV) {
        $tarifs = array();
        $typeOffre = isset($userData['type_offre']) ? $userData['type_offre'] : 'trv';
        $formule = isset($userData['formule_tarifaire']) ? $userData['formule_tarifaire'] : 'base';
        $puissance = intval($userData['puissance']);
        
        // Structure des tarifs selon le type d'offre
        if ($eligibleTRV && $typeOffre === 'trv') {
            // Tarifs réglementés
            $tarifs = $this->getTarifsTRV($puissance, $formule);
        } else {
            // Offres de marché
            $tarifs = $this->getTarifsMarche($puissance, $formule, $userData);
        }
        
        return $tarifs;
    }
    
    /**
     * Récupère les tarifs TRV depuis les options
     */
    private function getTarifsTRV($puissance, $formule) {
        $grille = isset($this->tarifs_data['trv_pro']) ? $this->tarifs_data['trv_pro'] : $this->getDefaultTRVTarifs();
        
        // Trouver la ligne correspondant à la puissance
        $tarifs = array();
        foreach ($grille as $ligne) {
            if (isset($ligne['puissance']) && intval($ligne['puissance']) == $puissance) {
                switch ($formule) {
                    case 'base':
                        $tarifs = array(
                            'abonnement_mensuel' => floatval($ligne['base_abo']),
                            'prix_kwh' => floatval($ligne['base_kwh'])
                        );
                        break;
                    
                    case 'heures_creuses':
                        $tarifs = array(
                            'abonnement_mensuel' => floatval($ligne['hc_abo']),
                            'prix_kwh_hp' => floatval($ligne['hc_hp']),
                            'prix_kwh_hc' => floatval($ligne['hc_hc'])
                        );
                        break;
                    
                    case 'tempo':
                        $tarifs = array(
                            'abonnement_mensuel' => floatval($ligne['tempo_abo']),
                            'prix_rouge_hp' => floatval($ligne['tempo_rouge_hp']),
                            'prix_rouge_hc' => floatval($ligne['tempo_rouge_hc']),
                            'prix_blanc_hp' => floatval($ligne['tempo_blanc_hp']),
                            'prix_blanc_hc' => floatval($ligne['tempo_blanc_hc']),
                            'prix_bleu_hp' => floatval($ligne['tempo_bleu_hp']),
                            'prix_bleu_hc' => floatval($ligne['tempo_bleu_hc'])
                        );
                        break;
                }
                break;
            }
        }
        
        // Si pas trouvé, utiliser les tarifs par défaut
        if (empty($tarifs)) {
            $tarifs = $this->getDefaultTarifForPuissance($puissance, $formule);
        }
        
        return $tarifs;
    }
    
    /**
     * Tarifs TRV par défaut (en € HT)
     */
    private function getDefaultTarifForPuissance($puissance, $formule) {
        $grille_base = array(
            3 => array('abo' => 9.69, 'kwh' => 0.2516),
            6 => array('abo' => 12.67, 'kwh' => 0.2516),
            9 => array('abo' => 15.89, 'kwh' => 0.2516),
            12 => array('abo' => 19.16, 'kwh' => 0.2516),
            15 => array('abo' => 22.21, 'kwh' => 0.2516),
            18 => array('abo' => 25.24, 'kwh' => 0.2516),
            24 => array('abo' => 31.96, 'kwh' => 0.2516),
            30 => array('abo' => 37.68, 'kwh' => 0.2516),
            36 => array('abo' => 44.43, 'kwh' => 0.2516)
        );
        
        $grille_hc = array(
            6 => array('abo' => 13.28, 'hp' => 0.27, 'hc' => 0.2068),
            9 => array('abo' => 16.82, 'hp' => 0.27, 'hc' => 0.2068),
            12 => array('abo' => 20.28, 'hp' => 0.27, 'hc' => 0.2068),
            15 => array('abo' => 23.57, 'hp' => 0.27, 'hc' => 0.2068),
            18 => array('abo' => 26.84, 'hp' => 0.27, 'hc' => 0.2068),
            24 => array('abo' => 33.70, 'hp' => 0.27, 'hc' => 0.2068),
            30 => array('abo' => 39.94, 'hp' => 0.27, 'hc' => 0.2068),
            36 => array('abo' => 46.24, 'hp' => 0.27, 'hc' => 0.2068)
        );
        
        $grille_tempo = array(
            9 => array('abo' => 13.23, 'rouge_hp' => 0.7562, 'rouge_hc' => 0.1568,
                      'blanc_hp' => 0.1894, 'blanc_hc' => 0.1486,
                      'bleu_hp' => 0.1609, 'bleu_hc' => 0.1296),
            12 => array('abo' => 16.55, 'rouge_hp' => 0.7562, 'rouge_hc' => 0.1568,
                       'blanc_hp' => 0.1894, 'blanc_hc' => 0.1486,
                       'bleu_hp' => 0.1609, 'bleu_hc' => 0.1296),
            15 => array('abo' => 23.08, 'rouge_hp' => 0.7562, 'rouge_hc' => 0.1568,
                       'blanc_hp' => 0.1894, 'blanc_hc' => 0.1486,
                       'bleu_hp' => 0.1609, 'bleu_hc' => 0.1296),
            18 => array('abo' => 26.18, 'rouge_hp' => 0.7562, 'rouge_hc' => 0.1568,
                       'blanc_hp' => 0.1894, 'blanc_hc' => 0.1486,
                       'bleu_hp' => 0.1609, 'bleu_hc' => 0.1296),
            24 => array('abo' => 38.22, 'rouge_hp' => 0.7562, 'rouge_hc' => 0.1568,
                       'blanc_hp' => 0.1894, 'blanc_hc' => 0.1486,
                       'bleu_hp' => 0.1609, 'bleu_hc' => 0.1296),
            30 => array('abo' => 39.50, 'rouge_hp' => 0.7562, 'rouge_hc' => 0.1568,
                       'blanc_hp' => 0.1894, 'blanc_hc' => 0.1486,
                       'bleu_hp' => 0.1609, 'bleu_hc' => 0.1296),
            36 => array('abo' => 47.15, 'rouge_hp' => 0.7562, 'rouge_hc' => 0.1568,
                       'blanc_hp' => 0.1894, 'blanc_hc' => 0.1486,
                       'bleu_hp' => 0.1609, 'bleu_hc' => 0.1296)
        );
        
        switch ($formule) {
            case 'base':
                if (isset($grille_base[$puissance])) {
                    return array(
                        'abonnement_mensuel' => $grille_base[$puissance]['abo'],
                        'prix_kwh' => $grille_base[$puissance]['kwh']
                    );
                }
                break;
            
            case 'heures_creuses':
                if (isset($grille_hc[$puissance])) {
                    return array(
                        'abonnement_mensuel' => $grille_hc[$puissance]['abo'],
                        'prix_kwh_hp' => $grille_hc[$puissance]['hp'],
                        'prix_kwh_hc' => $grille_hc[$puissance]['hc']
                    );
                }
                break;
            
            case 'tempo':
                if (isset($grille_tempo[$puissance])) {
                    $t = $grille_tempo[$puissance];
                    return array(
                        'abonnement_mensuel' => $t['abo'],
                        'prix_rouge_hp' => $t['rouge_hp'],
                        'prix_rouge_hc' => $t['rouge_hc'],
                        'prix_blanc_hp' => $t['blanc_hp'],
                        'prix_blanc_hc' => $t['blanc_hc'],
                        'prix_bleu_hp' => $t['bleu_hp'],
                        'prix_bleu_hc' => $t['bleu_hc']
                    );
                }
                break;
        }
        
        // Valeurs par défaut si rien trouvé
        return array(
            'abonnement_mensuel' => 20,
            'prix_kwh' => 0.25
        );
    }
    
    /**
     * Récupère les tarifs des offres de marché
     */
    private function getTarifsMarche($puissance, $formule, $userData) {
        // Si l'utilisateur a fourni ses propres tarifs
        if (isset($userData['tarifs_personnalises']) && $userData['tarifs_personnalises']) {
            return $this->getTarifsPersonnalises($userData);
        }
        
        // Sinon utiliser les tarifs des offres de marché standards (+5% sur TRV généralement)
        $tarifsTRV = $this->getDefaultTarifForPuissance($puissance, $formule);
        
        // Appliquer une majoration pour simuler une offre de marché
        $majoration = 1.05; // +5%
        
        foreach ($tarifsTRV as $key => $value) {
            if ($key !== 'abonnement_mensuel') {
                $tarifsTRV[$key] = $value * $majoration;
            }
        }
        
        return $tarifsTRV;
    }
    
    /**
     * Récupère les tarifs personnalisés saisis par l'utilisateur
     */
    private function getTarifsPersonnalises($userData) {
        $tarifs = array();
        
        if (isset($userData['abo_mensuel_perso'])) {
            $tarifs['abonnement_mensuel'] = floatval($userData['abo_mensuel_perso']);
        }
        
        if (isset($userData['prix_kwh_perso'])) {
            $tarifs['prix_kwh'] = floatval($userData['prix_kwh_perso']);
        }
        
        if (isset($userData['prix_kwh_hp_perso'])) {
            $tarifs['prix_kwh_hp'] = floatval($userData['prix_kwh_hp_perso']);
        }
        
        if (isset($userData['prix_kwh_hc_perso'])) {
            $tarifs['prix_kwh_hc'] = floatval($userData['prix_kwh_hc_perso']);
        }
        
        return $tarifs;
    }
    
    /**
     * Calcule les coûts selon la formule tarifaire
     */
    private function calculateCosts($userData, $tarifs) {
        $consommation = floatval($userData['consommation_annuelle']);
        $formule = isset($userData['formule_tarifaire']) ? $userData['formule_tarifaire'] : 'base';
        
        $results = array(
            'abonnement_annuel' => $tarifs['abonnement_mensuel'] * 12,
            'abonnement_mensuel' => $tarifs['abonnement_mensuel']
        );
        
        switch ($formule) {
            case 'base':
                $results['cout_consommation'] = $consommation * $tarifs['prix_kwh'];
                $results['detail_consommation'] = array(
                    'total_kwh' => $consommation,
                    'prix_kwh' => $tarifs['prix_kwh']
                );
                break;
            
            case 'heures_creuses':
                // Répartition par défaut ou personnalisée
                $pct_hp = isset($userData['pct_hp']) ? floatval($userData['pct_hp']) / 100 : 0.6;
                $pct_hc = 1 - $pct_hp;
                
                $conso_hp = $consommation * $pct_hp;
                $conso_hc = $consommation * $pct_hc;
                
                $cout_hp = $conso_hp * $tarifs['prix_kwh_hp'];
                $cout_hc = $conso_hc * $tarifs['prix_kwh_hc'];
                
                $results['cout_consommation'] = $cout_hp + $cout_hc;
                $results['detail_consommation'] = array(
                    'hp_kwh' => $conso_hp,
                    'hc_kwh' => $conso_hc,
                    'prix_hp' => $tarifs['prix_kwh_hp'],
                    'prix_hc' => $tarifs['prix_kwh_hc'],
                    'cout_hp' => $cout_hp,
                    'cout_hc' => $cout_hc
                );
                break;
            
            case 'tempo':
                // Répartition des jours Tempo
                $jours_rouge = 22;
                $jours_blanc = 43;
                $jours_bleu = 300;
                
                // Consommation par type de jour (estimation)
                $conso_jour_moyen = $consommation / 365;
                
                // Facteurs de consommation (les jours rouges sont souvent en hiver)
                $facteur_rouge = 1.5; // Plus de consommation
                $facteur_blanc = 1.2;
                $facteur_bleu = 0.95;
                
                // Calcul des consommations
                $conso_rouge = $conso_jour_moyen * $jours_rouge * $facteur_rouge;
                $conso_blanc = $conso_jour_moyen * $jours_blanc * $facteur_blanc;
                $conso_bleu = $conso_jour_moyen * $jours_bleu * $facteur_bleu;
                
                // Normaliser pour que le total corresponde à la consommation annuelle
                $total_theorique = $conso_rouge + $conso_blanc + $conso_bleu;
                $ratio = $consommation / $total_theorique;
                
                $conso_rouge *= $ratio;
                $conso_blanc *= $ratio;
                $conso_bleu *= $ratio;
                
                // Répartition HP/HC (16h HP, 8h HC par jour)
                $ratio_hp = 0.67; // 16/24
                $ratio_hc = 0.33; // 8/24
                
                // Calcul des coûts
                $cout_rouge = ($conso_rouge * $ratio_hp * $tarifs['prix_rouge_hp']) + 
                             ($conso_rouge * $ratio_hc * $tarifs['prix_rouge_hc']);
                
                $cout_blanc = ($conso_blanc * $ratio_hp * $tarifs['prix_blanc_hp']) + 
                             ($conso_blanc * $ratio_hc * $tarifs['prix_blanc_hc']);
                
                $cout_bleu = ($conso_bleu * $ratio_hp * $tarifs['prix_bleu_hp']) + 
                            ($conso_bleu * $ratio_hc * $tarifs['prix_bleu_hc']);
                
                $results['cout_consommation'] = $cout_rouge + $cout_blanc + $cout_bleu;
                $results['detail_consommation'] = array(
                    'jours_rouge' => array(
                        'nb_jours' => $jours_rouge,
                        'conso_totale' => $conso_rouge,
                        'cout' => $cout_rouge
                    ),
                    'jours_blanc' => array(
                        'nb_jours' => $jours_blanc,
                        'conso_totale' => $conso_blanc,
                        'cout' => $cout_blanc
                    ),
                    'jours_bleu' => array(
                        'nb_jours' => $jours_bleu,
                        'conso_totale' => $conso_bleu,
                        'cout' => $cout_bleu
                    )
                );
                break;
        }
        
        // Calcul du total HT
        $results['total_ht'] = $results['abonnement_annuel'] + $results['cout_consommation'];
        
        // Calcul des taxes
        $results['taxes'] = $this->calculateTaxes($results['total_ht'], $consommation);
        
        // Total TTC
        $results['total_ttc'] = $results['total_ht'] + $results['taxes']['total_taxes'] + $results['taxes']['tva'];
        
        // Moyennes mensuelles
        $results['cout_mensuel_moyen_ht'] = $results['total_ht'] / 12;
        $results['cout_mensuel_moyen_ttc'] = $results['total_ttc'] / 12;
        
        return $results;
    }
    
    /**
     * Calcule les taxes et contributions
     */
    private function calculateTaxes($montant_ht, $consommation_kwh) {
        // CSPE (Contribution au Service Public de l'Électricité)
        $cspe_par_mwh = 22.5; // €/MWh
        $cspe = ($consommation_kwh / 1000) * $cspe_par_mwh;
        
        // CTA (Contribution Tarifaire d'Acheminement) - 2.71% de la part fixe
        $cta = $montant_ht * 0.0271;
        
        // TCFE (Taxe sur la Consommation Finale d'Électricité) - variable selon commune
        // Utiliser une valeur moyenne
        $tcfe_par_mwh = 9.5; // €/MWh (valeur moyenne)
        $tcfe = ($consommation_kwh / 1000) * $tcfe_par_mwh;
        
        $total_taxes = $cspe + $cta + $tcfe;
        
        // TVA 20% sur HT + taxes
        $tva = ($montant_ht + $total_taxes) * 0.20;
        
        return array(
            'cspe' => $cspe,
            'cta' => $cta,
            'tcfe' => $tcfe,
            'total_taxes' => $total_taxes,
            'tva' => $tva
        );
    }
    
    /**
     * Génère le HTML du résultat
     */
    public function generateResultHTML($results) {
        if (!$results['success']) {
            return '<div class="error">' . $results['error'] . '</div>';
        }
        
        $data = $results['data'];
        ob_start();
        ?>
        <div class="htic-results-container">
            <h3>Estimation de votre budget électricité professionnel</h3>
            
            <div class="result-summary">
                <div class="result-box highlight">
                    <h4>Budget annuel TTC</h4>
                    <p class="amount-large"><?php echo number_format($data['total_ttc'], 2, ',', ' '); ?> €</p>
                    <p class="amount-detail">Soit <?php echo number_format($data['cout_mensuel_moyen_ttc'], 2, ',', ' '); ?> €/mois</p>
                </div>
            </div>
            
            <div class="result-details">
                <h4>Détail du calcul</h4>
                
                <table class="detail-table">
                    <tr>
                        <td>Abonnement annuel HT</td>
                        <td class="amount"><?php echo number_format($data['abonnement_annuel'], 2, ',', ' '); ?> €</td>
                    </tr>
                    <tr>
                        <td>Consommation HT</td>
                        <td class="amount"><?php echo number_format($data['cout_consommation'], 2, ',', ' '); ?> €</td>
                    </tr>
                    <tr class="subtotal">
                        <td><strong>Sous-total HT</strong></td>
                        <td class="amount"><strong><?php echo number_format($data['total_ht'], 2, ',', ' '); ?> €</strong></td>
                    </tr>
                    <tr>
                        <td>CSPE</td>
                        <td class="amount"><?php echo number_format($data['taxes']['cspe'], 2, ',', ' '); ?> €</td>
                    </tr>
                    <tr>
                        <td>CTA</td>
                        <td class="amount"><?php echo number_format($data['taxes']['cta'], 2, ',', ' '); ?> €</td>
                    </tr>
                    <tr>
                        <td>TCFE</td>
                        <td class="amount"><?php echo number_format($data['taxes']['tcfe'], 2, ',', ' '); ?> €</td>
                    </tr>
                    <tr>
                        <td>TVA (20%)</td>
                        <td class="amount"><?php echo number_format($data['taxes']['tva'], 2, ',', ' '); ?> €</td>
                    </tr>
                    <tr class="total">
                        <td><strong>TOTAL TTC</strong></td>
                        <td class="amount"><strong><?php echo number_format($data['total_ttc'], 2, ',', ' '); ?> €</strong></td>
                    </tr>
                </table>
                
                <?php if ($data['formule'] === 'heures_creuses' && isset($data['detail_consommation'])): ?>
                <div class="detail-section">
                    <h5>Répartition Heures Pleines / Heures Creuses</h5>
                    <ul>
                        <li>Heures Pleines : <?php echo number_format($data['detail_consommation']['hp_kwh'], 0, ',', ' '); ?> kWh 
                            à <?php echo number_format($data['detail_consommation']['prix_hp'], 4, ',', ' '); ?> €/kWh</li>
                        <li>Heures Creuses : <?php echo number_format($data['detail_consommation']['hc_kwh'], 0, ',', ' '); ?> kWh 
                            à <?php echo number_format($data['detail_consommation']['prix_hc'], 4, ',', ' '); ?> €/kWh</li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if ($data['formule'] === 'tempo' && isset($data['detail_consommation'])): ?>
                <div class="detail-section">
                    <h5>Répartition Tempo</h5>
                    <ul>
                        <li>Jours Rouges (<?php echo $data['detail_consommation']['jours_rouge']['nb_jours']; ?> j) : 
                            <?php echo number_format($data['detail_consommation']['jours_rouge']['cout'], 2, ',', ' '); ?> €</li>
                        <li>Jours Blancs (<?php echo $data['detail_consommation']['jours_blanc']['nb_jours']; ?> j) : 
                            <?php echo number_format($data['detail_consommation']['jours_blanc']['cout'], 2, ',', ' '); ?> €</li>
                        <li>Jours Bleus (<?php echo $data['detail_consommation']['jours_bleu']['nb_jours']; ?> j) : 
                            <?php echo number_format($data['detail_consommation']['jours_bleu']['cout'], 2, ',', ' '); ?> €</li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="info-section">
                    <h5>Informations sur votre profil</h5>
                    <ul>
                        <li>Catégorie : <?php echo $this->getCategorieLabel($data['categorie']); ?></li>
                        <li>Puissance souscrite : <?php echo $data['puissance']; ?> kVA</li>
                        <li>Formule tarifaire : <?php echo $this->getFormuleLabel($data['formule']); ?></li>
                        <li>Éligibilité TRV : <?php echo $data['eligibleTRV'] ? 'Oui' : 'Non'; ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Labels pour l'affichage
     */
    private function getCategorieLabel($categorie) {
        $labels = array(
            'bt_inf_36' => 'BT < 36 kVA',
            'bt_sup_36' => 'BT > 36 kVA',
            'hta' => 'HTA (Haute Tension)'
        );
        return isset($labels[$categorie]) ? $labels[$categorie] : $categorie;
    }
    
    private function getFormuleLabel($formule) {
        $labels = array(
            'base' => 'Base',
            'heures_creuses' => 'Heures Creuses',
            'tempo' => 'Tempo Pro'
        );
        return isset($labels[$formule]) ? $labels[$formule] : $formule;
    }
}
?>