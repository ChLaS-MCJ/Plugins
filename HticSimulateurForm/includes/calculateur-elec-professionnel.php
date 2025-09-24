<?php
/**
 * Calculateur pour l'électricité professionnelle
 * Fonction wrapper pour l'appel AJAX
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fonction principale appelée par le système AJAX
 * @param array $userData Données du formulaire utilisateur
 * @param array $configData Configuration des tarifs
 * @return array Résultat du calcul
 */
function htic_calculateur_elec_professionnel($userData, $configData) {
    try {
        if (empty($configData)) {
            $configData = get_option('htic_simulateur_elec_professionnel_data', array());
        }
    
        $calculator = new HticCalculateurElecProfessionnel($configData);
        
        $result = $calculator->calculate($userData);
        
        if ($result && isset($result['success']) && $result['success']) {
            return array(
                'success' => true,
                'data' => $result
            );
        } else {
            return array(
                'success' => false,
                'error' => $result['error'] ?? 'Erreur lors du calcul'
            );
        }
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Classe du calculateur électricité professionnel
 */
class HticCalculateurElecProfessionnel {
    
    private $config_data;
    
    public function __construct($configData = null) {
        $this->config_data = $configData ?: get_option('htic_simulateur_elec_professionnel_data', array());
    }
    
    public function calculate($userData) {
        $categorie = $userData['categorie'] ?? 'BT < 36 kVA';
        $puissance = intval($userData['puissance'] ?? 6);
        $consommation = intval($userData['conso_annuelle'] ?? 50000);
        $formule = $userData['formule_tarifaire'] ?? 'Base';
        $eligible_trv = ($userData['eligible_trv'] ?? 'oui') === 'oui';
        $offres = array();
        
        if ($eligible_trv && $puissance <= 36) {
            $offre_trv = $this->calculateTRV($puissance, $formule, $consommation);
            if ($offre_trv['total_ttc'] > 0) {
                $offre_trv['nom'] = 'Tarif Bleu Pro (TRV)';
                $offre_trv['type'] = 'Tarif réglementé';
                $offres[] = $offre_trv;
            }
        }
        
        if ($puissance >= 9 && $puissance <= 36) {
            $offre_tempo = $this->calculateTempo($puissance, $consommation);
            if ($offre_tempo['total_ttc'] > 0) {
                $offre_tempo['nom'] = 'Tempo Pro';
                $offre_tempo['type'] = 'Tarif Tempo';
                $offres[] = $offre_tempo;
            }
        }
    
        $nom_offre_fr = $this->config_data['pro_nom_offre_francaise'] ?? 'Offre 100% française';
        $offre_fr = $this->calculateOffreFrancaise($puissance, $formule, $consommation);
        if ($offre_fr['total_ttc'] > 0) {
            $offre_fr['nom'] = $nom_offre_fr;
            $offre_fr['type'] = 'Énergie verte';
            $offres[] = $offre_fr;
        }
        
        $nom_autre_offre = $this->config_data['pro_nom_autre_offre'] ?? 'Autre offre';
        $autre_offre = $this->calculateAutreOffre($puissance, $formule, $consommation);
        if ($autre_offre['total_ttc'] > 0) {
            $autre_offre['nom'] = $nom_autre_offre;
            $autre_offre['type'] = 'Offre de marché';
            $offres[] = $autre_offre;
        }
        
        if (empty($offres)) {
            $offres[] = $this->getOffreParDefaut($puissance, $formule, $consommation);
        }
    
        usort($offres, function($a, $b) {
            return $a['total_ttc'] <=> $b['total_ttc'];
        });
        
        $offres[0]['meilleure'] = true;
        
        $economie_max = 0;
        if (count($offres) > 1) {
            $economie_max = $offres[count($offres)-1]['total_ttc'] - $offres[0]['total_ttc'];
        }
        
        $user_data_complet = array(
            'raison_sociale' => $userData['raison_sociale'] ?? '',
            'siret' => $userData['siret'] ?? '',
            'nom' => $userData['nom'] ?? '',
            'prenom' => $userData['prenom'] ?? '',
            'email' => $userData['email'] ?? '',
            'telephone' => $userData['telephone'] ?? '',
            'adresse' => $userData['adresse'] ?? '',
            'code_postal' => $userData['code_postal'] ?? '',
            'ville' => $userData['ville'] ?? '',
            'kbis_filename' => $userData['kbis_filename'] ?? ''
        );
        
        return array(
            'success' => true,
            'offres' => $offres,
            'meilleure_offre' => $offres[0],
            'economie_max' => $economie_max,
            'consommation_annuelle' => $consommation,
            'puissance' => $puissance,
            'categorie' => $categorie,
            'formule' => $formule,
            'user_data' => $user_data_complet
        );
    }
    
    /**
     * Calcule l'offre TRV (Tableau 1)
     */
    private function calculateTRV($puissance, $formule, $consommation) {
        $result = array();
        
        if ($formule === 'Base') {
            $abo_mensuel = floatval($this->config_data['pro_trv_base_abo_' . $puissance] ?? 12.67);
            $prix_kwh = floatval($this->config_data['pro_trv_base_kwh_' . $puissance] ?? 0.2516);
            
            $result['abonnement_annuel'] = $abo_mensuel * 12;
            $result['cout_consommation'] = $consommation * $prix_kwh;
            $result['details'] = "Prix unique : {$prix_kwh}€/kWh";
            
        } else {
            $abo_mensuel = floatval($this->config_data['pro_trv_hc_abo_' . $puissance] ?? 13.28);
            $prix_hp = floatval($this->config_data['pro_trv_hc_hp_' . $puissance] ?? 0.27);
            $prix_hc = floatval($this->config_data['pro_trv_hc_hc_' . $puissance] ?? 0.2068);
            
            $ratio_hp = floatval($this->config_data['pro_ratio_hp_defaut'] ?? 60) / 100;
            $ratio_hc = 1 - $ratio_hp;
            
            $result['abonnement_annuel'] = $abo_mensuel * 12;
            $result['cout_consommation'] = ($consommation * $ratio_hp * $prix_hp) + 
                                          ($consommation * $ratio_hc * $prix_hc);
            $result['details'] = "HP: {$prix_hp}€ | HC: {$prix_hc}€";
        }
        
        return $this->ajouterTaxes($result, $consommation);
    }
    
    /**
     * Calcule l'offre Tempo (Tableau 2)
     */
    private function calculateTempo($puissance, $consommation) {
        $result = array();
        
        $abo_mensuel = floatval($this->config_data['pro_tempo_abo_' . $puissance] ?? 16.55);
        $result['abonnement_annuel'] = $abo_mensuel * 12;
  
        $jours_rouge = intval($this->config_data['pro_tempo_jours_rouges'] ?? 22);
        $jours_blanc = intval($this->config_data['pro_tempo_jours_blancs'] ?? 43);
        $jours_bleu = intval($this->config_data['pro_tempo_jours_bleus'] ?? 300);
      
        $prix_rouge_hp = floatval($this->config_data['pro_tempo_rouge_hp_' . $puissance] ?? 0.7562);
        $prix_rouge_hc = floatval($this->config_data['pro_tempo_rouge_hc_' . $puissance] ?? 0.1568);
        $prix_blanc_hp = floatval($this->config_data['pro_tempo_blanc_hp_' . $puissance] ?? 0.1894);
        $prix_blanc_hc = floatval($this->config_data['pro_tempo_blanc_hc_' . $puissance] ?? 0.1486);
        $prix_bleu_hp = floatval($this->config_data['pro_tempo_bleu_hp_' . $puissance] ?? 0.1609);
        $prix_bleu_hc = floatval($this->config_data['pro_tempo_bleu_hc_' . $puissance] ?? 0.1296);
    
        $conso_jour = $consommation / 365;
        $ratio_hp = 0.67;
        $ratio_hc = 0.33;
        
        $conso_rouge = $conso_jour * $jours_rouge * 1.5;
        $conso_blanc = $conso_jour * $jours_blanc * 1.2;
        $conso_bleu = $conso_jour * $jours_bleu * 0.9;
        
        $total_theorique = $conso_rouge + $conso_blanc + $conso_bleu;
        if ($total_theorique > 0) {
            $ratio_normal = $consommation / $total_theorique;
            $conso_rouge *= $ratio_normal;
            $conso_blanc *= $ratio_normal;
            $conso_bleu *= $ratio_normal;
        }
        
        $cout_rouge = ($conso_rouge * $ratio_hp * $prix_rouge_hp) + 
                    ($conso_rouge * $ratio_hc * $prix_rouge_hc);
        $cout_blanc = ($conso_blanc * $ratio_hp * $prix_blanc_hp) + 
                    ($conso_blanc * $ratio_hc * $prix_blanc_hc);
        $cout_bleu = ($conso_bleu * $ratio_hp * $prix_bleu_hp) + 
                    ($conso_bleu * $ratio_hc * $prix_bleu_hc);
        
        $result['cout_consommation'] = $cout_rouge + $cout_blanc + $cout_bleu;
   
        $result['details'] = sprintf(
            "Rouge: HP %.4f€/HC %.4f€ | Blanc: HP %.4f€/HC %.4f€ | Bleu: HP %.4f€/HC %.4f€",
            $prix_rouge_hp, $prix_rouge_hc,
            $prix_blanc_hp, $prix_blanc_hc,
            $prix_bleu_hp, $prix_bleu_hc
        );
        
        $result['details_tempo'] = array(
            'jours_rouge' => $jours_rouge,
            'jours_blanc' => $jours_blanc,
            'jours_bleu' => $jours_bleu,
            'prix_rouge_hp' => $prix_rouge_hp,
            'prix_rouge_hc' => $prix_rouge_hc,
            'prix_blanc_hp' => $prix_blanc_hp,
            'prix_blanc_hc' => $prix_blanc_hc,
            'prix_bleu_hp' => $prix_bleu_hp,
            'prix_bleu_hc' => $prix_bleu_hc,
            'cout_rouge' => $cout_rouge,
            'cout_blanc' => $cout_blanc,
            'cout_bleu' => $cout_bleu
        );
        
        return $this->ajouterTaxes($result, $consommation);
    }
    
    /**
     * Calcule l'offre française/verte (Tableau 3)
     */
    private function calculateOffreFrancaise($puissance, $formule, $consommation) {
        $result = array();
        
        if ($formule === 'Base') {
            $abo_mensuel = floatval($this->config_data['pro_offre_fr_base_abo_' . $puissance] ?? 12.67);
            $prix_kwh = floatval($this->config_data['pro_offre_fr_base_kwh_' . $puissance] ?? 0.2642);
            
            $result['abonnement_annuel'] = $abo_mensuel * 12;
            $result['cout_consommation'] = $consommation * $prix_kwh;
            $result['details'] = "100% énergie française - {$prix_kwh}€/kWh";
            
        } else {
            $abo_mensuel = floatval($this->config_data['pro_offre_fr_hc_abo_' . $puissance] ?? 13.28);
            $prix_hp = floatval($this->config_data['pro_offre_fr_hc_hp_' . $puissance] ?? 0.2835);
            $prix_hc = floatval($this->config_data['pro_offre_fr_hc_hc_' . $puissance] ?? 0.2171);
            
            $ratio_hp = floatval($this->config_data['pro_ratio_hp_defaut'] ?? 60) / 100;
            $ratio_hc = 1 - $ratio_hp;
            
            $result['abonnement_annuel'] = $abo_mensuel * 12;
            $result['cout_consommation'] = ($consommation * $ratio_hp * $prix_hp) + 
                                          ($consommation * $ratio_hc * $prix_hc);
            $result['details'] = "100% français - HP: {$prix_hp}€ | HC: {$prix_hc}€";
        }
        
        return $this->ajouterTaxes($result, $consommation);
    }
    
    /**
     * Calcule l'autre offre (Tableau 4)
     */
    private function calculateAutreOffre($puissance, $formule, $consommation) {
        $result = array();
        
        if ($formule === 'Base') {
            $abo_mensuel = floatval($this->config_data['pro_autre_offre_base_abo_' . $puissance] ?? 12.67);
            $prix_kwh = floatval($this->config_data['pro_autre_offre_base_kwh_' . $puissance] ?? 0.2768);
            
            $result['abonnement_annuel'] = $abo_mensuel * 12;
            $result['cout_consommation'] = $consommation * $prix_kwh;
            $result['details'] = "Prix compétitif - {$prix_kwh}€/kWh";
            
        } else {
            $abo_mensuel = floatval($this->config_data['pro_autre_offre_hc_abo_' . $puissance] ?? 13.28);
            $prix_hp = floatval($this->config_data['pro_autre_offre_hc_hp_' . $puissance] ?? 0.297);
            $prix_hc = floatval($this->config_data['pro_autre_offre_hc_hc_' . $puissance] ?? 0.2275);
            
            $ratio_hp = floatval($this->config_data['pro_ratio_hp_defaut'] ?? 60) / 100;
            $ratio_hc = 1 - $ratio_hp;
            
            $result['abonnement_annuel'] = $abo_mensuel * 12;
            $result['cout_consommation'] = ($consommation * $ratio_hp * $prix_hp) + 
                                          ($consommation * $ratio_hc * $prix_hc);
            $result['details'] = "Offre marché - HP: {$prix_hp}€ | HC: {$prix_hc}€";
        }
        
        return $this->ajouterTaxes($result, $consommation);
    }
    
    /**
     * Ajoute les taxes et calcule les totaux
     */
    private function ajouterTaxes($result, $consommation) {
        $result['total_ht'] = $result['abonnement_annuel'] + $result['cout_consommation'];
        $result['taxes'] = $this->calculateTaxes($result['total_ht'], $consommation);
        $result['total_taxes'] = $result['taxes']['total'];
        $result['total_ttc'] = $result['total_ht'] + $result['total_taxes'];
        
        return $result;
    }
    
    /**
     * Calcule les taxes et contributions
     */
    private function calculateTaxes($montant_ht, $consommation_kwh) {
        $cspe_par_mwh = floatval($this->config_data['pro_cspe'] ?? 22.5);
        $cspe = ($consommation_kwh / 1000) * $cspe_par_mwh;
    
        $tcfe_par_mwh = floatval($this->config_data['pro_tcfe'] ?? 9.5);
        $tcfe = ($consommation_kwh / 1000) * $tcfe_par_mwh;
        
        $cta_pct = floatval($this->config_data['pro_cta'] ?? 2.71);
        $cta = $montant_ht * ($cta_pct / 100);
        
        $total_taxes_hors_tva = $cspe + $tcfe + $cta;
  
        $tva_pct = floatval($this->config_data['pro_tva'] ?? 20);
        $tva = ($montant_ht + $total_taxes_hors_tva) * ($tva_pct / 100);
        
        return array(
            'cspe' => $cspe,
            'tcfe' => $tcfe,
            'cta' => $cta,
            'tva' => $tva,
            'total' => $total_taxes_hors_tva + $tva
        );
    }
    
    /**
     * Génère une offre par défaut si aucune offre calculée
     */
    private function getOffreParDefaut($puissance, $formule, $consommation) {
        $abo_mensuel = 12.67;
        $prix_kwh = 0.25;
        
        return array(
            'nom' => 'Offre standard',
            'type' => 'Tarif de base',
            'abonnement_annuel' => $abo_mensuel * 12,
            'cout_consommation' => $consommation * $prix_kwh,
            'total_ht' => ($abo_mensuel * 12) + ($consommation * $prix_kwh),
            'taxes' => $this->calculateTaxes(($abo_mensuel * 12) + ($consommation * $prix_kwh), $consommation),
            'total_taxes' => 0,
            'total_ttc' => 0,
            'details' => "Offre standard - {$prix_kwh}€/kWh"
        );
    }
}