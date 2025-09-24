<?php
/**
 * Template email pour client gaz résidentiel
 * Fichier: includes/SendEmail/templates/gaz-residentiel-client.php
 */

// Variables corrigées pour le gaz résidentiel
$annualTTC = $results['cout_annuel_ttc'] ?? $data['annualCost'] ?? 0;
$monthlyTTC = round($annualTTC / 10);
$consommation = $results['consommation_annuelle'] ?? $data['annualConsumption'] ?? 0;
?>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #222F46, #222F46); color: white; padding: 40px 30px; text-align: center; }
        .content { padding: 40px 30px; }
        .highlight-box { background: #fff3e0; border: 2px solid #ff6b35; padding: 25px; border-radius: 8px; margin: 25px 0; text-align: center; }
        .amount { font-size: 36px; color: #ff6b35; font-weight: bold; margin: 10px 0; }
        .housing-info { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .footer { background: #222F46; color: white; padding: 30px; text-align: center; }
        .next-steps { background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .usage-badges { display: flex; flex-wrap: wrap; gap: 10px; margin: 15px 0; }
        .usage-badge { background: #ffebee; color: #c62828; padding: 8px 12px; border-radius: 20px; font-size: 14px; font-weight: 500; border: 1px solid #ffcdd2; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">🔥 Votre Simulation Gaz Personnalisée</h1>
            <p style="margin: 15px 0 0; font-size: 16px; opacity: 0.9;">GES Solutions - Votre expert gaz résidentiel</p>
        </div>
        
        <div class="content">
            <p style="font-size: 16px; line-height: 1.6;">
                <?php if (!empty($client['prenom']) && !empty($client['nom'])): ?>
                    Madame, Monsieur <strong><?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?></strong>,
                <?php else: ?>
                    Madame, Monsieur,
                <?php endif; ?>
            </p>
            
            <p style="font-size: 16px; line-height: 1.6;">
                Nous avons le plaisir de vous adresser votre simulation personnalisée pour l'alimentation en gaz de votre logement.
            </p>
            
            <div class="highlight-box">
                <h2 style="color: #ff6b35; margin-top: 0;">🔥 Votre Estimation Gaz</h2>
                <div style="color: #666; margin-bottom: 10px;">Consommation annuelle estimée</div>
                <div style="font-size: 24px; color: #ff6b35; font-weight: bold; margin: 10px 0;">
                    <?php echo number_format($consommation, 0, ' ', ' '); ?> kWh/an
                </div>
                <div class="amount"><?php echo number_format($annualTTC, 0, ' ', ' '); ?> € TTC/an</div>
                <p style="font-size: 18px; margin: 15px 0;">
                    Soit <strong><?php echo number_format($monthlyTTC, 0, ' ', ' '); ?> €/mois TTC</strong> (sur 10 mois)
                </p>
                <p style="color: #666; font-size: 14px;">Tarifs tout compris incluant taxes et abonnement</p>
            </div>
            
            <div class="housing-info">
                <h3 style="color: #ff6b35; margin-top: 0;">🏠 Votre Logement</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <?php if (!empty($data['surface'])): ?>
                    <div>
                        <strong>Surface :</strong> <?php echo htmlspecialchars($data['surface']); ?> m²
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($data['residents'])): ?>
                    <div>
                        <strong>Occupants :</strong> <?php echo htmlspecialchars($data['residents']); ?> personne<?php echo $data['residents'] > 1 ? 's' : ''; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($data['commune'])): ?>
                    <div>
                        <strong>Commune :</strong> <?php echo htmlspecialchars($data['commune']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($data['housingType'])): ?>
                    <div>
                        <strong>Type :</strong> <?php echo $data['housingType'] === 'maison' ? 'Maison' : 'Appartement'; ?>
                    </div>
                    <?php endif; ?>
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
            
            <div class="next-steps">
                <h3 style="color: #82C720; margin-top: 0;">🚀 Prochaines étapes</h3>
                <ul style="margin: 15px 0; line-height: 1.8;">
                    <li>Notre conseiller vous contactera sous <strong>72h</strong></li>
                    <li>Vérification de votre éligibilité</li>
                    <li>Finalisation de votre contrat gaz</li>
                    <li>Mise en service sous 5 jours ouvrés</li>
                    <li>Accompagnement personnalisé</li>
                </ul>
            </div>
            
            <p style="font-size: 14px; color: #666; line-height: 1.6; margin-top: 30px;">
                Vous trouverez le récapitulatif complet en pièce jointe. Cette simulation est valable 30 jours.<br>
                Pour toute question, notre équipe reste à votre disposition.
            </p>
        </div>
        
        <div class="footer">
            <h3 style="margin: 0 0 15px;">Contact</h3>
            <p style="margin: 5px 0;">📧 contact@ges-solutions.fr</p>
            <p style="margin: 5px 0;">📞 01 23 45 67 89</p>
            <p style="margin: 15px 0 0; font-size: 12px; opacity: 0.8;">
                GES Solutions - Votre expert gaz naturel et propane
            </p>
        </div>
    </div>
</body>
</html>