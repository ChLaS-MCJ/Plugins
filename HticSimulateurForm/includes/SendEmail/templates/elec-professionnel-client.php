<?php
/**
 * Template email pour client professionnel - électricité
 * Fichier: includes/SendEmail/templates/elec-professionnel.php
 */

$annualHTVA = $results['estimation_annuelle'] ?? 0;
$monthlyHTVA = round($annualHTVA / 10);
$tva = round($annualHTVA * 0.2);
$totalTTC = $annualHTVA + $tva;
?>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #222F46, #222F46); color: white; padding: 40px 30px; text-align: center; }
        .content { padding: 40px 30px; }
        .highlight-box { background: #f0f8ff; border: 2px solid #222F46; padding: 25px; border-radius: 8px; margin: 25px 0; text-align: center; }
        .amount { font-size: 36px; color: #222F46; font-weight: bold; margin: 10px 0; }
        .company-info { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .footer { background: #222F46; color: white; padding: 30px; text-align: center; }
        .next-steps { background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">Votre Devis Électricité Professionnel</h1>
            <p style="margin: 15px 0 0; font-size: 16px; opacity: 0.9;">GES Solutions - Expert en énergie pour les entreprises</p>
        </div>
        
        <div class="content">
            <p style="font-size: 16px; line-height: 1.6;">
                Madame, Monsieur <strong><?php echo htmlspecialchars($client['nom'] ?? ''); ?></strong>,
            </p>
            
            <p style="font-size: 16px; line-height: 1.6;">
                Nous avons le plaisir de vous adresser votre devis personnalisé pour l'électricité de votre entreprise <strong><?php echo htmlspecialchars($entreprise['raison_sociale'] ?? ''); ?></strong>.
            </p>
            
            <div class="highlight-box">
                <h2 style="color: #222F46; margin-top: 0;">💰 Votre Estimation</h2>
                <div class="amount"><?php echo number_format($annualHTVA, 0, ' ', ' '); ?> € HTVA/an</div>
                <p style="font-size: 18px; margin: 15px 0;">
                    Soit <strong><?php echo number_format($monthlyHTVA, 0, ' ', ' '); ?> €/mois HTVA</strong> (sur 10 mois)
                </p>
                <p style="color: #666; margin: 10px 0;">
                    + TVA 20% = <strong><?php echo number_format($totalTTC, 0, ' ', ' '); ?> € TTC/an</strong>
                </p>
            </div>
            
            <?php if (!empty($results['offre_selectionnee'])): ?>
            <div class="company-info">
                <h3 style="color: #222F46; margin-top: 0;">⚡ Offre Sélectionnée</h3>
                <p><strong><?php echo htmlspecialchars($results['offre_selectionnee']['name'] ?? ''); ?></strong></p>
                <?php if (!empty($results['offre_selectionnee']['details'])): ?>
                    <p style="color: #666;"><?php echo htmlspecialchars($results['offre_selectionnee']['details']); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="next-steps">
                <h3 style="color: #82C720; margin-top: 0;">🚀 Prochaines étapes</h3>
                <ul style="margin: 15px 0; line-height: 1.8;">
                    <li>Notre conseiller professionnel vous contactera sous <strong>48h</strong></li>
                    <li>Finalisation de votre offre personnalisée</li>
                    <li>Mise en service sous 15 jours ouvrés</li>
                    <li>Accompagnement dédié tout au long du processus</li>
                </ul>
            </div>
            
            <p style="font-size: 14px; color: #666; line-height: 1.6; margin-top: 30px;">
                Vous trouverez le devis complet en pièce jointe. Ce devis est valable 30 jours.<br>
                Pour toute question, notre équipe commerciale reste à votre disposition.
            </p>
        </div>
        
        <div class="footer">
            <h3 style="margin: 0 0 15px;">Contact Commercial</h3>
            <p style="margin: 5px 0;">📧 commercial@ges-solutions.fr</p>
            <p style="margin: 5px 0;">📞 01 23 45 67 89</p>
            <p style="margin: 15px 0 0; font-size: 12px; opacity: 0.8;">
                GES Solutions - Votre partenaire énergie professionnel
            </p>
        </div>
    </div>
</body>
</html>