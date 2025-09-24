<?php
/**
 * Template email pour GES - devis électricité professionnel
 * Fichier: includes/SendEmail/templates/elec-professionnel-ges.php
 */

$annualHTVA = $results['estimation_annuelle'] ?? 0;
$totalTTC = round($annualHTVA * 1.2);

?>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #222F46; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .section { background: #f9f9f9; border-left: 4px solid #222F46; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; padding: 8px; font-weight: bold; color: #666; width: 40%; }
        .info-value { display: table-cell; padding: 8px; color: #333; }
        .highlight-box { background: #f0f8ff; border: 2px solid #222F46; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .amount { font-size: 32px; color: #222F46; font-weight: bold; }
        .footer { background: #f5f5f5; padding: 20px; text-align: center; color: #666; font-size: 12px; }
        .action-required { background: #fff3cd; border-left: 4px solid #E39411; padding: 15px; margin: 20px 0; }
        .documents { background: #e3f2fd; padding: 15px; border-radius: 4px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>NOUVEAU DEVIS PROFESSIONNEL</h2>
            <p style="margin: 10px 0 0 0; font-size: 14px;">Reçu le <?php echo $timestamp ?? date('d/m/Y à H:i'); ?></p>
        </div>
        
        <div class="content">
            <!-- Montant en évidence -->
            <div class="highlight-box">
                <div style="color: #666; margin-bottom: 10px;">Estimation annuelle HTVA</div>
                <div class="amount"><?php echo number_format($annualHTVA, 0, ' ', ' '); ?> €</div>
                <div style="color: #666; margin-top: 10px;">
                    soit <?php echo number_format($totalTTC, 0, ' ', ' '); ?> € TTC avec TVA
                </div>
            </div>
            
            <!-- Informations entreprise -->
            <div class="section">
                <h3 style="margin-top: 0; color: #222F46;">🏢 Informations Entreprise</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Raison sociale :</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($entreprise['raison_sociale'] ?? ''); ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Forme juridique :</div>
                        <div class="info-value"><?php echo htmlspecialchars($entreprise['forme_juridique'] ?? ''); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">SIRET :</div>
                        <div class="info-value"><?php echo htmlspecialchars($entreprise['siret'] ?? ''); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Code NAF :</div>
                        <div class="info-value"><?php echo htmlspecialchars($entreprise['code_naf'] ?? ''); ?></div>
                    </div>
                    <?php if (!empty($entreprise['adresse'])): ?>
                    <div class="info-row">
                        <div class="info-label">Adresse :</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($entreprise['adresse']); ?><br>
                            <?php echo htmlspecialchars($entreprise['code_postal'] . ' ' . $entreprise['ville']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Contact responsable -->
            <div class="section">
                <h3 style="margin-top: 0; color: #222F46;">👤 Contact Responsable</h3>
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
                        <div class="info-value"><strong style="color: #E39411;"><?php echo htmlspecialchars($client['telephone'] ?? ''); ?></strong></div>
                    </div>
                </div>
            </div>
            
            <!-- Détails techniques -->
            <div class="section">
                <h3 style="margin-top: 0; color: #222F46;">⚡ Configuration Électrique</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Catégorie :</div>
                        <div class="info-value"><?php echo htmlspecialchars($simulation['categorie'] ?? ''); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Puissance souscrite :</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($simulation['puissance'] ?? ''); ?> kVA</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Consommation annuelle :</div>
                        <div class="info-value"><strong><?php echo number_format($simulation['consommation_annuelle'] ?? 0, 0, ' ', ' '); ?> kWh</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Type de contrat :</div>
                        <div class="info-value"><?php echo $simulation['type_contrat'] === 'principal' ? 'Contrat principal' : 'Site secondaire'; ?></div>
                    </div>
                    <?php if (!empty($simulation['tarif_choisi'])): ?>
                    <div class="info-row">
                        <div class="info-label">Tarif sélectionné :</div>
                        <div class="info-value"><span style="background: #222F46; color: white; padding: 2px 8px; border-radius: 3px;"><?php echo strtoupper($simulation['tarif_choisi']); ?></span></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($results['offre_selectionnee'])): ?>
            <!-- Offre sélectionnée -->
            <div class="section" style="border-left-color: #82C720;">
                <h3 style="margin-top: 0; color: #222F46;">🎯 Offre Sélectionnée</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Nom de l'offre :</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($results['offre_selectionnee']['name'] ?? ''); ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Montant HTVA :</div>
                        <div class="info-value"><?php echo number_format($results['offre_selectionnee']['totalHTVA'] ?? 0, 0, ' ', ' '); ?> €/an</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Montant TTC :</div>
                        <div class="info-value"><?php echo number_format($results['offre_selectionnee']['totalTTC'] ?? 0, 0, ' ', ' '); ?> €/an</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($documents)): ?>
            <!-- Documents fournis -->
            <div class="documents">
                <strong>📎 Documents fournis par l'entreprise :</strong>
                <ul style="margin: 10px 0;">
                    <?php foreach ($documents as $doc_key => $doc_info): ?>
                        <li><?php echo htmlspecialchars($doc_info['name'] ?? ucfirst(str_replace('_', ' ', $doc_key))); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- Action requise -->
            <div class="action-required">
                <h3 style="margin-top: 0; color: #f59e0b;">⚠️ ACTION REQUISE</h3>
                <ul style="margin: 10px 0;">
                    <li>Contacter l'entreprise sous <strong>48h </strong></li>
                    <li>Valider les informations techniques du devis</li>
                    <li>Proposer la finalisation du contrat</li>
                    <li>Planifier la mise en service (15 jours ouvrés)</li>
                    <li>Mettre à jour le CRM professionnel</li>
                </ul>
            </div>
            
            <p style="text-align: center; color: #666; margin-top: 30px;">
                📄 <strong>PDF complet en pièce jointe</strong> avec tous les détails du devis professionnel
            </p>
        </div>
        
        <div class="footer">
            <p>Email automatique - Système de devis professionnel GES Solutions<br>
            Ne pas répondre à cet email - Utiliser les coordonnées de l'entreprise</p>
        </div>
    </div>
</body>
</html>