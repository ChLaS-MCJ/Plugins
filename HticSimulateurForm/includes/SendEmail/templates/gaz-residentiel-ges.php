<?php
/**
 * Template email pour GES - simulation gaz résidentiel
 * Fichier: includes/SendEmail/templates/gaz-residentiel-ges.php
 */

// Variables corrigées pour le template GES
$annualTTC = $results['cout_annuel_ttc'] ?? $data['annualCost'] ?? 0;
$monthlyTTC = round($annualTTC / 10);
$consommation = $results['consommation_annuelle'] ?? $data['annualConsumption'] ?? 0;

// Déterminer le type de gaz
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🔥 NOUVELLE SIMULATION GAZ RÉSIDENTIEL</h2>
            <p style="margin: 10px 0 0 0; font-size: 14px;">Reçue le <?php echo $datetime ?? date('d/m/Y à H:i'); ?></p>
        </div>
        
        <div class="content">
            <!-- Estimation en évidence -->
            <div class="highlight-box">
                <div style="color: #666; margin-bottom: 10px;">Estimation annuelle TTC</div>
                <div class="amount"><?php echo number_format($annualTTC, 0, ' ', ' '); ?> €</div>
                <div style="color: #666; margin-top: 10px;">
                    soit <?php echo number_format($monthlyTTC, 0, ' ', ' '); ?> €/mois (sur 10 mois)
                </div>
                <div style="color: #666; margin-top: 10px; font-weight: bold;">
                    Consommation : <?php echo number_format($consommation, 0, ' ', ' '); ?> kWh/an
                </div>
            </div>
            
            <!-- Informations client -->
            <div class="section">
                <h3 style="margin-top: 0; color: #ff6b35;">👤 Informations Client</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Nom complet :</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars(($client['prenom'] ?? '') . ' ' . strtoupper($client['nom'] ?? '')); ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email :</div>
                        <div class="info-value"><a href="mailto:<?php echo htmlspecialchars($client['email'] ?? ''); ?>"><?php echo htmlspecialchars($client['email'] ?? ''); ?></a></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Téléphone :</div>
                        <div class="info-value"><strong style="color: #E39411;"><?php echo htmlspecialchars($client['telephone'] ?? 'Non renseigné'); ?></strong></div>
                    </div>
                    <?php if (!empty($client['date_naissance'])): ?>
                    <div class="info-row">
                        <div class="info-label">Date de naissance :</div>
                        <div class="info-value"><?php echo htmlspecialchars($client['date_naissance']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Informations logement -->
            <div class="section">
                <h3 style="margin-top: 0; color: #ff6b35;">🏠 Logement et Configuration</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Type de logement :</div>
                        <div class="info-value"><?php echo ($data['housingType'] ?? '') === 'maison' ? 'Maison' : 'Appartement'; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Surface habitable :</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($data['surface'] ?? ''); ?> m²</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Nombre d'occupants :</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($data['residents'] ?? ''); ?> personne<?php echo ($data['residents'] ?? 0) > 1 ? 's' : ''; ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Commune :</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['commune'] ?? ''); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Type de gaz :</div>
                        <div class="info-value"><span style="background: #ff6b35; color: white; padding: 2px 8px; border-radius: 3px;"><?php echo $typeGaz; ?></span></div>
                    </div>
                </div>
                
                <?php 
                $usages = [];
                if (!empty($data['chauffageGaz']) && $data['chauffageGaz'] === 'oui') $usages[] = '🔥 Chauffage';
                if (!empty($data['eauChaude']) && $data['eauChaude'] === 'gaz') $usages[] = '🚿 Eau chaude';
                if (!empty($data['cuisson']) && $data['cuisson'] === 'gaz') $usages[] = '🍳 Cuisson';
                ?>
                <?php if (!empty($usages)): ?>
                <div style="margin-top: 15px;">
                    <strong>Usages du gaz :</strong>
                    <div class="usage-badges">
                        <?php foreach ($usages as $usage): ?>
                            <span class="usage-badge"><?php echo $usage; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($results['repartition'])): ?>
            <!-- Répartition de la consommation -->
            <div class="section" style="border-left-color: #82C720;">
                <h3 style="margin-top: 0; color: #ff6b35;">📊 Répartition de la Consommation</h3>
                <div class="info-grid">
                    <?php if (!empty($results['repartition']['chauffage'])): ?>
                    <div class="info-row">
                        <div class="info-label">Chauffage :</div>
                        <div class="info-value"><?php echo number_format($results['repartition']['chauffage'], 0, ' ', ' '); ?> kWh/an (<?php echo round($results['repartition']['chauffage'] / $consommation * 100); ?>%)</div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($results['repartition']['eau_chaude'])): ?>
                    <div class="info-row">
                        <div class="info-label">Eau chaude :</div>
                        <div class="info-value"><?php echo number_format($results['repartition']['eau_chaude'], 0, ' ', ' '); ?> kWh/an (<?php echo round($results['repartition']['eau_chaude'] / $consommation * 100); ?>%)</div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($results['repartition']['cuisson'])): ?>
                    <div class="info-row">
                        <div class="info-label">Cuisson :</div>
                        <div class="info-value"><?php echo number_format($results['repartition']['cuisson'], 0, ' ', ' '); ?> kWh/an (<?php echo round($results['repartition']['cuisson'] / $consommation * 100); ?>%)</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($uploaded_files) && is_array($uploaded_files)): ?>
            <!-- Documents fournis -->
            <div class="documents">
                <strong>📎 Documents fournis par le client :</strong>
                <ul style="margin: 10px 0;">
                    <?php foreach ($uploaded_files as $doc_key => $doc_info): ?>
                        <li><?php echo htmlspecialchars($doc_info['name'] ?? ucfirst(str_replace('_', ' ', $doc_key))); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- Action requise -->
            <div class="action-required">
                <h3 style="margin-top: 0; color: #f59e0b;">⚠️ ACTION REQUISE</h3>
                <ul style="margin: 10px 0;">
                    <li>Contacter le client sous <strong>72h</strong></li>
                    <li>Vérifier l'éligibilité et la faisabilité technique</li>
                    <li>Proposer la finalisation du contrat gaz</li>
                    <li>Planifier la mise en service (5 jours ouvrés)</li>
                    <li>Mettre à jour le CRM résidentiel</li>
                </ul>
                
                <?php if ($priorite !== 'NORMALE'): ?>
                <div style="background: <?php echo $prioriteColor; ?>; color: white; padding: 10px; border-radius: 4px; margin-top: 15px;">
                    <strong>PRIORITÉ <?php echo $priorite; ?></strong> - 
                    <?php if ($consommation > 20000): ?>
                        Forte consommation (<?php echo number_format($consommation, 0, ' ', ' '); ?> kWh/an)
                    <?php endif; ?>
                    <?php if ($annualTTC > 2000): ?>
                        Montant élevé (<?php echo number_format($annualTTC, 0, ' ', ' '); ?> €/an)
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <p style="text-align: center; color: #666; margin-top: 30px;">
                📄 <strong>PDF complet en pièce jointe</strong> avec tous les détails de la simulation
            </p>
        </div>
        
        <div class="footer">
            <p>Email automatique - Système de simulation gaz résidentiel GES Solutions<br>
            Ne pas répondre à cet email - Utiliser les coordonnées du client</p>
        </div>
    </div>
</body>
</html>