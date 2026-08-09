<?php
$__sidebarRole = currentRole();
require_once __DIR__ . '/../../app/autoload.php'; $__nuruBase = \App\Core\Router::basePath();
$__sidebarHome = $__sidebarRole === 'manager' ? 'dashboard_2.php' : 'admin.php';
$__sidebarName = $_SESSION['full_name'] ?? $_SESSION['email'] ?? 'Nuru user';
?>
<aside class="left-sidebar">
    <!-- Sidebar scroll-->
    <div class="scroll-sidebar">
        <!-- User profile -->
        <div class="user-profile position-relative" style="background: url(<?= $__nuruBase ?>/assets/images/background/user-info.jpg) no-repeat;">
            <!-- User profile image -->
            <div class="profile-img">
                <img src="<?= $__nuruBase ?>/assets/images/users/profile.png" alt="user" class="w-100" />
            </div>
            <!-- User profile text-->
            <div class="profile-text pt-1 dropdown">
                <a href="#" class="dropdown-toggle u-dropdown w-100 text-white d-block position-relative" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= htmlspecialchars($__sidebarName) ?>
                </a>
                <div class="dropdown-menu animated flipInY" aria-labelledby="dropdownMenuLink">
                    <a class="dropdown-item" href="<?= \App\Core\Router::legacyUrl('change-password.php') ?>">
                        <i data-feather="key" class="feather-sm text-warning me-1 ms-1"></i>
                        Change password
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?= \App\Core\Router::legacyUrl('config/logout.php') ?>">
                        <i data-feather="log-out" class="feather-sm text-danger me-1 ms-1"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
        <!-- End User profile text-->
        
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav">
            <ul id="sidebarnav">
                <li class="nav-small-cap">
                    <i class="mdi mdi-dots-horizontal"></i>
                    <span class="hide-menu">Property Management</span>
                </li>
                
                <!-- Dashboard -->
                <li class="sidebar-item">
                    <a class="sidebar-link waves-effect waves-dark sidebar-link" href="<?= \App\Core\Router::legacyUrl($__sidebarHome) ?>" aria-expanded="false">
                        <i class="mdi mdi-view-dashboard"></i>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>
                
                <!-- Buyers Management -->
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                        <i class="me-2 mdi mdi-account"></i>
                        <span class="hide-menu">Buyers</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('buyers-list.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-format-list-bulleted"></i>
                                <span class="hide-menu">List Buyers</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('buyer_admin_form.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-account-plus"></i>
                                <span class="hide-menu">Add Buyer</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- Sellers Management -->
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                        <i class="mdi mdi-home-outline"></i>
                        <span class="hide-menu">Sellers</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('sellers-list.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-format-list-bulleted"></i>
                                <span class="hide-menu">List Sellers</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('seller_admin_form.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-home-plus"></i>
                                <span class="hide-menu">Add Seller</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- Agents Management -->
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                        <i class="me-2 mdi mdi-voice"></i>
                        <span class="hide-menu">Agents</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('agent_list.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-format-list-bulleted"></i>
                                <span class="hide-menu">Agents List </span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('agent_admin_form.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-account-plus"></i>
                                <span class="hide-menu">Add Agent</span>
                            </a>
                        </li>
                    </ul>
                </li>            
				<li class="sidebar-item">
                    <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                        <i class="me-2 mdi mdi-voice"></i>
                        <span class="hide-menu">Consultants</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('consultant_list.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-format-list-bulleted"></i>
                                <span class="hide-menu">Consultant List </span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('consulting_agent_form.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-account-plus"></i>
                                <span class="hide-menu">Add Buyer</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Properties Management -->
                <li class="sidebar-item">
                    <a class="sidebar-link waves-effect waves-dark sidebar-link" href="<?= \App\Core\Router::legacyUrl('public-inquiries.php') ?>" aria-expanded="false">
                        <i class="mdi mdi-message-text-outline"></i>
                        <span class="hide-menu">Website Enquiries</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                        <i class="me-2 mdi mdi-factory"></i>
                        <span class="hide-menu">Properties</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('properties-list.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-format-list-bulleted"></i>
                                <span class="hide-menu"> Properties List</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('properties-available.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-home"></i>
                                <span class="hide-menu">Available Properties</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('properties-sold.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-home-check"></i>
                                <span class="hide-menu">Sold Properties</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('property_admin_form.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-home-plus"></i>
                                <span class="hide-menu">Add Property</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- Property Matching -->
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                        <i class="mdi mdi-vector-difference-ba"></i>
                        <span class="hide-menu">Property Matching</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('match-table1.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-table"></i>
                                <span class="hide-menu">Match Table</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('match-results.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-format-list-bulleted"></i>
                                <span class="hide-menu">Match Results</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- Tasks Management -->
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                        <i class="mdi mdi-clipboard-check"></i>
                        <span class="hide-menu">Tasks</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                       
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('tasks-cancelled.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-clock-outline"></i>
                                <span class="hide-menu">Cancelled</span>
                            </a>
                        </li>                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('tasks-pending.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-clock-outline"></i>
                                <span class="hide-menu">Pending</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('tasks-in-progress.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-progress-clock"></i>
                                <span class="hide-menu">In Progress</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('tasks-completed.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-check-circle"></i>
                                <span class="hide-menu">Completed</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                        <i class="mdi mdi-checkbox-multiple-marked-outline"></i>
                        <span class="hide-menu">Checklist Configuration</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item"><a href="<?= \App\Core\Router::legacyUrl('stages.php') ?>" class="sidebar-link"><span class="hide-menu">Stages</span></a></li>
                        <li class="sidebar-item"><a href="<?= \App\Core\Router::legacyUrl('items.php') ?>" class="sidebar-link"><span class="hide-menu">Items</span></a></li>
                    </ul>
                </li>

                <!-- Reports and Exports -->
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                        <i class="mdi mdi-chart-bar"></i>
                        <span class="hide-menu">Reports & Exports</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('reports-dashboard.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-view-dashboard"></i>
                                <span class="hide-menu">Report Dashboard</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('reports-sales.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-trending-up"></i>
                                <span class="hide-menu">Sales Reports</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('reports-property.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-home-analytics"></i>
                                <span class="hide-menu">Property Reports</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('reports-agent.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-account-group"></i>
                                <span class="hide-menu">Agent Reports</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('exports.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-file-export"></i>
                                <span class="hide-menu">Export Data</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('reports-custom.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-tune"></i>
                                <span class="hide-menu">Custom Reports</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('analytics.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-google-analytics"></i>
                                <span class="hide-menu">Analytics</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <!-- System Management (admin only) -->
                <li class="nav-small-cap">
                    <i class="mdi mdi-dots-horizontal"></i>
                    <span class="hide-menu">System</span>
                </li>

                <!-- System Settings -->
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                        <i class="ti-harddrives"></i>
                        <span class="hide-menu">System Settings</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('settings-general.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-cog"></i>
                                <span class="hide-menu">General Settings</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('settings-database.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-database"></i>
                                <span class="hide-menu">Database Settings</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('settings-email.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-email"></i>
                                <span class="hide-menu">Email Settings</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="<?= \App\Core\Router::legacyUrl('backup-restore.php') ?>" class="sidebar-link">
                                <i class="mdi mdi-backup-restore"></i>
                                <span class="hide-menu">Database Backup</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- User Management -->
                <li class="sidebar-item">
                    <a class="sidebar-link waves-effect waves-dark sidebar-link" href="<?= \App\Core\Router::legacyUrl('user-management.php') ?>" aria-expanded="false">
                        <i class="mdi mdi-account-multiple"></i>
                        <span class="hide-menu">User Management</span>
                    </a>
                </li>

                <!-- Activity Log -->
                <li class="sidebar-item">
                    <a class="sidebar-link waves-effect waves-dark sidebar-link" href="<?= \App\Core\Router::legacyUrl('activity-log.php') ?>" aria-expanded="false">
                        <i class="mdi mdi-history"></i>
                        <span class="hide-menu">Activity Log</span>
                    </a>
                </li>
                <?php endif; ?>

            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
    
    <!-- Bottom points-->
    <div class="sidebar-footer">
        <!-- item-->
        <a href="<?= \App\Core\Router::legacyUrl('change-password.php') ?>" class="link" data-bs-toggle="tooltip" data-bs-placement="top" title="Change password">
            <i class="ti-lock"></i>
        </a>
        <!-- item-->
        <a href="<?= \App\Core\Router::legacyUrl($__sidebarHome) ?>" class="link" data-bs-toggle="tooltip" data-bs-placement="top" title="Dashboard">
            <i class="mdi mdi-view-dashboard"></i>
        </a>
        <!-- item-->
        <a href="<?= \App\Core\Router::legacyUrl('config/logout.php') ?>" class="link" data-bs-toggle="tooltip" data-bs-placement="top" title="Logout">
            <i class="mdi mdi-power"></i>
        </a>
    </div>
    <!-- End Bottom points-->
</aside>

<!-- Active Menu Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set active menu based on current page
    const currentPage = window.location.pathname.split('/').pop();
    const menuItems = document.querySelectorAll('#sidebarnav .sidebar-link');
    
    // First, remove any conflicting 'show' classes from all dropdowns
    const allDropdowns = document.querySelectorAll('.collapse.first-level');
    allDropdowns.forEach(dropdown => {
        dropdown.classList.remove('show');
    });
    
    // Reset all aria-expanded attributes to false
    const allLinks = document.querySelectorAll('.sidebar-link.has-arrow');
    allLinks.forEach(link => {
        link.setAttribute('aria-expanded', 'false');
    });
    
    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && href.includes(currentPage) && !href.includes('javascript:void(0)')) {
            item.classList.add('active');
            
            // Expand parent menu if exists
            const parent = item.closest('.collapse');
            if (parent) {
                parent.classList.add('show');
                const parentLink = parent.previousElementSibling;
                if (parentLink && parentLink.classList.contains('has-arrow')) {
                    parentLink.setAttribute('aria-expanded', 'true');
                }
            }
        }
    });
    
    // Handle manual menu toggles
    const toggleLinks = document.querySelectorAll('.sidebar-link.has-arrow');
    toggleLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const dropdown = this.nextElementSibling;
            
            if (dropdown && dropdown.classList.contains('collapse')) {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                
                // Close all other dropdowns first
                const otherDropdowns = document.querySelectorAll('.collapse.first-level.show');
                otherDropdowns.forEach(otherDropdown => {
                    if (otherDropdown !== dropdown) {
                        otherDropdown.classList.remove('show');
                        const otherLink = otherDropdown.previousElementSibling;
                        if (otherLink) {
                            otherLink.setAttribute('aria-expanded', 'false');
                        }
                    }
                });
                
                // Toggle current dropdown
                if (isExpanded) {
                    dropdown.classList.remove('show');
                    this.setAttribute('aria-expanded', 'false');
                } else {
                    dropdown.classList.add('show');
                    this.setAttribute('aria-expanded', 'true');
                }
            }
        });
    });
});
</script>
