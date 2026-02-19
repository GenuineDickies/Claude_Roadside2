<?php
/**
 * SMS Knowledge Base
 * 
 * Comprehensive documentation for Telnyx SMS integration,
 * 10DLC compliance, and API usage.
 * 
 * Embedded page - works within main app layout
 */

// Get current doc from URL
$doc = $_GET['doc'] ?? 'readme';
$valid_docs = [
    'readme' => 'README.md',
    '10dlc' => '01-10DLC-Compliance.md',
    'api' => '02-API-Reference.md',
    'webhooks' => '03-Webhooks.md',
    'templates' => '04-Message-Templates.md',
    'consent' => '05-Opt-In-Consent.md',
    'troubleshooting' => '06-Troubleshooting.md',
    'proxy' => '07-Webhook-Proxy-SiteGround.md'
];

$current_file = $valid_docs[$doc] ?? 'README.md';
$docs_path = __DIR__ . '/../docs/sms/';
$content = '';

if (file_exists($docs_path . $current_file)) {
    $content = file_get_contents($docs_path . $current_file);
}

// Simple Markdown to HTML conversion
function kb_markdown_to_html($md) {
    // Escape HTML
    $html = htmlspecialchars($md, ENT_QUOTES, 'UTF-8');
    
    // Code blocks with syntax highlighting
    $html = preg_replace_callback('/```(\w+)?\n(.*?)```/s', function($m) {
        $lang = $m[1] ?: 'text';
        $code = $m[2];
        return '<pre class="kb-code-block" data-lang="' . $lang . '"><code>' . $code . '</code></pre>';
    }, $html);
    
    // Inline code
    $html = preg_replace('/`([^`]+)`/', '<code class="kb-inline-code">$1</code>', $html);
    
    // Headers
    $html = preg_replace('/^######\s+(.*)$/m', '<h6 class="kb-h6">$1</h6>', $html);
    $html = preg_replace('/^#####\s+(.*)$/m', '<h5 class="kb-h5">$1</h5>', $html);
    $html = preg_replace('/^####\s+(.*)$/m', '<h4 class="kb-h4">$1</h4>', $html);
    $html = preg_replace('/^###\s+(.*)$/m', '<h3 class="kb-h3">$1</h3>', $html);
    $html = preg_replace('/^##\s+(.*)$/m', '<h2 class="kb-h2">$1</h2>', $html);
    $html = preg_replace('/^#\s+(.*)$/m', '<h1 class="kb-h1">$1</h1>', $html);
    
    // Bold and italic
    $html = preg_replace('/\*\*\*(.+?)\*\*\*/s', '<strong><em>$1</em></strong>', $html);
    $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
    $html = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $html);
    
    // Links
    $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" class="kb-link" target="_blank">$1</a>', $html);
    
    // Tables
    $html = preg_replace_callback('/(\|.+\|)\n(\|[-:| ]+\|)\n((?:\|.+\|\n?)+)/m', function($m) {
        $header = trim($m[1]);
        $rows = trim($m[3]);
        
        $header_cells = array_filter(array_map('trim', explode('|', $header)));
        $header_html = '<thead><tr>';
        foreach ($header_cells as $cell) {
            $header_html .= '<th>' . $cell . '</th>';
        }
        $header_html .= '</tr></thead>';
        
        $body_html = '<tbody>';
        foreach (explode("\n", $rows) as $row) {
            $row = trim($row);
            if (!$row) continue;
            $cells = array_filter(array_map('trim', explode('|', $row)));
            $body_html .= '<tr>';
            foreach ($cells as $cell) {
                $body_html .= '<td>' . $cell . '</td>';
            }
            $body_html .= '</tr>';
        }
        $body_html .= '</tbody>';
        
        return '<div class="table-responsive"><table class="table table-dark table-bordered kb-table">' . $header_html . $body_html . '</table></div>';
    }, $html);
    
    // Horizontal rules
    $html = preg_replace('/^---+$/m', '<hr class="kb-hr">', $html);
    
    // Blockquotes
    $html = preg_replace('/^>\s*(.*)$/m', '<blockquote class="kb-blockquote">$1</blockquote>', $html);
    
    // Lists
    $html = preg_replace('/^- (.*)$/m', '<li class="kb-li">$1</li>', $html);
    $html = preg_replace('/^(\d+)\. (.*)$/m', '<li class="kb-li-num">$2</li>', $html);
    
    // Wrap consecutive list items
    $html = preg_replace('/(<li class="kb-li">.*<\/li>\n?)+/s', '<ul class="kb-list">$0</ul>', $html);
    $html = preg_replace('/(<li class="kb-li-num">.*<\/li>\n?)+/s', '<ol class="kb-list-num">$0</ol>', $html);
    
    // Checkboxes
    $html = str_replace('- [ ]', '<span class="kb-checkbox unchecked">☐</span>', $html);
    $html = str_replace('- [x]', '<span class="kb-checkbox checked">☑</span>', $html);
    
    // Warning/Note boxes
    $html = preg_replace('/⚠️\s*\*\*([^*]+)\*\*:?\s*(.*)/', '<div class="kb-alert kb-alert-warning"><strong>⚠️ $1</strong> $2</div>', $html);
    
    // Paragraphs (double newlines)
    $html = preg_replace('/\n\n+/', '</p><p class="kb-p">', $html);
    $html = '<p class="kb-p">' . $html . '</p>';
    
    // Clean up empty paragraphs
    $html = preg_replace('/<p class="kb-p">\s*<\/p>/', '', $html);
    $html = preg_replace('/<p class="kb-p">(<h[1-6]|<pre|<div|<table|<ul|<ol|<hr|<blockquote)/', '$1', $html);
    $html = preg_replace('/(<\/h[1-6]>|<\/pre>|<\/div>|<\/table>|<\/ul>|<\/ol>|<hr[^>]*>|<\/blockquote>)<\/p>/', '$1', $html);
    
    return $html;
}

$html_content = kb_markdown_to_html($content);
?>

<style>
/* SMS Knowledge Base Scoped Styles */
.kb-container {
    display: flex;
    gap: 24px;
}

.kb-sidebar {
    width: 260px;
    flex-shrink: 0;
    background: var(--bg-secondary, #12151A);
    border: 1px solid var(--border-dark, rgba(255,255,255,0.08));
    border-radius: 8px;
    padding: 16px;
    height: fit-content;
    position: sticky;
    top: 20px;
}

.kb-sidebar-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border-dark, rgba(255,255,255,0.08));
}

.kb-sidebar-header i {
    font-size: 1.25rem;
    color: var(--signal-green, #27AE60);
}

.kb-sidebar-header h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.kb-search input {
    background: var(--bg-tertiary, #1A1E25);
    border: 1px solid var(--border-dark, rgba(255,255,255,0.08));
    color: var(--text-primary, #fff);
    border-radius: 6px;
    padding: 8px 12px;
    width: 100%;
    font-size: 13px;
    margin-bottom: 16px;
}

.kb-search input:focus {
    outline: none;
    border-color: var(--navy-primary, #2B5EA7);
}

.kb-nav-section {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-muted, rgba(255,255,255,0.5));
    margin: 16px 0 8px 0;
}

.kb-nav-section:first-of-type {
    margin-top: 0;
}

.kb-nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    margin-bottom: 4px;
    border-radius: 6px;
    color: var(--text-secondary, rgba(255,255,255,0.7));
    text-decoration: none;
    font-size: 13px;
    transition: all 0.15s ease;
}

.kb-nav-link:hover {
    background: var(--bg-tertiary, #1A1E25);
    color: var(--text-primary, #fff);
}

.kb-nav-link.active {
    background: var(--navy-primary, #2B5EA7);
    color: white;
}

.kb-nav-link i {
    font-size: 14px;
    opacity: 0.7;
    width: 18px;
    text-align: center;
}

.kb-info-card {
    background: var(--bg-tertiary, #1A1E25);
    border: 1px solid var(--border-dark, rgba(255,255,255,0.08));
    border-radius: 6px;
    padding: 12px;
    margin-top: 16px;
}

.kb-info-card-title {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted, rgba(255,255,255,0.5));
    margin-bottom: 8px;
}

.kb-info-item {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    font-size: 12px;
}

.kb-info-label { color: var(--text-muted, rgba(255,255,255,0.5)); }
.kb-info-value { font-family: 'JetBrains Mono', monospace; color: var(--signal-green, #27AE60); }

/* Main Content */
.kb-main {
    flex: 1;
    min-width: 0;
}

.kb-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    font-size: 13px;
    color: var(--text-muted, rgba(255,255,255,0.5));
}

.kb-breadcrumb a {
    color: var(--navy-light, #3A7BD5);
    text-decoration: none;
}

.kb-content {
    background: var(--bg-secondary, #12151A);
    border: 1px solid var(--border-dark, rgba(255,255,255,0.08));
    border-radius: 8px;
    padding: 24px 32px;
    line-height: 1.7;
}

.kb-h1 {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--navy-primary, #2B5EA7);
}

.kb-h2 {
    font-size: 1.35rem;
    font-weight: 600;
    margin-top: 32px;
    margin-bottom: 12px;
    color: var(--text-primary, #fff);
}

.kb-h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-top: 24px;
    margin-bottom: 10px;
    color: var(--text-primary, #fff);
}

.kb-h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-top: 20px;
    margin-bottom: 8px;
    color: var(--signal-amber, #F5A623);
}

.kb-p {
    margin-bottom: 12px;
    color: var(--text-secondary, rgba(255,255,255,0.7));
}

.kb-code-block {
    background: var(--bg-primary, #0C0E12);
    border: 1px solid var(--border-dark, rgba(255,255,255,0.08));
    border-radius: 6px;
    padding: 14px 16px;
    margin: 12px 0;
    overflow-x: auto;
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
    position: relative;
}

.kb-code-block::before {
    content: attr(data-lang);
    position: absolute;
    top: 6px;
    right: 10px;
    font-size: 10px;
    color: var(--text-muted, rgba(255,255,255,0.5));
    text-transform: uppercase;
}

.kb-code-block code {
    color: var(--signal-green, #27AE60);
}

.kb-inline-code {
    background: var(--bg-tertiary, #1A1E25);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.85em;
    color: var(--signal-amber, #F5A623);
}

.kb-table {
    margin: 12px 0;
    font-size: 13px;
}

.kb-table th {
    background: var(--bg-tertiary, #1A1E25) !important;
    font-weight: 600;
    white-space: nowrap;
}

.kb-table td {
    background: var(--bg-secondary, #12151A) !important;
}

.kb-list, .kb-list-num {
    margin: 8px 0 12px 20px;
    padding: 0;
}

.kb-li, .kb-li-num {
    margin-bottom: 6px;
    color: var(--text-secondary, rgba(255,255,255,0.7));
}

.kb-link {
    color: var(--navy-light, #3A7BD5);
    text-decoration: none;
}

.kb-link:hover {
    color: var(--signal-blue, #3498DB);
    text-decoration: underline;
}

.kb-hr {
    border-color: var(--border-medium, rgba(255,255,255,0.12));
    margin: 24px 0;
}

.kb-blockquote {
    border-left: 4px solid var(--navy-primary, #2B5EA7);
    padding-left: 12px;
    margin: 12px 0;
    color: var(--text-secondary, rgba(255,255,255,0.7));
    font-style: italic;
}

.kb-alert {
    padding: 12px;
    border-radius: 6px;
    margin: 12px 0;
}

.kb-alert-warning {
    background: rgba(245, 166, 35, 0.1);
    border: 1px solid rgba(245, 166, 35, 0.3);
    color: var(--signal-amber, #F5A623);
}

.kb-checkbox.checked { color: var(--signal-green, #27AE60); }
.kb-checkbox.unchecked { color: var(--text-muted, rgba(255,255,255,0.5)); }

@media (max-width: 992px) {
    .kb-container { flex-direction: column; }
    .kb-sidebar { width: 100%; position: static; }
}
</style>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="fas fa-comment-sms me-2" style="color:var(--signal-green)"></i>SMS Knowledge Base</h1>
        <p class="text-muted mb-0" style="font-size:13px">Telnyx SMS integration, 10DLC compliance, and API documentation</p>
    </div>
    <div>
        <a href="https://portal.telnyx.com" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-external-link-alt me-1"></i> Telnyx Portal
        </a>
    </div>
</div>

<div class="kb-container">
    <!-- Sidebar Navigation -->
    <aside class="kb-sidebar">
        <div class="kb-sidebar-header">
            <i class="fas fa-book"></i>
            <h3>Documentation</h3>
        </div>
        
        <div class="kb-search">
            <input type="text" placeholder="Search docs..." id="kb-search-input">
        </div>
        
        <nav>
            <div class="kb-nav-section">Getting Started</div>
            <a href="?page=sms-knowledge&doc=readme" class="kb-nav-link <?= $doc === 'readme' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Overview
            </a>
            
            <div class="kb-nav-section">Compliance</div>
            <a href="?page=sms-knowledge&doc=10dlc" class="kb-nav-link <?= $doc === '10dlc' ? 'active' : '' ?>">
                <i class="fas fa-shield-alt"></i> 10DLC Registration
            </a>
            <a href="?page=sms-knowledge&doc=consent" class="kb-nav-link <?= $doc === 'consent' ? 'active' : '' ?>">
                <i class="fas fa-user-check"></i> Opt-In & Consent
            </a>
            
            <div class="kb-nav-section">Implementation</div>
            <a href="?page=sms-knowledge&doc=api" class="kb-nav-link <?= $doc === 'api' ? 'active' : '' ?>">
                <i class="fas fa-code"></i> API Reference
            </a>
            <a href="?page=sms-knowledge&doc=webhooks" class="kb-nav-link <?= $doc === 'webhooks' ? 'active' : '' ?>">
                <i class="fas fa-exchange-alt"></i> Webhooks
            </a>
            <a href="?page=sms-knowledge&doc=templates" class="kb-nav-link <?= $doc === 'templates' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i> Message Templates
            </a>
            
            <div class="kb-nav-section">Support</div>
            <a href="?page=sms-knowledge&doc=troubleshooting" class="kb-nav-link <?= $doc === 'troubleshooting' ? 'active' : '' ?>">
                <i class="fas fa-bug"></i> Troubleshooting
            </a>
        </nav>
        
        <!-- Quick Config Info -->
        <div class="kb-info-card">
            <div class="kb-info-card-title">Current Config</div>
            <div class="kb-info-item">
                <span class="kb-info-label">From</span>
                <span class="kb-info-value">+1 (971) 501-1430</span>
            </div>
            <div class="kb-info-item">
                <span class="kb-info-label">Profile</span>
                <span class="kb-info-value">Roadside_Service</span>
            </div>
            <div class="kb-info-item">
                <span class="kb-info-label">Status</span>
                <span class="kb-info-value">● Active</span>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="kb-main">
        <div class="kb-breadcrumb">
            <a href="?page=sms-knowledge">SMS Docs</a>
            <i class="fas fa-chevron-right" style="font-size:10px"></i>
            <span><?= htmlspecialchars(str_replace(['.md', '-', '_', '01-', '02-', '03-', '04-', '05-', '06-'], ['', ' ', ' ', '', '', '', '', '', ''], $current_file)) ?></span>
        </div>
        
        <article class="kb-content">
            <?= $html_content ?>
        </article>
    </main>
</div>

<script>
// Search functionality
document.getElementById('kb-search-input').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase();
    const links = document.querySelectorAll('.kb-nav-link');
    
    links.forEach(link => {
        const text = link.textContent.toLowerCase();
        link.style.display = text.includes(query) ? 'flex' : 'none';
    });
});
</script>
