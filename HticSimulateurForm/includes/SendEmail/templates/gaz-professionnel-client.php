<?php
/**
 * Template email pour client gaz professionnel
 * Fichier: includes/SendEmail/templates/gaz-professionnel-client.php
 * Design identique au résidentiel mais adapté aux données professionnelles
 */

// Variables adaptées pour le gaz professionnel
$annualCost = $data['annualCost'] ?? 0;
$isHighConsumption = $data['isHighConsumption'] ?? false;

if ($isHighConsumption) {
    $annualHTVA = 0; // Pas d'estimation pour grosse consommation
    $monthlyHTVA = 0;
} else {
    // Affichage uniquement HTVA sans TVA
    $annualHTVA = $annualCost;
    $monthlyHTVA = round($annualHTVA / 10);
}

$consommation = $data['annualConsumption'] ?? 0;
$company = $data['companyName'] ?? 'Votre entreprise';
$contact = ($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? '');
$gasType = $data['gasType'] ?? 'Gaz naturel';
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
        .company-info { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .footer { background: #222F46; color: white; padding: 30px; text-align: center; }
        .next-steps { background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .usage-badges { display: flex; flex-wrap: wrap; gap: 10px; margin: 15px 0; }
        .usage-badge { background: #ffebee; color: #c62828; padding: 8px 12px; border-radius: 20px; font-size: 14px; font-weight: 500; border: 1px solid #ffcdd2; }
        .high-consumption { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .gas-type-badge { background: #e3f2fd; color: #1976d2; padding: 6px 12px; border-radius: 15px; font-size: 14px; font-weight: 500; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if ($isHighConsumption): ?>
                <h1 style="margin: 0; font-size: 28px;">📞 Votre Demande de Devis Personnalisé</h1>
                <p style="margin: 15px 0 0; font-size: 16px; opacity: 0.9;">GES Solutions - Étude sur-mesure pour grande consommation</p>
            <?php else: ?>
                <h1 style="margin: 0; font-size: 28px;">🔥 Votre Devis Gaz Professionnel</h1>
                <p style="margin: 15px 0 0; font-size: 16px; opacity: 0.9;">GES Solutions - Votre expert gaz professionnel</p>
            <?php endif; ?>
        </div>
        
        <div class="content">
            <p style="font-size: 16px; line-height: 1.6;">
                <?php if (!empty($contact)): ?>
                    Madame, Monsieur <strong><?php echo htmlspecialchars($contact); ?></strong>,
                <?php else: ?>
                    Madame, Monsieur,
                <?php endif; ?>
            </p>
            
            <?php if ($isHighConsumption): ?>
                <p style="font-size: 16px; line-height: 1.6;">
                    Nous avons bien reçu votre demande de devis gaz professionnel pour <strong><?php echo htmlspecialchars($company); ?></strong>. 
                    Avec une consommation prévisionnelle de <strong><?php echo number_format($consommation, 0, ' ', ' '); ?> kWh/an</strong>, 
                    votre entreprise nécessite une étude personnalisée pour optimiser vos coûts énergétiques.
                </p>
            <?php else: ?>
                <p style="font-size: 16px; line-height: 1.6;">
                    Nous avons le plaisir de vous adresser votre devis personnalisé pour l'alimentation en gaz de votre entreprise 
                    <strong><?php echo htmlspecialchars($company); ?></strong>.
                </p>
            <?php endif; ?>
            
            <div class="highlight-box <?php echo $isHighConsumption ? 'high-consumption' : ''; ?>">
                <?php if ($isHighConsumption): ?>
                    <h2 style="color: #856404; margin-top: 0;">⚡ Grande Consommation Détectée</h2>
                    <div style="color: #666; margin-bottom: 10px;">Consommation prévisionnelle</div>
                    <div style="font-size: 24px; color: #856404; font-weight: bold; margin: 10px 0;">
                        <?php echo number_format($consommation, 0, ' ', ' '); ?> kWh/an
                    </div>
                    <div style="font-size: 18px; color: #856404; font-weight: bold; margin: 15px 0;">
                        Devis personnalisé requis
                    </div>
                    <p style="color: #666; font-size: 14px;">Notre équipe vous contactera sous 48h pour une étude sur-mesure</p>
                <?php else: ?>
                    <h2 style="color: #ff6b35; margin-top: 0;">🔥 Votre Devis Gaz Professionnel</h2>
                    <div style="color: #666; margin-bottom: 10px;">Consommation annuelle estimée</div>
                    <div style="font-size: 24px; color: #ff6b35; font-weight: bold; margin: 10px 0;">
                        <?php echo number_format($consommation, 0, ' ', ' '); ?> kWh/an
                    </div>
                    <div class="amount"><?php echo number_format($annualHTVA, 0, ' ', ' '); ?> € HTVA/an</div>
                    <p style="font-size: 18px; margin: 15px 0;">
                        Soit <strong><?php echo number_format($monthlyHTVA, 0, ' ', ' '); ?> €/mois HTVA</strong> (sur 10 mois)
                    </p>
                    <p style="color: #666; font-size: 14px;">
                        Tarifs professionnels hors TVA, incluant taxes et abonnement<br>
                        + TVA 20% en sus selon réglementation
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="company-info">
                <h3 style="color: #ff6b35; margin-top: 0;">🏢 Votre Entreprise</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <strong>Entreprise :</strong> <?php echo htmlspecialchars($company); ?>
                    </div>
                    <?php if (!empty($data['legalForm'])): ?>
                    <div>
                        <strong>Forme juridique :</strong> <?php echo htmlspecialchars($data['legalForm']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($data['commune'])): ?>
                    <div>
                        <strong>Commune :</strong> <?php echo htmlspecialchars($data['commune']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($data['contractType'])): ?>
                    <div>
                        <strong>Type de contrat :</strong> <?php echo $data['contractType'] === 'principal' ? 'Contrat principal' : 'Site secondaire'; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($data['selectedTariff'])): ?>
                <div style="margin-top: 15px;">
                    <div class="usage-badges">
                        <span class="usage-badge">🔥 <?php echo htmlspecialchars($data['selectedTariff']); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="next-steps">
                <?php if ($isHighConsumption): ?>
                    <h3 style="color: #82C720; margin-top: 0;">📞 Prochaines étapes</h3>
                    <ul style="margin: 15px 0; line-height: 1.8;">
                        <li>Notre expert vous contactera sous <strong>48h</strong></li>
                        <li>Analyse détaillée de vos besoins spécifiques</li>
                        <li>Négociation des meilleures conditions tarifaires</li>
                        <li>Proposition commerciale personnalisée</li>
                        <li>Accompagnement dédié pour votre projet</li>
                    </ul>
                <?php else: ?>
                    <h3 style="color: #82C720; margin-top: 0;">🚀 Prochaines étapes</h3>
                    <ul style="margin: 15px 0; line-height: 1.8;">
                        <li>Notre conseiller commercial vous contactera sous <strong>72h</strong></li>
                        <li>Vérification de votre éligibilité technique</li>
                        <li>Finalisation de votre contrat gaz professionnel</li>
                        <li>Mise en service sous 5 jours ouvrés</li>
                        <li>Accompagnement personnalisé</li>
                    </ul>
                <?php endif; ?>
            </div>
            
            <?php if ($isHighConsumption): ?>
                <p style="font-size: 14px; color: #666; line-height: 1.6; margin-top: 30px;">
                    Votre demande a été transmise à notre équipe commerciale spécialisée. Un expert vous contactera rapidement 
                    pour analyser vos besoins et vous proposer les meilleures conditions du marché.<br>
                    Cette étude personnalisée est <strong>gratuite et sans engagement</strong>.
                </p>
            <?php else: ?>
                <p style="font-size: 14px; color: #666; line-height: 1.6; margin-top: 30px;">
                    Vous trouverez le devis complet en pièce jointe. Cette proposition est valable 30 jours.<br>
                    Pour toute question, notre équipe commerciale reste à votre disposition.
                </p>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <h3 style="margin: 0 0 15px;">Contact Commercial</h3>
            <p style="margin: 5px 0;">📧 commercial@ges-solutions.fr</p>
            <p style="margin: 5px 0;">📞 05 58 74 06 50</p>
            <p style="margin: 15px 0 0; font-size: 12px; opacity: 0.8;">
                GES Solutions - Votre partenaire gaz professionnel
            </p>
        </div>
    </div>
</body>
</html>