<?php
/**
 * Template email pour GES - simulation gaz professionnel
 * Fichier: includes/SendEmail/templates/gaz-professionnel-ges.php
 * Design identique au résidentiel mais adapté aux données professionnelles
 */

// Variables adaptées pour le template GES professionnel
$company = $data['companyName'] ?? 'Non précisé';
$contact = ($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? '');
$email = $data['email'] ?? 'Non précisé';
$phone = $data['phone'] ?? 'Non précisé';
$siret = $data['siret'] ?? 'Non précisé';
$legalForm = $data['legalForm'] ?? 'Non précisé';
$consommation = $data['annualConsumption'] ?? 0;
$isHighConsumption = $data['isHighConsumption'] ?? false;

// Calculs financiers
if ($isHighConsumption) {
    $annualHTVA = 0;
    $monthlyHTVA = 0;
    $priorite = 'ÉLEVÉE';
    $prioriteColor = '#dc3545';
} else {
    $annualHTVA = $data['annualCost'] ?? 0;
    $monthlyHTVA = round($annualHTVA / 10);
    
    // Déterminer priorité
    if ($consommation > 25000 || $annualHTVA > 2500) {
        $priorite = 'ÉLEVÉE';
        $prioriteColor = '#dc3545';
    } else {
        $priorite = 'NORMALE';
        $prioriteColor = '#28a745';
    }
}

// Déterminer le type de gaz (même logique que résidentiel)
$typeGaz = 'Non précisé';
if (!empty($data['commune'])) {
    if ($data['commune'] === 'autre') {
        $typeGaz = ($data['type_gaz_autre'] ?? '') === 'naturel' ? 'Gaz naturel' : 'Gaz propane';
    } else {
        // Logique pour déterminer le type selon la commune
        $communesNaturel = ['AIRE SUR L\'ADOUR', 'BARCELONNE DU GERS', 'GAAS', 'LABATUT', 'LALUQUE', 'MISSON', 'POUILLON'];
        $typeGaz = in_array($data['commune'], $communesNaturel) ? 'Gaz naturel' : 'Gaz propane';
    }
}
?>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #222F46; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .section { background: #f9f9f9; border-left: 4px solid #ff6b35; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; padding: 8px; font-weight: bold; color: #666; width: 40%; }
        .info-value { display: table-cell; padding: 8px; color: #333; }
        .highlight-box { background: #fff3e0; border: 2px solid #ff6b35; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .amount { font-size: 32px; color: #ff6b35; font-weight: bold; }
        .footer { background: #f5f5f5; padding: 20px; text-align: center; color: #666; font-size: 12px; }
        .action-required { background: #fff3cd; border-left: 4px solid #E39411; padding: 15px; margin: 20px 0; }
        .documents { background: #e3f2fd; padding: 15px; border-radius: 4px; margin-top: 10px; }
        .usage-badges { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0; }
        .usage-badge { background: #ffebee; color: #c62828; padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: 500; }
        .high-consumption { background: #f8d7da; border-color: #dc3545; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if ($isHighConsumption): ?>
                <h2>🔥 DEMANDE DEVIS GAZ PRO - GRANDE CONSOMMATION</h2>
                <p style="margin: 10px 0 0 0; font-size: 14px; color: #ffc107;">⚠️ PRIORITÉ ÉLEVÉE - Contact requis sous 48h</p>
            <?php else: ?>
                <h2>🔥 NOUVEAU DEVIS GAZ PROFESSIONNEL</h2>
                <p style="margin: 10px 0 0 0; font-size: 14px;">Reçu le <?php echo $datetime ?? date('d/m/Y à H:i'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="content">
            <!-- Estimation en évidence -->
            <div class="highlight-box <?php echo $isHighConsumption ? 'high-consumption' : ''; ?>">
                <?php if ($isHighConsumption): ?>
                    <div style="color: #666; margin-bottom: 10px;">Consommation prévisionnelle</div>
                    <div style="font-size: 24px; color: #856404; font-weight: bold; margin: 10px 0;">
                        <?php echo number_format($consommation, 0, ' ', ' '); ?> kWh/an
                    </div>
                    <div style="color: #666; margin-top: 10px; font-weight: bold; font-size: 18px;">
                        DEVIS PERSONNALISÉ REQUIS
                    </div>
                    <div style="color: #666; margin-top: 10px;">
                        Grande consommation nécessitant une étude sur-mesure
                    </div>
                <?php else: ?>
                    <div style="color: #666; margin-bottom: 10px;">Estimation annuelle HTVA</div>
                    <div class="amount"><?php echo number_format($annualHTVA, 0, ' ', ' '); ?> €</div>
                    <div style="color: #666; margin-top: 10px;">
                        soit <?php echo number_format($monthlyHTVA, 0, ' ', ' '); ?> €/mois HTVA (sur 10 mois)
                    </div>
                    <div style="color: #666; margin-top: 10px; font-weight: bold;">
                        Consommation : <?php echo number_format($consommation, 0, ' ', ' '); ?> kWh/an | 
                        Hors TVA (+ 20% en sus)
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Informations entreprise -->
            <div class="section">
                <h3 style="margin-top: 0; color: #ff6b35;">🏢 Informations Entreprise</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Raison sociale :</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($company); ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Forme juridique :</div>
                        <div class="info-value"><?php echo htmlspecialchars($legalForm); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">SIRET :</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($siret); ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Code NAF/APE :</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['nafCode'] ?? 'Non précisé'); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Type de contrat :</div>
                        <div class="info-value"><?php echo ($data['contractType'] ?? 'principal') === 'principal' ? 'Contrat principal' : 'Site secondaire'; ?></div>
                    </div>
                </div>
                
                <?php if (!empty($data['companyAddress'])): ?>
                <div style="margin-top: 15px; background: #e9ecef; padding: 10px; border-radius: 4px;">
                    <strong>Adresse du site :</strong><br>
                    <?php echo htmlspecialchars($data['companyAddress']); ?>
                    <?php if (!empty($data['companyPostalCode']) || !empty($data['companyCity'])): ?>
                        <br><?php echo htmlspecialchars(($data['companyPostalCode'] ?? '') . ' ' . ($data['companyCity'] ?? '')); ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Contact responsable -->
            <div class="section">
                <h3 style="margin-top: 0; color: #ff6b35;">👤 Contact Responsable</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Nom complet :</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($contact); ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Fonction :</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['fonction'] ?? 'Non précisée'); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email professionnel :</div>
                        <div class="info-value"><a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Téléphone :</div>
                        <div class="info-value"><strong style="color: #E39411;"><a href="tel:<?php echo htmlspecialchars($phone); ?>"><?php echo htmlspecialchars($phone); ?></a></strong></div>
                    </div>
                </div>
            </div>
            
            <!-- Configuration gaz professionnel -->
            <div class="section">
                <h3 style="margin-top: 0; color: #ff6b35;">🔥 Configuration Gaz Professionnel</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Commune desservie :</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['commune'] ?? ''); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Type de gaz :</div>
                        <div class="info-value"><span style="background: #ff6b35; color: white; padding: 2px 8px; border-radius: 3px;"><?php echo $typeGaz; ?></span></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Consommation prévisionnelle :</div>
                        <div class="info-value">
                            <strong><?php echo number_format($consommation, 0, ' ', ' '); ?> kWh/an</strong>
                            <?php if ($isHighConsumption): ?>
                                <span style="background: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 3px; margin-left: 10px; font-size: 12px;">GRANDE CONSO</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($data['selectedTariff'])): ?>
                    <div class="info-row">
                        <div class="info-label">Tarif sélectionné :</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['selectedTariff']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!$isHighConsumption && $annualHTVA > 0): ?>
                <div style="margin-top: 15px; background: #e8f5e8; padding: 10px; border-radius: 4px;">
                    <strong>💰 Estimation financière :</strong><br>
                    Montant annuel HTVA: <strong><?php echo number_format($annualHTVA, 2, ',', ' '); ?> €</strong><br>
                    Montant mensuel moyen: <strong><?php echo number_format($monthlyHTVA, 2, ',', ' '); ?> € HTVA</strong><br>
                    <small style="color: #666;">+ TVA 20% en sus selon réglementation</small>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Validations et documents professionnels -->
            <div class="section" style="border-left-color: #82C720;">
                <h3 style="margin-top: 0; color: #ff6b35;">✅ Validations & Documents Professionnels</h3>
                
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Conditions générales pro :</div>
                        <div class="info-value">
                            <?php if ($data['acceptConditions'] ?? false): ?>
                                <span style="color: #28a745; font-weight: bold;">✅ Acceptées</span>
                            <?php else: ?>
                                <span style="color: #dc3545; font-weight: bold;">❌ NON ACCEPTÉES</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Prélèvement automatique :</div>
                        <div class="info-value">
                            <?php if ($data['acceptPrelevement'] ?? false): ?>
                                <span style="color: #28a745; font-weight: bold;">✅ Autorisé</span>
                            <?php else: ?>
                                <span style="color: #ffc107; font-weight: bold;">⚠️ Autre moyen de paiement</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Pouvoir d'engagement :</div>
                        <div class="info-value">
                            <?php if ($data['certifiePouvoir'] ?? false): ?>
                                <span style="color: #28a745; font-weight: bold;">✅ Certifié</span>
                            <?php else: ?>
                                <span style="color: #dc3545; font-weight: bold;">❌ NON CERTIFIÉ</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Documents fournis -->
                <?php if (!empty($uploaded_files) && is_array($uploaded_files)): ?>
                <div class="documents">
                    <strong>📎 Documents professionnels fournis :</strong>
                    <ul style="margin: 10px 0;">
                        <?php foreach ($uploaded_files as $doc_key => $doc_info): ?>
                            <li>
                                <strong><?php 
                                    switch($doc_key) {
                                        case 'kbis_file': echo 'K-bis'; break;
                                        case 'rib_entreprise': echo 'RIB Entreprise'; break;
                                        case 'mandat_signature': echo 'Mandat de signature'; break;
                                        default: echo ucfirst(str_replace('_', ' ', $doc_key));
                                    }
                                ?> :</strong>
                                <?php echo htmlspecialchars($doc_info['name'] ?? 'Fichier joint'); ?>
                                <?php if (isset($doc_info['size'])): ?>
                                    <small>(<?php echo number_format($doc_info['size'] / 1024, 1); ?> Ko)</small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php else: ?>
                <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin-top: 10px;">
                    ⚠️ <strong>Documents manquants</strong> - Vérifier la transmission des pièces jointes professionnelles
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Action requise -->
            <div class="action-required">
                <h3 style="margin-top: 0; color: #f59e0b;">⚠️ ACTION REQUISE</h3>
                <?php if ($isHighConsumption): ?>
                    <ul style="margin: 10px 0;">
                        <li><strong>📞 CONTACT PRIORITAIRE sous 48h maximum</strong></li>
                        <li>Analyser les besoins spécifiques de l'entreprise</li>
                        <li>Établir un devis personnalisé sur-mesure</li>
                        <li>Négocier les conditions tarifaires optimales</li>
                        <li>Proposer solutions techniques adaptées</li>
                        <li>Mettre à jour le CRM professionnel - Priorité ÉLEVÉE</li>
                    </ul>
                <?php else: ?>
                    <ul style="margin: 10px 0;">
                        <li>Contacter l'entreprise sous <strong>72h</strong></li>
                        <li>Vérifier l'éligibilité et la faisabilité technique</li>
                        <li>Valider les conditions commerciales du devis</li>
                        <li>Proposer la finalisation du contrat gaz professionnel</li>
                        <li>Planifier la mise en service (5 jours ouvrés)</li>
                        <li>Mettre à jour le CRM professionnel</li>
                    </ul>
                <?php endif; ?>
                
                <?php if ($priorite !== 'NORMALE'): ?>
                <div style="background: <?php echo $prioriteColor; ?>; color: white; padding: 10px; border-radius: 4px; margin-top: 15px;">
                    <strong>PRIORITÉ <?php echo $priorite; ?></strong> - 
                    <?php if ($isHighConsumption): ?>
                        Grande consommation (<?php echo number_format($consommation, 0, ' ', ' '); ?> kWh/an)
                    <?php elseif ($consommation > 25000): ?>
                        Forte consommation (<?php echo number_format($consommation, 0, ' ', ' '); ?> kWh/an)
                    <?php endif; ?>
                    <?php if ($annualHTVA > 2500): ?>
                        Montant élevé (<?php echo number_format($annualHTVA, 0, ' ', ' '); ?> €/an HTVA)
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <p style="text-align: center; color: #666; margin-top: 30px;">
                <?php if ($isHighConsumption): ?>
                    📄 <strong>Demande de devis personnalisé transmise</strong> - Contact prioritaire requis
                <?php else: ?>
                    📄 <strong>PDF complet en pièce jointe</strong> avec tous les détails du devis professionnel
                <?php endif; ?>
            </p>
        </div>
        
        <div class="footer">
            <p>Email automatique - Système de simulation gaz professionnel GES Solutions<br>
            Ne pas répondre à cet email - Utiliser les coordonnées du responsable entreprise<br>
            Référence: <?php echo $reference ?? 'GAZ-PRO-' . date('Ymd') . '-' . rand(1000, 9999); ?> | 
            IP: <?php echo $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'N/A'; ?></p>
        </div>
    </div>
</body>
</html>