<?php
/**
 * Rose SMM Panel - SVG Icons Helper
 * Clean, lightweight, high-contrast SVG icons for Navbars, Categories & Services
 * Consistent Rose color palette (#e11d48 / var(--primary-rose))
 */

class SMMIcons {

    /**
     * Map category or service name to standardized platform slug
     */
    public static function getSlug(string $name): string {
        $n = strtolower(trim($name));
        if (strpos($n, 'insta') !== false) return 'instagram';
        if (strpos($n, 'face') !== false || strpos($n, 'fb') !== false) return 'facebook';
        if (strpos($n, 'you') !== false || strpos($n, 'tube') !== false || strpos($n, 'yt') !== false) return 'youtube';
        if (strpos($n, 'tik') !== false || strpos($n, 'tok') !== false) return 'tiktok';
        if (strpos($n, 'tele') !== false || strpos($n, 'tg') !== false) return 'telegram';
        if (strpos($n, 'twit') !== false || strpos($n, ' x ') !== false || strpos($n, 'x.com') !== false || strpos($n, ' x') !== false) return 'twitter';
        if (strpos($n, 'spot') !== false) return 'spotify';
        if (strpos($n, 'linke') !== false) return 'linkedin';
        if (strpos($n, 'disc') !== false) return 'discord';
        if (strpos($n, 'twitc') !== false) return 'twitch';
        if (strpos($n, 'whats') !== false) return 'whatsapp';
        if (strpos($n, 'thread') !== false) return 'threads';
        if (strpos($n, 'pinter') !== false) return 'pinterest';
        if (strpos($n, 'reddi') !== false) return 'reddit';
        if (strpos($n, 'soundc') !== false) return 'soundcloud';
        if (strpos($n, 'web') !== false || strpos($n, 'traffic') !== false || strpos($n, 'visit') !== false || strpos($n, 'seo') !== false) return 'traffic';
        return 'general';
    }

    /**
     * Get Raw SVG markup for a category slug
     */
    public static function getCategorySvg(string $slug, string $class = 'cat-icon'): string {
        $slug = strtolower(trim($slug));
        
        switch ($slug) {
            case 'instagram':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>'
                    . '<path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>'
                    . '<line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>'
                    . '</svg>';

            case 'facebook':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>'
                    . '</svg>';

            case 'youtube':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>'
                    . '<polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="currentColor" stroke="none"></polygon>'
                    . '</svg>';

            case 'tiktok':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"></path>'
                    . '</svg>';

            case 'telegram':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<line x1="22" y1="2" x2="11" y2="13"></line>'
                    . '<polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>'
                    . '</svg>';

            case 'twitter':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<path d="M4 4l11.733 16h4.267l-11.733 -16z"></path>'
                    . '<path d="M4 20l6.768 -6.768m2.46 -2.46l6.772 -6.772"></path>'
                    . '</svg>';

            case 'spotify':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<circle cx="12" cy="12" r="10"></circle>'
                    . '<path d="M8 11.5c3.5-1 7-.5 10 1.2"></path>'
                    . '<path d="M9 14.5c2.8-.8 5.6-.4 8 1"></path>'
                    . '<path d="M7 8.5c4.5-1.2 9-.6 13 1.5"></path>'
                    . '</svg>';

            case 'linkedin':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>'
                    . '<rect x="2" y="9" width="4" height="12"></rect>'
                    . '<circle cx="4" cy="4" r="2"></circle>'
                    . '</svg>';

            case 'discord':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<path d="M18 6h0a14.5 14.5 0 0 0-4-1.5 9.6 9.6 0 0 0-.5 1 11.8 11.8 0 0 0-3 0 9.6 9.6 0 0 0-.5-1 14.5 14.5 0 0 0-4 1.5c-2.5 4-3 8-2.5 12a14.8 14.8 0 0 0 4.5 2.3c.4-.5.7-1 1-1.6a9.5 9.5 0 0 1-1.5-.7c.1-.1.3-.2.4-.3 3 1.4 6.2 1.4 9.2 0 .1.1.3.2.4.3a9.5 9.5 0 0 1-1.5.7c.3.6.6 1.1 1 1.6a14.8 14.8 0 0 0 4.5-2.3c.6-4.5 0-8.5-2.5-12.2z"></path>'
                    . '<circle cx="8.5" cy="12" r="1.5" fill="currentColor"></circle>'
                    . '<circle cx="15.5" cy="12" r="1.5" fill="currentColor"></circle>'
                    . '</svg>';

            case 'twitch':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<path d="M21 2H3v16h5v4l4-4h5l4-4V2zm-10 9V7m5 4V7"></path>'
                    . '</svg>';

            case 'whatsapp':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>'
                    . '</svg>';

            case 'threads':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<circle cx="12" cy="12" r="4"></circle>'
                    . '<path d="M16 8v5a3 3 0 0 1-6 0v-1a6 6 0 1 1 2 4.47"></path>'
                    . '</svg>';

            case 'pinterest':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<circle cx="12" cy="12" r="10"></circle>'
                    . '<path d="M8 20l3-7a2 2 0 1 1 3.5 1.5c-.8 3-2.5 4-4.5 3"></path>'
                    . '</svg>';

            case 'reddit':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<circle cx="12" cy="12" r="10"></circle>'
                    . '<circle cx="9" cy="12" r="1.5" fill="currentColor"></circle>'
                    . '<circle cx="15" cy="12" r="1.5" fill="currentColor"></circle>'
                    . '<path d="M10 16c1 1 3 1 4 0"></path>'
                    . '</svg>';

            case 'traffic':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<circle cx="12" cy="12" r="10"></circle>'
                    . '<line x1="2" y1="12" x2="22" y2="12"></line>'
                    . '<path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>'
                    . '</svg>';

            case 'general':
            default:
                // Clean generic fallback icon: Star / Sparkles in Rose
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>'
                    . '</svg>';
        }
    }

    /**
     * Get SVG icon for a Category Name
     */
    public static function getCategoryIcon(string $categoryName, string $class = 'cat-icon'): string {
        $slug = self::getSlug($categoryName);
        return self::getCategorySvg($slug, $class);
    }

    /**
     * Get Navigation SVG Icon by key
     */
    public static function getNavIcon(string $key, string $class = 'nav-icon'): string {
        $key = strtolower(trim($key));
        
        switch ($key) {
            case 'dashboard':
            case 'admin_dashboard':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<rect x="3" y="3" width="7" height="9"></rect>'
                    . '<rect x="14" y="3" width="7" height="5"></rect>'
                    . '<rect x="14" y="12" width="7" height="9"></rect>'
                    . '<rect x="3" y="16" width="7" height="5"></rect>'
                    . '</svg>';

            case 'new_order':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<circle cx="9" cy="21" r="1"></circle>'
                    . '<circle cx="20" cy="21" r="1"></circle>'
                    . '<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>'
                    . '<line x1="12" y1="9" x2="12" y2="15"></line>'
                    . '<line x1="9" y1="12" x2="15" y2="12"></line>'
                    . '</svg>';

            case 'orders':
            case 'admin_orders':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>'
                    . '<polyline points="14 2 14 8 20 8"></polyline>'
                    . '<line x1="16" y1="13" x2="8" y2="13"></line>'
                    . '<line x1="16" y1="17" x2="8" y2="17"></line>'
                    . '<polyline points="10 9 9 9 8 9"></polyline>'
                    . '</svg>';

            case 'services':
            case 'admin_services':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>'
                    . '<polyline points="2 17 12 22 22 17"></polyline>'
                    . '<polyline points="2 12 12 17 22 12"></polyline>'
                    . '</svg>';

            case 'add_funds':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<rect x="2" y="4" width="20" height="16" rx="2"></rect>'
                    . '<line x1="2" y1="10" x2="22" y2="10"></line>'
                    . '<circle cx="17" cy="15" r="1.5"></circle>'
                    . '</svg>';

            case 'tickets':
            case 'admin_tickets':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>'
                    . '</svg>';

            case 'profile':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>'
                    . '<circle cx="12" cy="7" r="4"></circle>'
                    . '</svg>';

            case 'api_docs':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<polyline points="16 18 22 12 16 6"></polyline>'
                    . '<polyline points="8 6 2 12 8 18"></polyline>'
                    . '</svg>';

            case 'admin_users':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>'
                    . '<circle cx="9" cy="7" r="4"></circle>'
                    . '<path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>'
                    . '<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>'
                    . '</svg>';

            case 'admin_categories':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>'
                    . '</svg>';

            case 'admin_providers':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>'
                    . '<rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>'
                    . '<line x1="6" y1="6" x2="6.01" y2="6"></line>'
                    . '<line x1="6" y1="18" x2="6.01" y2="18"></line>'
                    . '</svg>';

            case 'admin_import':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>'
                    . '<polyline points="7 10 12 15 17 10"></polyline>'
                    . '<line x1="12" y1="15" x2="12" y2="3"></line>'
                    . '</svg>';

            case 'admin_payments':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>'
                    . '<line x1="1" y1="10" x2="23" y2="10"></line>'
                    . '</svg>';

            case 'admin_finance':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<line x1="18" y1="20" x2="18" y2="10"></line>'
                    . '<line x1="12" y1="20" x2="12" y2="4"></line>'
                    . '<line x1="6" y1="20" x2="6" y2="14"></line>'
                    . '</svg>';

            case 'admin_currencies':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<circle cx="12" cy="12" r="10"></circle>'
                    . '<path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path>'
                    . '<line x1="12" y1="6" x2="12" y2="8"></line>'
                    . '<line x1="12" y1="16" x2="12" y2="18"></line>'
                    . '</svg>';

            case 'admin_settings':
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<circle cx="12" cy="12" r="3"></circle>'
                    . '<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>'
                    . '</svg>';

            default:
                return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<circle cx="12" cy="12" r="10"></circle>'
                    . '</svg>';
        }
    }

    /**
     * Map of all category icons as SVGs for client-side JS
     */
    public static function getAllCategorySvgs(): array {
        $slugs = [
            'instagram', 'facebook', 'youtube', 'tiktok', 'telegram', 
            'twitter', 'spotify', 'linkedin', 'discord', 'twitch', 
            'whatsapp', 'threads', 'pinterest', 'reddit', 'traffic', 'general'
        ];
        $map = [];
        foreach ($slugs as $s) {
            $map[$s] = self::getCategorySvg($s, 'cat-icon');
        }
        return $map;
    }
}

// Global convenience procedural helpers
function get_nav_icon(string $key, string $class = 'nav-icon'): string {
    return SMMIcons::getNavIcon($key, $class);
}

function get_category_icon(string $categoryName, string $class = 'cat-icon'): string {
    return SMMIcons::getCategoryIcon($categoryName, $class);
}

function get_category_slug(string $categoryName): string {
    return SMMIcons::getSlug($categoryName);
}
