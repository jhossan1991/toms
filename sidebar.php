<?php
// sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);

/**
 * 1. Define Menu Mappings
 * This tells the sidebar which parent should be open for which sub-page.
 */
$menu_map = [
    'dashSub'    => ['index.php', 'expiry_dashboard.php'],
    'staffSub'   => ['hayiki/staff/staff_list.php', 'add_staff.php', 'view_staff.php', 'edit_staff.php'],
    'clientSub'  => ['clients.php', 'add_client.php', 'edit_client.php', 'client_view.php'],
    'quoteSub'   => ['manage_quotations.php', 'add_quotation.php', 'view_quotation.php', 'edit_quotation.php'],
    'jobsSub'    => ['manage_jobs.php', 'add_job.php', 'view_job.php', 'edit_job.php'],
    'vendorSub'  => ['vendors.php', 'add_vendor.php', 'edit_vendor.php'],
    'assignSub'  => [], 
    'invoiceSub' => [], 
    'settleSub'  => [],
    'vehicleSub' => [],
    'settingsSub'=> [
        'settings.php', 
        'manage_users.php', 
        'roles_permissions.php', 
        'branch_list.php', 
        'service_types.php', 
        'language_pairs.php', 
        'pricing_rules.php'
    ]
];

/**
 * 2. Helper function to check if a section should be expanded
 */
function is_section_active($section_id, $current_page, $menu_map) {
    return isset($menu_map[$section_id]) && in_array($current_page, $menu_map[$section_id]);
}
?>
<style>
    #sidebar {
        min-width: 260px;
        max-width: 260px;
        min-height: 100vh;
        background: #1b1a2f;
        color: #fff;
        transition: all 0.3s;
        font-size: 0.9rem;
    }
    #sidebar .sidebar-header {
        padding: 20px;
        background: #1b1a2f;
        text-align: center;
        border-bottom: 1px solid #2d2c44;
    }
    #sidebar .sidebar-header h4 { color: #79c219; font-weight: bold; margin: 0; }
    
    #sidebar ul.components { padding: 10px 0; }

    #sidebar ul li a {
        padding: 12px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #adb5bd;
        text-decoration: none;
        transition: 0.3s;
        background: #258d54; 
        border-bottom: 1px solid rgba(255,255,255,0.1);
        cursor: pointer;
    }

    #sidebar ul li a:hover {
        background: #79c219;
        color: #fff;
    }

    .submenu-container {
        background: #1e1d33;
    }
    .submenu-container a {
        background: transparent !important;
        padding: 8px 20px 8px 45px !important;
        font-size: 0.85rem;
        border-bottom: none !important;
        display: block;
        color: #adb5bd;
        text-decoration: none;
    }
    .submenu-container a:hover, .submenu-container a.sub-active {
        color: #79c219 !important;
    }

    #sidebar ul li a.active-parent {
        background: #79c219;
        color: #fff;
        border-left: 4px solid #fff;
    }

    #sidebar i { width: 20px; margin-right: 10px; }
    .chevron-icon { font-size: 0.7rem; transition: transform 0.3s; }
    
    /* Improved Chevron Rotation logic */
    a[aria-expanded="true"] .chevron-icon { transform: rotate(90deg); }

    /* Prevent crushing during animation */
    .collapse {
        transition: height 0.35s ease !important;
    }
</style>

<nav id="sidebar">
    <div class="sidebar-header">
        <h4>AlHayiki</h4>
        <small class="text-white-50">Translation Services</small>
    </div>

    <ul class="list-unstyled components" id="sidebarAccordion">
        
        <li>
            <?php $active = is_section_active('dashSub', $current_page, $menu_map); ?>
            <a href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#dashSub" class="<?= $active ? 'active-parent' : 'collapsed' ?>" aria-expanded="<?= $active ? 'true' : 'false' ?>">
                <span><i class="fas fa-home"></i> Dashboard</span>
                <i class="fas fa-chevron-right chevron-icon"></i>
            </a>
            <div class="collapse <?= $active ? 'show' : '' ?> submenu-container" id="dashSub" data-bs-parent="#sidebarAccordion">
                <a href="index.php" class="<?= ($current_page == 'index.php') ? 'sub-active' : '' ?>">System Overview</a>
                <a href="#">Today's Tasks</a>
            </div>
        </li>

        <li>
            <?php $active = is_section_active('staffSub', $current_page, $menu_map); ?>
            <a href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#staffSub" class="<?= $active ? 'active-parent' : 'collapsed' ?>" aria-expanded="<?= $active ? 'true' : 'false' ?>">
                <span><i class="fas fa-user-tie"></i> Staff Management</span>
                <i class="fas fa-chevron-right chevron-icon"></i>
            </a>
            <div class="collapse <?= $active ? 'show' : '' ?> submenu-container" id="staffSub" data-bs-parent="#sidebarAccordion">
                <a href="add_staff.php" class="<?= ($current_page == 'add_staff.php') ? 'sub-active' : '' ?>">Add Staff</a>
                <a href="staff_list.php" class="<?= ($current_page == 'staff_list.php') ? 'sub-active' : '' ?>">Staff List</a>
                <a href="expiry_dashboard.php" class="<?= ($current_page == 'expiry_dashboard.php') ? 'sub-active' : '' ?>">Expiry Dashboard</a>
            </div>
        </li>

        <li>
            <?php $active = is_section_active('clientSub', $current_page, $menu_map); ?>
            <a href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#clientSub" class="<?= $active ? 'active-parent' : 'collapsed' ?>" aria-expanded="<?= $active ? 'true' : 'false' ?>">
                <span><i class="fas fa-users"></i> Clients</span>
                <i class="fas fa-chevron-right chevron-icon"></i>
            </a>
            <div class="collapse <?= $active ? 'show' : '' ?> submenu-container" id="clientSub" data-bs-parent="#sidebarAccordion">
                <a href="clients.php" class="<?= ($current_page == 'clients.php') ? 'sub-active' : '' ?>">Client List</a>
                <a href="add_client.php" class="<?= ($current_page == 'add_client.php') ? 'sub-active' : '' ?>">Add Client</a>
            </div>
        </li>

        <li>
            <?php $active = is_section_active('quoteSub', $current_page, $menu_map); ?>
            <a href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#quoteSub" class="<?= $active ? 'active-parent' : 'collapsed' ?>" aria-expanded="<?= $active ? 'true' : 'false' ?>">
                <span><i class="fas fa-file-invoice"></i> Quotations</span>
                <i class="fas fa-chevron-right chevron-icon"></i>
            </a>
            <div class="collapse <?= $active ? 'show' : '' ?> submenu-container" id="quoteSub" data-bs-parent="#sidebarAccordion">
                <a href="add_quotation.php" class="<?= ($current_page == 'add_quotation.php') ? 'sub-active' : '' ?>">Create Quotation</a>
                <a href="manage_quotations.php" class="<?= ($current_page == 'manage_quotations.php') ? 'sub-active' : '' ?>">Quotation List</a>
            </div>
        </li>

        <li>
            <?php $active = is_section_active('jobsSub', $current_page, $menu_map); ?>
            <a href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#jobsSub" class="<?= $active ? 'active-parent' : 'collapsed' ?>" aria-expanded="<?= $active ? 'true' : 'false' ?>">
                <span><i class="fas fa-briefcase"></i> Jobs</span>
                <i class="fas fa-chevron-right chevron-icon"></i>
            </a>
            <div class="collapse <?= $active ? 'show' : '' ?> submenu-container" id="jobsSub" data-bs-parent="#sidebarAccordion">
                <a href="add_job.php" class="<?= ($current_page == 'add_job.php') ? 'sub-active' : '' ?>">Create Job</a>
                <a href="manage_jobs.php" class="<?= ($current_page == 'manage_jobs.php') ? 'sub-active' : '' ?>">Job List</a>
            </div>
        </li>

        <li>
            <?php $active = is_section_active('assignSub', $current_page, $menu_map); ?>
            <a href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#assignSub" class="<?= $active ? 'active-parent' : 'collapsed' ?>" aria-expanded="<?= $active ? 'true' : 'false' ?>">
                <span><i class="fas fa-tasks"></i> Assignments</span>
                <i class="fas fa-chevron-right chevron-icon"></i>
            </a>
            <div class="collapse <?= $active ? 'show' : '' ?> submenu-container" id="assignSub" data-bs-parent="#sidebarAccordion">
                <a href="#">Assignment Board</a>
                <a href="#">My Assignments</a>
            </div>
        </li>

        <li>
            <?php $active = is_section_active('vendorSub', $current_page, $menu_map); ?>
            <a href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#vendorSub" class="<?= $active ? 'active-parent' : 'collapsed' ?>" aria-expanded="<?= $active ? 'true' : 'false' ?>">
                <span><i class="fas fa-handshake"></i> Vendors</span>
                <i class="fas fa-chevron-right chevron-icon"></i>
            </a>
            <div class="collapse <?= $active ? 'show' : '' ?> submenu-container" id="vendorSub" data-bs-parent="#sidebarAccordion">
                <a href="vendors.php" class="<?= ($current_page == 'vendors.php') ? 'sub-active' : '' ?>">Vendor List</a>
                <a href="add_vendor.php" class="<?= ($current_page == 'add_vendor.php') ? 'sub-active' : '' ?>">Add Vendor</a>
            </div>
        </li>

        <li>
            <?php $active = is_section_active('invoiceSub', $current_page, $menu_map); ?>
            <a href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#invoiceSub" class="<?= $active ? 'active-parent' : 'collapsed' ?>" aria-expanded="<?= $active ? 'true' : 'false' ?>">
                <span><i class="fas fa-file-invoice-dollar"></i> Invoices</span>
                <i class="fas fa-chevron-right chevron-icon"></i>
            </a>
            <div class="collapse <?= $active ? 'show' : '' ?> submenu-container" id="invoiceSub" data-bs-parent="#sidebarAccordion">
                <a href="#">Invoice List</a>
                <a href="#">Overdue Invoices</a>
            </div>
        </li>

        <li>
            <?php $active = is_section_active('settleSub', $current_page, $menu_map); ?>
            <a href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#settleSub" class="<?= $active ? 'active-parent' : 'collapsed' ?>" aria-expanded="<?= $active ? 'true' : 'false' ?>">
                <span><i class="fas fa-calculator"></i> Settlements</span>
                <i class="fas fa-chevron-right chevron-icon"></i>
            </a>
            <div class="collapse <?= $active ? 'show' : '' ?> submenu-container" id="settleSub" data-bs-parent="#sidebarAccordion">
                <a href="#">Monthly Settlement</a>
                <a href="#">Vendor Payables</a>
            </div>
        </li>

        <li>
            <?php $active = is_section_active('vehicleSub', $current_page, $menu_map); ?>
            <a href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#vehicleSub" class="<?= $active ? 'active-parent' : 'collapsed' ?>" aria-expanded="<?= $active ? 'true' : 'false' ?>">
                <span><i class="fas fa-car"></i> Vehicle Management</span>
                <i class="fas fa-chevron-right chevron-icon"></i>
            </a>
            <div class="collapse <?= $active ? 'show' : '' ?> submenu-container" id="vehicleSub" data-bs-parent="#sidebarAccordion">
                <a href="#">Add Vehicle</a>
                <a href="#">Vehicle List</a>
                <a href="#">Vehicle Assignment</a>
                <a href="#">Registration (Istimara)</a>
                <a href="#">Insurance</a>
                <a href="#"> Traffic Violations</a>
                <a href="#">Maintenance Records</a>
                <a href="#">Expiry Alerts</a>
            </div>
        </li>

        <li class="mt-4">
            <?php $active = is_section_active('settingsSub', $current_page, $menu_map); ?>
            <a href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#settingsSub" class="<?= $active ? 'active-parent' : 'collapsed' ?>" aria-expanded="<?= $active ? 'true' : 'false' ?>">
                <span><i class="fas fa-cogs"></i> System Settings</span>
                <i class="fas fa-chevron-right chevron-icon"></i>
            </a>
            <div class="collapse <?= $active ? 'show' : '' ?> submenu-container" id="settingsSub" data-bs-parent="#sidebarAccordion">
                <a href="manage_users.php" class="<?= ($current_page == 'manage_users.php') ? 'sub-active' : '' ?>">User Management</a>
                <a href="roles_permissions.php" class="<?= ($current_page == 'roles_permissions.php') ? 'sub-active' : '' ?>">Roles & Permissions</a>
                <a href="branch_list.php" class="<?= ($current_page == 'branch_list.php') ? 'sub-active' : '' ?>">Branch Management</a>
                <a href="service_types.php" class="<?= ($current_page == 'service_types.php') ? 'sub-active' : '' ?>">Service Types</a>
                <a href="language_pairs.php" class="<?= ($current_page == 'language_pairs.php') ? 'sub-active' : '' ?>">Language Pairs</a>
                <a href="pricing_rules.php" class="<?= ($current_page == 'pricing_rules.php') ? 'sub-active' : '' ?>">Pricing Rules</a>
            </div>
        </li>

        <li>
            <a href="logout.php" class="text-warning">
                <span><i class="fas fa-sign-out-alt"></i> Logout</span>
            </a>
        </li>
    </ul>
</nav>