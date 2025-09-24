<?php
/**
 * Dashboard moderne pour la gestion des simulations
 * Fichier: admin/simulations-dashboard.php
 * Version: 2.2 
 */

// Sécurité WordPress
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tables avec vérification d'existence
$table_elec_res = $wpdb->prefix . 'simulations_electricite';
$table_elec_pro = $wpdb->prefix . 'simulations_electricite_pro';
$table_gaz_res = $wpdb->prefix . 'simulations_gaz';
$table_gaz_pro = $wpdb->prefix . 'simulations_gaz_pro';

// Fonction pour vérifier si une table existe
function table_exists($table_name) {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
}

// Fonction sécurisée pour récupérer les simulations
function get_simulations_safe($table_name) {
    global $wpdb;
    
    if (!table_exists($table_name)) {
        return [];
    }
    
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 100");
    
    if ($wpdb->last_error) {
        return [];
    }
    
    return is_array($results) ? $results : [];
}

// Configuration des onglets
$tab_configs = [
    'elec-res' => [
        'active' => true,
        'label' => 'électricité résidentielle'
    ],
    'elec-pro' => [
        'active' => false,
        'label' => 'électricité professionnelle'
    ],
    'gaz-res' => [
        'active' => false,
        'label' => 'gaz résidentiel'
    ],
    'gaz-pro' => [
        'active' => false,
        'label' => 'gaz professionnel'
    ]
];

// Statistiques sécurisées
function get_count_safe($table_name) {
    global $wpdb;
    if (!table_exists($table_name)) return 0;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    return (int) ($count ?? 0);
}

$stats = array(
    'elec_res' => get_count_safe($table_elec_res),
    'elec_pro' => get_count_safe($table_elec_pro),
    'gaz_res' => get_count_safe($table_gaz_res),
    'gaz_pro' => get_count_safe($table_gaz_pro)
);

// Calculs dérivés
$stats['today'] = 0;
$stats['pending'] = 0;

foreach ([$table_elec_res, $table_elec_pro, $table_gaz_res, $table_gaz_pro] as $table) {
    if (table_exists($table)) {
        $today_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE DATE(created_at) = CURDATE()");
        $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'non_traite'");
        
        $stats['today'] += (int) ($today_count ?? 0);
        $stats['pending'] += (int) ($pending_count ?? 0);
    }
}

// Traitement des actions
if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'simulation_action')) {
    $sim_id = intval($_POST['sim_id']);
    $table = sanitize_text_field($_POST['table_name']);
    
    // Vérifier que la table existe avant de faire des opérations
    if (table_exists($table)) {
        switch($_POST['action']) {
            case 'update_status':
                $status = sanitize_text_field($_POST['status']);
                $wpdb->update($table, array('status' => $status), array('id' => $sim_id));
                echo '<div class="notice notice-success is-dismissible"><p>Statut mis à jour avec succès!</p></div>';
                break;
            case 'delete':
                $wpdb->delete($table, array('id' => $sim_id));
                echo '<div class="notice notice-success is-dismissible"><p>Simulation supprimée!</p></div>';
                break;
        }
    }
}

// Récupération sécurisée des simulations
$elec_res_sims = get_simulations_safe($table_elec_res);
$elec_pro_sims = get_simulations_safe($table_elec_pro);
$gaz_res_sims = get_simulations_safe($table_gaz_res);
$gaz_pro_sims = get_simulations_safe($table_gaz_pro);

// Variables pour les badges et labels
$badges = [
    'non_traite' => 'badge-pending',
    'en_cours' => 'badge-processing',
    'traite' => 'badge-completed'
];

$labels = [
    'non_traite' => 'En attente',
    'en_cours' => 'En cours',
    'traite' => 'Traité'
];

/**
 * Fonction  pour générer un onglet de simulation
 */
function render_simulation_tab($type, $simulations, $config, $table_name) {
    global $badges, $labels;
    
    // Triple vérification de sécurité
    if (!is_array($simulations)) {
        $simulations = [];
    }
    
    if (!is_array($config)) {
        $config = ['active' => false, 'label' => 'inconnue'];
    }
    
    $simulation_count = count($simulations);
    
    // Définir les colonnes selon le type
    $is_pro = strpos($type, 'pro') !== false;
    $is_gaz = strpos($type, 'gaz') !== false;
    $is_elec_pro = ($type === 'elec-pro');
    $is_gaz_res = ($type === 'gaz-res');
    $is_gaz_pro = ($type === 'gaz-pro');
    
    ?>
    <div class="tab-pane <?php echo !empty($config['active']) ? 'active' : ''; ?>" id="tab-<?php echo esc_attr($type); ?>">
        <!-- Système de recherche moderne -->
        <div class="search-section">
            <div class="search-header">
                <div class="search-title">
                    <span>Recherche avancée</span>
                </div>
                <div class="search-stats">
                    <div class="search-stat">
                        <span>Total:</span>
                        <span class="search-stat-value"><?php echo $simulation_count; ?></span>
                    </div>
                    <div class="search-stat">
                        <span>Affichés:</span>
                        <span class="search-stat-value" id="visible-count-<?php echo esc_attr($type); ?>"><?php echo $simulation_count; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="search-wrapper">
                <div class="search-input-group">
                    <input type="text" 
                           class="search-input" 
                           id="search-<?php echo esc_attr($type); ?>" 
                           placeholder="Rechercher par nom, email, téléphone, code postal..."
                           data-table="<?php echo esc_attr($type); ?>-table">
                    <button class="search-clear" id="clear-<?php echo esc_attr($type); ?>" onclick="clearSearch('<?php echo esc_js($type); ?>')">×</button>
                </div>
                
                <div class="date-filter-group">
                    <input type="date" 
                           class="date-input" 
                           id="date-start-<?php echo esc_attr($type); ?>" 
                           placeholder="Date début"
                           onchange="filterByDate('<?php echo esc_js($type); ?>')">
                    <span class="date-separator">→</span>
                    <input type="date" 
                           class="date-input" 
                           id="date-end-<?php echo esc_attr($type); ?>" 
                           placeholder="Date fin"
                           onchange="filterByDate('<?php echo esc_js($type); ?>')">
                </div>
            </div>
            
            <div class="search-filters">
                <button class="filter-button" onclick="toggleFilter('<?php echo esc_js($type); ?>', 'status', 'non_traite', this)">
                    <span>⏳</span>
                    <span>Non traités</span>
                </button>
                <button class="filter-button" onclick="toggleFilter('<?php echo esc_js($type); ?>', 'status', 'en_cours', this)">
                    <span>⚙️</span>
                    <span>En cours</span>
                </button>
                <button class="filter-button" onclick="toggleFilter('<?php echo esc_js($type); ?>', 'status', 'traite', this)">
                    <span>✅</span>
                    <span>Traités</span>
                </button>
                <button class="filter-button" onclick="resetFilters('<?php echo esc_js($type); ?>')">
                    <span>🔄</span>
                    <span>Réinitialiser</span>
                </button>
            </div>
        </div>
        
        <?php if (empty($simulations)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <div class="empty-title">Aucune simulation</div>
                <div class="empty-text">
                    <?php if (!table_exists($table_name)): ?>
                        Table de base de données non trouvée (<?php echo esc_html($table_name ? basename($table_name) : 'inconnue'); ?>)
                    <?php else: ?>
                        Aucune simulation <?php echo esc_html($config['label'] ?? 'inconnue'); ?> pour le moment
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <table class="modern-table" id="<?php echo esc_attr($type); ?>-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th><?php echo $is_pro ? 'Entreprise' : 'Client'; ?></th>
                        <th>Contact</th>
                        <?php 
                        // Conditionnellement afficher les colonnes selon le type
                        if (!$is_elec_pro && !$is_gaz_res && !$is_gaz_pro): ?>
                            <th>Détails</th>
                        <?php endif; ?>
                        <th>Estimation</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($simulations as $sim): ?>
                        <tr data-date="<?php echo esc_attr($sim->created_at ?? ''); ?>" data-status="<?php echo esc_attr($sim->status ?? 'non_traite'); ?>">
                            <td><?php echo esc_html(date('d/m/Y H:i', strtotime($sim->created_at ?? 'now'))); ?></td>
                            
                            <!-- Colonne Client/Entreprise -->
                            <td>
                                <div class="client-info">
                                    <?php if ($is_pro): ?>
                                        <span class="client-name"><?php echo esc_html($sim->company_name ?? 'N/A'); ?></span>
                                        <span class="client-email"><?php echo esc_html($sim->contact_email ?? $sim->email ?? ''); ?></span>
                                    <?php else: ?>
                                        <span class="client-name"><?php echo esc_html(($sim->first_name ?? '') . ' ' . ($sim->last_name ?? '')); ?></span>
                                        <span class="client-email"><?php echo esc_html($sim->email ?? ''); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <!-- Colonne Contact -->
                            <td>
                                <?php if ($is_elec_pro || $is_gaz_pro): ?>
                                    <!-- Pour les pros : afficher Email Pro -->
                                    <?php echo esc_html($sim->contact_email ?? $sim->email ?? 'N/A'); ?>
                                <?php else: ?>
                                    <!-- Pour les particuliers : afficher téléphone -->
                                    <?php if (!empty($sim->phone)): ?>
                                        <a href="tel:<?php echo esc_attr($sim->phone); ?>" style="color: #667eea; text-decoration: none;">
                                            <?php echo esc_html($sim->phone); ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">N/A</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Colonne Détails (conditionnelle) -->
                            <?php if (!$is_elec_pro && !$is_gaz_res && !$is_gaz_pro): ?>
                                <td>
                                    <?php 
                                    $detail_text = 'N/A';
                                    if ($is_pro) {
                                        $detail_text = $sim->category ?? $sim->contract_type ?? 'N/A';
                                    } else {
                                        $detail_text = $sim->housing_type ?? 'N/A';
                                    }
                                    echo esc_html($detail_text);
                                    ?>
                                    <br>
                                    <small>
                                        <?php 
                                        if ($is_pro) {
                                            echo esc_html(($sim->company_postal_code ?? $sim->postal_code ?? 'N/A'));
                                        } else {
                                            echo esc_html(($sim->surface ?? 'N/A') . 'm² - ' . ($sim->postal_code ?? 'N/A'));
                                        }
                                        ?>
                                    </small>
                                </td>
                            <?php endif; ?>
                            
                            <!-- Colonne Estimation -->
                            <td>
                                <?php if (!$is_gaz_res): ?>
                                    <strong style="color: #10b981;">
                                        <?php 
                                        $estimate = 0;
                                        if ($is_pro) {
                                            $estimate = $sim->monthly_estimate ?? ($sim->annual_estimate ?? 0) / 12 ?? 0;
                                        } else {
                                            $estimate = $sim->monthly_estimate ?? $sim->estimation_mensuelle ?? 0;
                                        }
                                        echo number_format($estimate, 0, ',', ' '); 
                                        ?> €/mois
                                    </strong>
                                <?php else: ?>
                                    <!-- Pour gaz résidentiel : pas d'affichage N/A -->
                                    <strong style="color: #10b981;">
                                        <?php 
                                        $estimate = $sim->cout_mensuel ?? ($sim->cout_annuel ?? 0) / 12 ?? 0;
                                        echo number_format($estimate, 0, ',', ' '); 
                                        ?> €/mois
                                    </strong>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Colonne Statut avec couleurs correctes -->
                            <td>
                                <?php 
                                $status = $sim->status ?? 'non_traite'; 
                                $status_class = '';
                                $status_label = '';
                                
                                switch($status) {
                                    case 'non_traite':
                                        $status_class = 'badge-pending';
                                        $status_label = 'En attente';
                                        break;
                                    case 'en_cours':
                                        $status_class = 'badge-processing';
                                        $status_label = 'En cours';
                                        break;
                                    case 'traite':
                                        $status_class = 'badge-completed';
                                        $status_label = 'Traité';
                                        break;
                                    default:
                                        $status_class = 'badge-pending';
                                        $status_label = ucfirst($status);
                                }
                                ?>
                                <span class="badge <?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html($status_label); ?>
                                </span>
                            </td>
                            
                            <!-- Colonne Actions -->
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-view" onclick="showDetails('<?php echo esc_js(str_replace('-', '_', $type)); ?>', <?php echo intval($sim->id ?? 0); ?>)">
                                        👁️ Détails
                                    </button>
                                    
                                    <form method="post" style="display: inline-block;">
                                        <?php wp_nonce_field('simulation_action'); ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="sim_id" value="<?php echo intval($sim->id ?? 0); ?>">
                                        <input type="hidden" name="table_name" value="<?php echo esc_attr($table_name); ?>">
                                        <select name="status" onchange="this.form.submit()" class="btn-action btn-status">
                                            <option value="non_traite" <?php selected($status, 'non_traite'); ?>>⏳</option>
                                            <option value="en_cours" <?php selected($status, 'en_cours'); ?>>⚙️</option>
                                            <option value="traite" <?php selected($status, 'traite'); ?>>✅</option>
                                        </select>
                                    </form>
                                    
                                    <form method="post" style="display: inline-block;" onsubmit="return confirm('Supprimer cette simulation ?');">
                                        <?php wp_nonce_field('simulation_action'); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="sim_id" value="<?php echo intval($sim->id ?? 0); ?>">
                                        <input type="hidden" name="table_name" value="<?php echo esc_attr($table_name); ?>">
                                        <button type="submit" class="btn-action btn-delete">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Fonction pour rendre tous les onglets 
 */
function render_all_tabs() {
    global $elec_res_sims, $elec_pro_sims, $gaz_res_sims, $gaz_pro_sims;
    global $table_elec_res, $table_elec_pro, $table_gaz_res, $table_gaz_pro;
    
    // Configuration locale pour éviter les problèmes de scope
    $local_tab_configs = [
        'elec-res' => ['active' => true, 'label' => 'électricité résidentielle'],
        'elec-pro' => ['active' => false, 'label' => 'électricité professionnelle'],
        'gaz-res' => ['active' => false, 'label' => 'gaz résidentiel'],
        'gaz-pro' => ['active' => false, 'label' => 'gaz professionnel']
    ];
    
    // Vérifications de sécurité
    $elec_res_sims = is_array($elec_res_sims) ? $elec_res_sims : [];
    $elec_pro_sims = is_array($elec_pro_sims) ? $elec_pro_sims : [];
    $gaz_res_sims = is_array($gaz_res_sims) ? $gaz_res_sims : [];
    $gaz_pro_sims = is_array($gaz_pro_sims) ? $gaz_pro_sims : [];
    
    echo '<div class="tab-content">';
    
    // Électricité Résidentiel
    render_simulation_tab(
        'elec-res',
        $elec_res_sims,
        $local_tab_configs['elec-res'],
        $table_elec_res
    );
    
    // Électricité Professionnel  
    render_simulation_tab(
        'elec-pro',
        $elec_pro_sims,
        $local_tab_configs['elec-pro'],
        $table_elec_pro
    );
    
    // Gaz Résidentiel
    render_simulation_tab(
        'gaz-res',
        $gaz_res_sims,
        $local_tab_configs['gaz-res'],
        $table_gaz_res
    );
    
    // Gaz Professionnel
    render_simulation_tab(
        'gaz-pro',
        $gaz_pro_sims,
        $local_tab_configs['gaz-pro'],
        $table_gaz_pro
    );
    
    echo '</div>';
}

?>

<div class="wrap htic-dashboard">
    <!-- Header -->
    <div class="dashboard-header">
        <h1 class="dashboard-title">Dashboard des Simulations Énergie</h1>
        <p class="dashboard-subtitle">Gérez toutes vos simulations reçues en un seul endroit</p>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card elec">
            <div class="stat-number"><?php echo number_format($stats['elec_res'] + $stats['elec_pro']); ?></div>
            <div class="stat-label">⚡ Simulations Électricité</div>
        </div>
        
        <div class="stat-card gaz">
            <div class="stat-number"><?php echo number_format($stats['gaz_res'] + $stats['gaz_pro']); ?></div>
            <div class="stat-label">🔥 Simulations Gaz</div>
        </div>
        
        <div class="stat-card today">
            <div class="stat-number"><?php echo number_format($stats['today']); ?></div>
            <div class="stat-label">📅 Aujourd'hui</div>
        </div>
        
        <div class="stat-card pending">
            <div class="stat-number"><?php echo number_format($stats['pending']); ?></div>
            <div class="stat-label">⏳ En attente</div>
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="tabs-container">
        <div class="tabs-nav">
            <button class="tab-button active" data-tab="elec-res">
                <span class="tab-icon">🏠</span>
                Élec. Résidentiel
                <?php if($stats['elec_res'] > 0): ?>
                    <span style="background: #e5e7eb; padding: 2px 8px; border-radius: 10px; margin-left: 8px;">
                        <?php echo $stats['elec_res']; ?>
                    </span>
                <?php endif; ?>
            </button>
            
            <button class="tab-button" data-tab="elec-pro">
                <span class="tab-icon">🏢</span>
                Élec. Professionnel
                <?php if($stats['elec_pro'] > 0): ?>
                    <span style="background: #e5e7eb; padding: 2px 8px; border-radius: 10px; margin-left: 8px;">
                        <?php echo $stats['elec_pro']; ?>
                    </span>
                <?php endif; ?>
            </button>
            
            <button class="tab-button" data-tab="gaz-res">
                <span class="tab-icon">🏠</span>
                Gaz Résidentiel
                <?php if($stats['gaz_res'] > 0): ?>
                    <span style="background: #e5e7eb; padding: 2px 8px; border-radius: 10px; margin-left: 8px;">
                        <?php echo $stats['gaz_res']; ?>
                    </span>
                <?php endif; ?>
            </button>
            
            <button class="tab-button" data-tab="gaz-pro">
                <span class="tab-icon">🏢</span>
                Gaz Professionnel
                <?php if($stats['gaz_pro'] > 0): ?>
                    <span style="background: #e5e7eb; padding: 2px 8px; border-radius: 10px; margin-left: 8px;">
                        <?php echo $stats['gaz_pro']; ?>
                    </span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Remplacement de l'appel de fonction par du code direct -->
        <div class="tab-content">
            <?php
            // Configuration locale
            $local_configs = [
                'elec-res' => ['active' => true, 'label' => 'électricité résidentielle'],
                'elec-pro' => ['active' => false, 'label' => 'électricité professionnelle'],
                'gaz-res' => ['active' => false, 'label' => 'gaz résidentiel'],
                'gaz-pro' => ['active' => false, 'label' => 'gaz professionnel']
            ];
            
            // Appels directs sans fonction intermédiaire
            render_simulation_tab('elec-res', $elec_res_sims, $local_configs['elec-res'], $table_elec_res);
            render_simulation_tab('elec-pro', $elec_pro_sims, $local_configs['elec-pro'], $table_elec_pro);
            render_simulation_tab('gaz-res', $gaz_res_sims, $local_configs['gaz-res'], $table_gaz_res);
            render_simulation_tab('gaz-pro', $gaz_pro_sims, $local_configs['gaz-pro'], $table_gaz_pro);
            ?>
        </div>
    </div>
</div>

<!-- Modal pour les détails -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Détails de la simulation</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Le contenu sera injecté par AJAX -->
        </div>
    </div>
</div>

<script>
// ========================================
// SYSTÈME DE RECHERCHE ET FILTRAGE MODERNE
// ========================================

class DashboardManager {
    constructor() {
        this.activeFilters = {};
        this.searchTimers = {};
        this.init();
    }

    init() {
        this.initTabs();
        this.initSearch();
        this.initModal();
        this.initKeyboardShortcuts();
    }

    // Gestion des onglets
    initTabs() {
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const targetTab = e.currentTarget.getAttribute('data-tab');
                this.switchTab(targetTab);
            });
        });
    }

    switchTab(targetTab) {
        // Retirer active de tous les éléments
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
        
        // Activer le nouvel onglet
        const targetButton = document.querySelector(`[data-tab="${targetTab}"]`);
        const targetPane = document.getElementById(`tab-${targetTab}`);
        
        if (targetButton && targetPane) {
            targetButton.classList.add('active');
            targetPane.classList.add('active');
        }
        
        // Réinitialiser la recherche
        this.resetFilters(targetTab);
    }

    // Système de recherche
    initSearch() {
        document.querySelectorAll('.search-input').forEach(input => {
            const type = input.id.replace('search-', '');
            
            // Recherche avec debounce
            input.addEventListener('input', (e) => {
                clearTimeout(this.searchTimers[type]);
                
                // Afficher/masquer le bouton clear
                const clearBtn = document.getElementById(`clear-${type}`);
                if (clearBtn) {
                    clearBtn.classList.toggle('show', e.target.value.length > 0);
                }
                
                this.searchTimers[type] = setTimeout(() => {
                    this.performSearch(type);
                }, 300);
            });
        });
    }

    performSearch(type) {
        const searchInput = document.getElementById(`search-${type}`);
        if (!searchInput) return;
        
        const searchTerm = searchInput.value.toLowerCase();
        const table = document.getElementById(`${type}-table`);
        
        if (!table) return;
        
        const tbody = table.getElementsByTagName('tbody')[0];
        if (!tbody) return;
        
        const rows = tbody.getElementsByTagName('tr');
        let visibleCount = 0;
        
        Array.from(rows).forEach(row => {
            let showRow = true;
            
            // Recherche textuelle
            if (searchTerm) {
                const text = (row.textContent || row.innerText).toLowerCase();
                showRow = text.includes(searchTerm);
                
                if (showRow) {
                    this.highlightMatches(row, searchTerm);
                }
            } else {
                this.removeHighlights(row);
            }
            
            // Filtrage par date
            if (showRow) {
                showRow = this.checkDateFilter(row, type);
            }
            
            // Filtrage par statut
            if (showRow && this.activeFilters[type]?.status) {
                const rowStatus = row.getAttribute('data-status');
                showRow = this.activeFilters[type].status.includes(rowStatus);
            }
            
            // Afficher/masquer la ligne
            row.style.display = showRow ? '' : 'none';
            if (showRow) visibleCount++;
        });
        
        // Mettre à jour le compteur
        const visibleCountEl = document.getElementById(`visible-count-${type}`);
        if (visibleCountEl) {
            visibleCountEl.textContent = visibleCount;
        }
    }

    checkDateFilter(row, type) {
        const dateStartEl = document.getElementById(`date-start-${type}`);
        const dateEndEl = document.getElementById(`date-end-${type}`);
        
        if (!dateStartEl || !dateEndEl) return true;
        
        const dateStart = dateStartEl.value;
        const dateEnd = dateEndEl.value;
        
        if (!dateStart && !dateEnd) return true;
        
        const rowDate = row.getAttribute('data-date');
        if (!rowDate) return true;
        
        const date = new Date(rowDate);
        
        if (dateStart && date < new Date(dateStart + ' 00:00:00')) return false;
        if (dateEnd && date > new Date(dateEnd + ' 23:59:59')) return false;
        
        return true;
    }

    highlightMatches(element, searchTerm) {
        this.removeHighlights(element);
        
        if (!searchTerm) return;
        
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );
        
        const textNodes = [];
        let node;
        
        while (node = walker.nextNode()) {
            textNodes.push(node);
        }
        
        textNodes.forEach(node => {
            const text = node.nodeValue;
            const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            
            if (regex.test(text)) {
                const span = document.createElement('span');
                span.innerHTML = text.replace(regex, '<span class="highlight">$1</span>');
                node.parentNode.replaceChild(span, node);
            }
        });
    }

    removeHighlights(element) {
        element.querySelectorAll('.highlight').forEach(highlight => {
            const parent = highlight.parentNode;
            parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
            parent.normalize();
        });
    }

    // Gestion des filtres
    toggleFilter(type, filterType, value, button) {
        if (!this.activeFilters[type]) {
            this.activeFilters[type] = {};
        }
        
        if (!this.activeFilters[type][filterType]) {
            this.activeFilters[type][filterType] = [];
        }
        
        const index = this.activeFilters[type][filterType].indexOf(value);
        
        if (index > -1) {
            this.activeFilters[type][filterType].splice(index, 1);
            button.classList.remove('active');
        } else {
            this.activeFilters[type][filterType].push(value);
            button.classList.add('active');
        }
        
        this.performSearch(type);
    }

    resetFilters(type) {
        // Réinitialiser les filtres
        this.activeFilters[type] = {};
        
        // Réinitialiser l'UI
        const searchInput = document.getElementById(`search-${type}`);
        const dateStart = document.getElementById(`date-start-${type}`);
        const dateEnd = document.getElementById(`date-end-${type}`);
        const clearBtn = document.getElementById(`clear-${type}`);
        
        if (searchInput) searchInput.value = '';
        if (dateStart) dateStart.value = '';
        if (dateEnd) dateEnd.value = '';
        if (clearBtn) clearBtn.classList.remove('show');
        
        // Réinitialiser les boutons
        document.querySelectorAll(`#tab-${type} .filter-button`).forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Faire la recherche pour réafficher toutes les lignes
        const table = document.getElementById(`${type}-table`);
        if (table) {
            this.performSearch(type);
        }
    }

    clearSearch(type) {
        const searchInput = document.getElementById(`search-${type}`);
        const clearBtn = document.getElementById(`clear-${type}`);
        
        if (searchInput) searchInput.value = '';
        if (clearBtn) clearBtn.classList.remove('show');
        
        this.performSearch(type);
    }

    filterByDate(type) {
        this.performSearch(type);
    }

    // Gestion du modal
    initModal() {
        const modal = document.getElementById('detailsModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal();
                }
            });
        }
    }

    showDetails(type, id) {
        const modal = document.getElementById('detailsModal');
        const modalBody = document.getElementById('modalBody');
        const modalTitle = document.getElementById('modalTitle');
        
        if (!modal || !modalBody || !modalTitle) return;
        
        // Titre selon le type
        const titles = {
            'elec_res': '⚡ Détails - Électricité Résidentiel',
            'elec_pro': '⚡ Détails - Électricité Professionnel',
            'gaz_res': '🔥 Détails - Gaz Résidentiel',
            'gaz_pro': '🔥 Détails - Gaz Professionnel'
        };
        
        modalTitle.innerHTML = titles[type] || 'Détails de la simulation';
        modalBody.innerHTML = '<div style="text-align: center; padding: 40px;">Chargement...</div>';
        modal.classList.add('active');
        
        // Appel AJAX pour récupérer les détails
        if (typeof jQuery !== 'undefined') {
            jQuery.ajax({
                url: ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'get_simulation_details',
                    type: type,
                    id: id,
                    _wpnonce: '<?php echo wp_create_nonce("get_simulation_details"); ?>'
                },
                success: (response) => {
                    if (response.success) {
                        modalBody.innerHTML = response.data.html;
                    } else {
                        modalBody.innerHTML = '<div style="color: red; text-align: center; padding: 40px;">Erreur: ' + (response.data || 'Impossible de charger les détails') + '</div>';
                    }
                },
                error: () => {
                    modalBody.innerHTML = '<div style="color: red; text-align: center; padding: 40px;">Erreur de connexion au serveur</div>';
                }
            });
        }
    }

    closeModal() {
        const modal = document.getElementById('detailsModal');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    // Raccourcis clavier
    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Escape pour fermer le modal
            if (e.key === 'Escape') {
                this.closeModal();
            }
            
            // Ctrl/Cmd + F pour focus sur la recherche
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                const activeTab = document.querySelector('.tab-pane.active');
                if (activeTab) {
                    const searchInput = activeTab.querySelector('.search-input');
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                }
            }
            
            // Alt + 1-4 pour changer d'onglet
            if (e.altKey && e.key >= '1' && e.key <= '4') {
                const tabs = ['elec-res', 'elec-pro', 'gaz-res', 'gaz-pro'];
                const tabIndex = parseInt(e.key) - 1;
                if (tabs[tabIndex]) {
                    this.switchTab(tabs[tabIndex]);
                }
            }
        });
    }
}

// Initialisation
let dashboardManager;
document.addEventListener('DOMContentLoaded', () => {
    dashboardManager = new DashboardManager();
});

// Fonctions globales pour les onclick
function toggleFilter(type, filterType, value, button) {
    if (dashboardManager) {
        dashboardManager.toggleFilter(type, filterType, value, button);
    }
}

function clearSearch(type) {
    if (dashboardManager) {
        dashboardManager.clearSearch(type);
    }
}

function resetFilters(type) {
    if (dashboardManager) {
        dashboardManager.resetFilters(type);
    }
}

function filterByDate(type) {
    if (dashboardManager) {
        dashboardManager.filterByDate(type);
    }
}

function showDetails(type, id) {
    if (dashboardManager) {
        dashboardManager.showDetails(type, id);
    }
}

function closeModal() {
    if (dashboardManager) {
        dashboardManager.closeModal();
    }
}
</script>


<style>
    /* ================================
    STYLES DU DASHBOARD MODERNE
    ================================ */
    
    .htic-dashboard {
        background: #f0f2f5;
        margin: 0;
        padding: 20px;
        min-height: 100vh;
    }

    .htic-dashboard * {
        box-sizing: border-box;
    }

    /* Header */
    .dashboard-header {
        background: linear-gradient(135deg, #222F46 0%, #57709d 100%);
        padding: 30px;
        border-radius: 12px;
        color: white;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }

    .dashboard-title {
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 10px;
        color: white;
    }

    .dashboard-subtitle {
        opacity: 0.9;
        font-size: 14px;
    }

    /* Statistiques */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .stat-card.elec { border-left-color: #3b82f6; }
    .stat-card.gaz { border-left-color: #10b981; }
    .stat-card.today { border-left-color: #f59e0b; }
    .stat-card.pending { border-left-color: #ef4444; }

    .stat-number {
        font-size: 32px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #6b7280;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Tabs */
    .tabs-container {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .tabs-nav {
        display: flex;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }

    .tab-button {
        flex: 1;
        padding: 16px 24px;
        background: none;
        border: none;
        font-size: 14px;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.3s;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .tab-button:hover {
        background: rgba(102, 126, 234, 0.05);
        color: #667eea;
    }

    .tab-button.active {
        color: #667eea;
        background: white;
    }

    .tab-button.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(135deg, #222F46 0%, #57709d 100%);
    }

    .tab-icon {
        font-size: 18px;
    }

    .tab-content {
        padding: 25px;
    }

    .tab-pane {
        display: none;
    }

    .tab-pane.active {
        display: block;
    }

    /* ================================
    SYSTÈME DE RECHERCHE MODERNE
    ================================ */

    .search-section {
        background: linear-gradient(135deg, #f6f8fb 0%, #f1f4f8 100%);
        padding: 25px;
        border-radius: 12px;
        margin-bottom: 30px;
        border: 1px solid #e1e7ef;
    }

    .search-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .search-title {
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .search-stats {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .search-stat {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
        color: #64748b;
    }

    .search-stat-value {
        font-weight: 600;
        color: #334155;
    }

    .search-wrapper {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .search-input-group {
        flex: 1;
        min-width: 300px;
        position: relative;
    }

    .search-input {
        width: 100%;
        height: 48px;
        padding: 0 50px 0 48px;
        border: 2px solid #e1e7ef;
        border-radius: 10px;
        font-size: 14px;
        background: white;
        transition: all 0.3s ease;
    }

    .search-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .search-clear {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: #f1f5f9;
        border: none;
        color: #64748b;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        transition: all 0.2s;
    }

    .search-clear.show {
        display: flex;
    }

    .search-clear:hover {
        background: #e2e8f0;
        color: #334155;
    }

    /* Date Picker */
    .date-filter-group {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .date-input {
        height: 48px;
        padding: 0 15px;
        border: 2px solid #e1e7ef;
        border-radius: 10px;
        font-size: 14px;
        background: white;
        transition: all 0.3s;
        cursor: pointer;
    }

    .date-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .date-separator {
        color: #94a3b8;
        font-weight: 500;
    }

    /* Filtres */
    .search-filters {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 15px;
    }

    .filter-button {
        height: 42px;
        padding: 0 18px;
        background: white;
        border: 2px solid #e1e7ef;
        border-radius: 10px;
        color: #475569;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-button:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .filter-button.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: transparent;
    }

    /* Table moderne */
    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .modern-table thead {
        background: #f9fafb;
    }

    .modern-table th {
        padding: 12px 16px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e5e7eb;
    }

    .modern-table tbody tr {
        border-bottom: 1px solid #f3f4f6;
        transition: all 0.2s;
    }

    .modern-table tbody tr:hover {
        background: #f9fafb;
    }

    .modern-table td {
        padding: 14px 16px;
        font-size: 14px;
        color: #374151;
    }

    .client-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .client-name {
        font-weight: 600;
        color: #1f2937;
        font-size: 0.875rem;
    }

    .client-email {
        font-size: 0.75rem;
        color: #6b7280;
    }

    /* Badges */
    .badge {
        display: inline-block;
        padding: 0.375rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        transition: all 0.2s ease;
    }

    .badge-pending {
        background-color: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    /* En cours - Orange/Jaune */
    .badge-processing {
        background-color: #fffbeb;
        color: #d97706;
        border: 1px solid #fed7aa;
    }

    /* Traité - Vert */
    .badge-completed {
        background-color: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }

    /* Actions */
    .action-buttons {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: nowrap;
        min-width: 200px;
    }

    .btn-action {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }

    .btn-action::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s;
    }

    .btn-action:hover::before {
        left: 100%;
    }

    .btn-view {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
        border: 2px solid transparent;
    }

    .btn-view:hover {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        transform: translateY(-2px);
        box-shadow: 0 8px 15px -3px rgba(59, 130, 246, 0.4);
    }

    .btn-view:active {
        transform: translateY(0);
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
    }

    .btn-status {
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        border: 2px solid #cbd5e1;
        color: #475569;
        padding: 0.4rem 0.8rem;
        font-weight: 500;
        min-width: 50px;
    }

    .btn-status:hover {
        background: linear-gradient(135deg, #f1f5f9, #cbd5e1);
        border-color: #94a3b8;
        color: #334155;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.1);
    }

    .btn-status:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .btn-delete {
            background: linear-gradient(135deg, #ffd7d7, #ff9595);
        color: white;
        box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
        padding: 0.5rem;
        min-width: 40px;
        justify-content: center;
    }

    .btn-delete:hover {
        background: linear-gradient(135deg, #ee8888ff, #f46363ff);
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 8px 15px -3px rgba(239, 68, 68, 0.4);
    }

    .btn-delete:active {
        transform: translateY(0) scale(1);
        box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
    }

    @keyframes pulse-blue {
        0%, 100% {
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
        }
        50% {
            box-shadow: 0 6px 12px -1px rgba(59, 130, 246, 0.4);
        }
    }

    .btn-view:focus {
        animation: pulse-blue 2s infinite;
        outline: none;
    }

    .btn-status option {
        padding: 0.5rem;
        background-color: white;
        color: #374151;
    }

    .btn-status option:hover {
        background-color: #f3f4f6;
    }

    /* État vide */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-icon {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.3;
    }

    a[href^="tel:"] {
        color: #667eea !important;
        text-decoration: none !important;
        font-weight: 500;
    }

    a[href^="tel:"]:hover {
        text-decoration: underline !important;
    }

    .empty-title {
        font-size: 18px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .empty-text {
        color: #6b7280;
        font-size: 14px;
    }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999999;
        padding: 20px;
        box-sizing: border-box;
    }

    .modal-overlay.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        border-radius: 16px;
        max-width: 900px;
        width: 90%;
        max-height: 90vh;
        overflow: hidden;
        animation: slideUp 0.3s;
        display: flex;
        flex-direction: column;
    }

    @keyframes slideUp {
        from { 
            transform: translateY(50px) scale(0.95); 
            opacity: 0; 
        }
        to { 
            transform: translateY(0) scale(1); 
            opacity: 1; 
        }
    }

    .modal-header {
        background: linear-gradient(135deg, #222F46 0%, #57709d 100%);
        color: white;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    .modal-title {
        font-size: 20px;
        font-weight: 600;
        color: white;
    }

    .modal-body::before {
        content: '';
        position: sticky;
        top: -24px;
        height: 10px;
        background: linear-gradient(to bottom, rgba(248, 249, 250, 1), rgba(248, 249, 250, 0));
        margin: -24px -24px 0 -24px;
        z-index: 1;
        pointer-events: none;
    }

    .modal-body::after {
        content: '';
        position: sticky;
        bottom: -24px;
        height: 10px;
        background: linear-gradient(to top, rgba(248, 249, 250, 1), rgba(248, 249, 250, 0));
        margin: 0 -24px -24px -24px;
        z-index: 1;
        pointer-events: none;
    }

    .modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 20px;
        transition: all 0.2s;
    }

    .modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .modal-body {
        padding: 24px;
        max-height: none;
        overflow-y: auto;
        flex: 1; 
        min-height: 0; 
    }

    .wp-core-ui .notice.is-dismissible{
        color:black;
    }

    /* Highlight */
    .highlight {
        background: linear-gradient(135deg, #222F46 0%, #57709d 100%);
        padding: 2px 4px;
        border-radius: 3px;
        font-weight: 600;
    }

    /* ================================
    MODAL DE DÉTAILS - STYLES COMPLETS
    ================================ */

    .detail-section {
        margin-bottom: 24px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
    }

    .detail-section:last-child {
        margin-bottom: 0;
    }

    .detail-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        padding: 12px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        transition: all 0.2s;
    }

    .detail-item:hover {
        border-color: #cbd5e1;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .detail-label {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 500;
    }

    .detail-value {
        font-size: 15px;
        color: #1e293b;
        font-weight: 600;
        word-break: break-word;
    }

    .detail-value a {
        color: #667eea;
        text-decoration: none;
        transition: color 0.2s;
    }

    .detail-value a:hover {
        color: #764ba2;
        text-decoration: underline;
    }

    /* Box de highlight pour l'estimation */
    .highlight-box {
        background: linear-gradient(135deg, #222F46 0%, #57709d 100%);
        color: white;
        padding: 24px;
        border-radius: 12px;
        margin: 24px 0;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        text-align: center;
        word-break: break-word;
    }

    .highlight-box h3 {
        margin: 0 0 16px 0;
        color: white!important;
        font-size: 18px;
        font-weight: 600;
    }

    .highlight-value {
        font-size: 32px;
        font-weight: 700;
        color: white;
        margin-bottom: 8px;
    }

    .highlight-label {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 400;
    }

    /* Section de répartition des coûts */
    .cost-breakdown {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid rgba(255, 255, 255, 0.2);
    }

    .cost-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .cost-row:last-child {
        border-bottom: none;
        padding-top: 15px;
        margin-top: 10px;
        border-top: 2px solid rgba(255, 255, 255, 0.2);
    }

    .cost-label {
        color: rgba(255, 255, 255, 0.9);
        font-size: 14px;
    }

    .cost-value {
        color: white;
        font-size: 16px;
        font-weight: 600;
    }

    /* Section documents */
    .documents-section {
        background: #f0f9ff;
        border-left: 4px solid #3b82f6;
    }

    .document-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: white;
        border-radius: 8px;
        margin-bottom: 10px;
        border: 1px solid #dbeafe;
    }

    .document-icon {
        width: 40px;
        height: 40px;
        background: #eff6ff;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .document-info {
        flex: 1;
    }

    .document-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 14px;
        margin-bottom: 2px;
    }

    .document-size {
        font-size: 12px;
        color: #64748b;
    }

    .document-status {
        padding: 4px 12px;
        background: #10b981;
        color: white;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    /* Section consentements */
    .consent-section {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        border-left: 4px solid #10b981;
    }

    .consent-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: white;
        border-radius: 8px;
        margin-bottom: 10px;
    }

    .consent-icon {
        font-size: 24px;
    }

    .consent-text {
        flex: 1;
        font-size: 14px;
        color: #1e293b;
        font-weight: 500;
    }

    /* Métadonnées en bas */
    .metadata-section {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        padding: 16px 20px;
        border-radius: 10px;
        margin-top: 24px;
        margin-bottom: 0; 
        border: 1px solid #cbd5e1;
    }

    .metadata-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }

    .metadata-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .metadata-label {
        font-size: 11px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .metadata-value {
        font-size: 14px;
        color: #1e293b;
        font-weight: 600;
    }

    /* Badges dans le modal */
    .detail-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        margin-right: 8px;
    }

    .detail-badge.success {
        background: #d1fae5;
        color: #065f46;
    }

    .detail-badge.warning {
        background: #fef3c7;
        color: #92400e;
    }

    .detail-badge.info {
        background: #dbeafe;
        color: #1e40af;
    }

    .detail-badge.danger {
        background: #fee2e2;
        color: #991b1b;
    }

    /* Animation d'entrée pour les sections */
    .detail-section {
        animation: fadeInUp 0.3s ease;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive pour le modal */
    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            max-height: 95vh;
            margin: 2.5vh auto;
        }
        
        .modal-body {
            padding: 16px;
        }
        
        .detail-grid {
            grid-template-columns: 1fr;
        }
        
        .highlight-value {
            font-size: 24px;
        }
        
        .metadata-grid {
            grid-template-columns: 1fr;
        }

        .action-buttons {
            gap: 0.5rem;
            min-width: auto;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 0.4rem 0.8rem;
            font-size: 0.75rem;
        }
        
        .btn-view {
            order: 1;
            flex: 1;
            justify-content: center;
        }
        
        .btn-status {
            order: 2;
            flex: 1;
            min-width: 70px;
        }
        
        .btn-delete {
            order: 3;
            min-width: 36px;
        }
    }


    .modal-body::-webkit-scrollbar {
        width: 12px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
        margin: 8px 0;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #222F46 0%, #57709d 100%);
        border-radius: 10px;
        border: 2px solid #f1f5f9;
    }

    .modal-body::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #222F46 0%, #57709d 100%);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .search-wrapper {
            flex-direction: column;
        }
        
        .date-filter-group {
            width: 100%;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .tabs-nav {
            flex-direction: column;
        }
    }
</style>
