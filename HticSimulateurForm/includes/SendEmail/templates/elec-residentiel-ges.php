<?php
/**
 * Template email pour GES - simulation électricité résidentielle
 * Fichier: includes/SendEmail/templates/elec-residentiel-ges.php
 */

$priorite = $priorite ?? ['niveau' => 'NORMALE', 'couleur' => '#82C720'];
?>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #222F46; color: white; padding: 20px; text-align: center; }
        .header h2 { margin: 0; font-size: 24px; }
        .priority-badge { display: inline-block; background: <?php echo $priorite['couleur']; ?>; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold; margin-top: 10px; }
        .content { padding: 30px; }
        .section { background: #f9f9f9; border-left: 4px solid #222F46; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; padding: 8px; font-weight: bold; color: #666; width: 40%; }
        .info-value { display: table-cell; padding: 8px; color: #333; }
        .highlight-box { background: #e8f5e8; border: 2px solid #82C720; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .amount { font-size: 32px; color: #222F46; font-weight: bold; }
        .footer { background: #f5f5f5; padding: 20px; text-align: center; color: #666; font-size: 12px; }
        .action-required { background: #fff3cd; border-left: 4px solid #E39411; padding: 15px; margin: 20px 0; }
        .documents { background: #e3f2fd; padding: 10px; border-radius: 4px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>NOUVELLE SIMULATION</h2>
            <div class="priority-badge">Priorité <?php echo $priorite['niveau']; ?></div>
            <p style="margin: 10px 0 0 0; font-size: 14px;">Reçue le <?php echo $timestamp; ?></p>
        </div>
        
        <div class="content">
            <!-- Montant principal en évidence -->
            <div class="highlight-box" style="text-align: center;">
                <div style="color: #666; margin-bottom: 10px;">Estimation mensuelle client</div>
                <div class="amount"><?php echo number_format($results['estimation_mensuelle'], 0, ',', ' '); ?> € TTC</div>
                <div style="color: #666; margin-top: 5px;">soit <?php echo number_format($results['estimation_mensuelle'] * 12, 0, ',', ' '); ?> €/an</div>
            </div>
            
            <!-- Informations client -->
            <div class="section">
                <h3 style="margin-top: 0; color: #222F46;">👤 Informations Client</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Nom complet :</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars(($client['prenom'] ?? '') . ' ' . strtoupper($client['nom'] ?? '')); ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email :</div>
                        <div class="info-value"><a href="mailto:<?php echo htmlspecialchars($client['email']); ?>"><?php echo htmlspecialchars($client['email']); ?></a></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Téléphone :</div>
                        <div class="info-value"><strong style="color: #E39411;"><?php echo htmlspecialchars($client['telephone']); ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Code postal :</div>
                        <div class="info-value"><?php echo htmlspecialchars($simulation['code_postal']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Détails simulation -->
            <div class="section">
                <h3 style="margin-top: 0; color: #222F46;">📊 Détails de la Simulation</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Type logement :</div>
                        <div class="info-value"><?php echo ucfirst($simulation['type_logement'] ?? 'Non spécifié'); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Surface :</div>
                        <div class="info-value"><?php echo ($simulation['surface'] ?? 'N/A') . ' m²'; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Nb occupants :</div>
                        <div class="info-value"><?php echo ($simulation['nb_personnes'] ?? 'N/A') . ' personnes'; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Consommation :</div>
                        <div class="info-value"><strong><?php echo number_format($results['consommation_annuelle'], 0, ' ', ' '); ?> kWh/an</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Puissance :</div>
                        <div class="info-value"><?php echo ($results['puissance_recommandee'] ?? 'N/A') . ' kVA'; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Option tarifaire :</div>
                        <div class="info-value"><span style="background: #222F46; color: white; padding: 2px 8px; border-radius: 3px;"><?php echo strtoupper($results['tarif_choisi'] ?? 'BASE'); ?></span></div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($documents)): ?>
            <!-- Documents joints -->
            <div class="documents">
                <strong>📎 Documents fournis par le client :</strong>
                <ul style="margin: 5px 0;">
                    <?php foreach ($documents as $file_key => $file_info): ?>
                        <li><?php echo htmlspecialchars($file_info['name'] ?? ucfirst(str_replace('_', ' ', $file_key))); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- Action requise -->
            <div class="action-required">
                <h3 style="margin-top: 0; color: #E39411;">⚠️ ACTION REQUISE</h3>
                <ul style="margin: 10px 0;">
                    <li>Contacter le client <strong>sous 72h maximum</strong></li>
                    <li>Valider les informations de la simulation</li>
                    <li>Proposer les offres adaptées</li>
                    <li>Planifier la mise en service si accord</li>
                </ul>
            </div>
            
            <p style="text-align: center; color: #666; margin-top: 30px;">
                📄 <strong>PDF complet en pièce jointe</strong> avec tous les détails de la simulation
            </p>
        </div>
        
        <div class="footer">
            <p>Email automatique - Système de simulation GES Solutions</p>
        </div>
    </div>
</body>
</html>