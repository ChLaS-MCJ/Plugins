<?php
/**
 * Base Template Email - Version corrigée
 * includes/SendEmail/templates/base-template.php
 */

function render_email_base($title, $content, $client, $isGES = false) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo esc_html($title); ?></title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background-color: #f4f4f4;
            }
            .container { 
                max-width: 1000px; 
                margin: 0 auto; 
                background-color: white;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            .header { 
                background: linear-gradient(135deg, #222F46 0%, #57709d 100%);
                color: white; 
                padding: 30px 20px; 
                text-align: center; 
            }
            .header h1 { 
                margin: 0; 
                font-size: 24px; 
                font-weight: 600;
            }
            .header p {
                margin: 10px 0 0 0;
                font-size: 16px;
                opacity: 0.9;
            }
            .content { 
                padding: 30px 20px; 
                background: white; 
            }
            .result-box { 
                background: #f8f9fa; 
                padding: 20px; 
                margin: 15px 0; 
                border-radius: 8px; 
                border-left: 4px solid #667eea;
            }
            .result-box h3 { 
                color: #667eea; 
                margin-top: 0; 
                font-size: 18px;
                font-weight: 600;
            }
            .footer { 
                text-align: center; 
                padding: 20px; 
                font-size: 12px; 
                color: #666; 
                background: #f8f9fa; 
                border-top: 1px solid #eee;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 15px 0; 
            }
            th, td { 
                padding: 12px 8px; 
                text-align: left; 
                border-bottom: 1px solid #eee; 
            }
            th { 
                background-color: #f1f3f4; 
                font-weight: 600; 
                color: #444;
            }
            .highlight { 
                color: #667eea; 
                font-weight: 600; 
            }
            ul {
                padding-left: 20px;
            }
            li {
                margin-bottom: 5px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1><?php echo $isGES ? 'GES Solutions - Administration' : 'GES Solutions'; ?></h1>
                <p><?php echo esc_html($title); ?></p>
            </div>
            
            <div class="content">
                <?php if (!$isGES && !empty($client['prenom'])): ?>
                <h2>Bonjour <?php echo esc_html($client['prenom'] . ' ' . $client['nom']); ?>,</h2>
                <?php endif; ?>
                
                <?php echo $content; ?>
                
                <?php if (!$isGES): ?>
                <p style="margin-top: 30px;">Cette simulation a été générée le <?php echo date('d/m/Y à H:i'); ?>.</p>
                <p>Pour toute question, n'hésitez pas à nous contacter.</p>
                <?php endif; ?>
            </div>
            
            <div class="footer">
                <p><strong>GES Solutions</strong> - Votre partenaire énergie</p>
                <p>Email généré automatiquement - Ne pas répondre</p>
                <?php if (function_exists('home_url')): ?>
                <p><a href="<?php echo home_url(); ?>" style="color: #667eea;"><?php echo home_url(); ?></a></p>
                <?php endif; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>