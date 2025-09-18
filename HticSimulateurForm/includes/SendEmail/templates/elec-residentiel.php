<?php
/**
 *  Template Elec residentiel :
 * 
 */

require_once plugin_dir_path(__FILE__) . 'base-template.php';

$title = 'Votre simulation électricité résidentielle';
$results = $data['results'];
$simulation = $data['simulation'];

// Contenu spécifique électricité résidentiel
ob_start();
?>
<p>Voici les résultats de votre simulation électricité personnalisée :</p>

<div class="result-box">
    <h3>📊 Résultats de votre simulation</h3>
    
    <?php if (isset($results['consommation_annuelle'])): ?>
    <p><strong>Consommation annuelle estimée :</strong> 
       <span class="highlight"><?php echo number_format($results['consommation_annuelle']); ?> kWh/an</span>
    </p>
    <?php endif; ?>
    
    <?php if (isset($results['puissance_recommandee'])): ?>
    <p><strong>Puissance recommandée :</strong> 
       <span class="highlight"><?php echo $results['puissance_recommandee']; ?> kVA</span>
    </p>
    <?php endif; ?>
</div>

<?php if (isset($results['tarifs']) && is_array($results['tarifs'])): ?>
<div class="result-box">
    <h3>💰 Comparaison des tarifs</h3>
    <table>
        <thead>
            <tr><th>Tarif</th><th>Coût annuel</th><th>Coût mensuel</th></tr>
        </thead>
        <tbody>
        <?php foreach ($results['tarifs'] as $type => $tarif): ?>
            <?php if (is_array($tarif) && isset($tarif['total_annuel'])): ?>
            <tr>
                <td><?php echo ucfirst($type); ?></td>
                <td><?php echo number_format($tarif['total_annuel']); ?>€</td>
                <td><?php echo number_format($tarif['total_annuel']/12); ?>€</td>
            </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="result-box">
    <h3>🏠 Vos informations de logement</h3>
    <table>
        <tr><th>Type de logement</th><td><?php echo esc_html($simulation['type_logement'] ?? 'Non spécifié'); ?></td></tr>
        <tr><th>Surface</th><td><?php echo esc_html($simulation['surface'] ?? 'Non spécifié'); ?> m²</td></tr>
        <tr><th>Nombre de personnes</th><td><?php echo esc_html($simulation['nb_personnes'] ?? 'Non spécifié'); ?></td></tr>
        <tr><th>Isolation</th><td><?php echo esc_html($simulation['isolation'] ?? 'Non spécifié'); ?></td></tr>
        <tr><th>Type de chauffage</th><td><?php echo esc_html($simulation['type_chauffage'] ?? 'Non spécifié'); ?></td></tr>
    </table>
</div>
<?php
$content = ob_get_clean();

// Générer l'email complet avec le template de base
echo render_email_base($title, $content, $client);
