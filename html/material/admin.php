<?php
session_start();
if (!isset($_SESSION['user_id']))
{
	header("location: authentication-login.php");
    exit;
}
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin','manager']);
 include("./config/matching-functions.php");
 ?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta
      name="keywords"
      content="Nuru real estate administration, property management, buyers, sellers, agents, tasks, and reporting"
    />
    <meta
      name="description"
      content="Secure Nuru Real Estate operations portal"
    />
    <meta name="robots" content="noindex,nofollow" />
    <title>Nuru Admin</title>
    <!-- Favicon icon -->
    <link
      rel="icon"
      type="image/png"
      sizes="16x16"
      href="../../assets/images/favicon.png"
    />
    <link
      rel="stylesheet"
      href="../../assets/libs/apexcharts/dist/apexcharts.css"
    />
    <link
      href="../../assets/extra-libs/css-chart/css-chart.css"
      rel="stylesheet"
    />
    <!-- Vector CSS -->
    <link
      href="../../assets/libs/jvectormap/jquery-jvectormap.css"
      rel="stylesheet"
    />
    <!-- Custom CSS -->
    <link href="../../dist/css/style.min.css" rel="stylesheet" />
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <![endif]-->
	
	<Style>
	/**
 * Namibia Map Visualization Styles
 * Styles for the interactive map with region-based data visualization
 */

/* Map Container */
#namibiaMap {
    position: relative;
    min-height: 500px;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 10px;
    margin: 20px 0;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Map Controls */
.map-controls {
    position: absolute;
    top: 40px;
    left: 10px;
    z-index: 100;
}

.map-controls .btn {
    font-size: 11px;
    padding: 4px 8px;
    border-width: 1px;
    font-weight: 400;
    transition: all 0.2s ease;
}

.map-controls .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.map-controls .btn.active {
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,123,255,0.3);
}

/* Legend Styles */
.map-legend {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: rgba(255,255,255,0.95);
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    font-size: 12px;
    border: 1px solid #ddd;
    z-index: 100;
    backdrop-filter: blur(10px);
}

.legend-title {
    font-weight: bold;
    margin-bottom: 8px;
    color: #333;
    font-size: 13px;
}

.legend-item {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
}

.legend-color {
    width: 15px;
    height: 15px;
    margin-right: 8px;
    border: 1px solid #ccc;
    border-radius: 2px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

/* Statistics Panel */
.map-stats {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255,255,255,0.95);
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    font-size: 12px;
    border: 1px solid #ddd;
    z-index: 100;
    min-width: 150px;
    backdrop-filter: blur(10px);
}

.stats-title {
    font-weight: bold;
    margin-bottom: 8px;
    color: #333;
    font-size: 13px;
}

.stats-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
    padding: 2px 0;
}

.stat-label {
    color: #666;
    font-weight: 500;
}

.stat-value {
    font-weight: bold;
    color: #333;
}

/* Tooltip Styles */
#map-tooltip {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    line-height: 1.4;
}

#map-tooltip strong {
    color: #333;
    font-weight: 600;
}

#map-tooltip span {
    display: inline-block;
    margin: 2px 0;
}

/* Region Hover Effects */
#namibiaMap svg path:hover,
#namibiaMap svg polygon:hover,
#namibiaMap svg rect:hover {
    filter: brightness(1.1) saturate(1.1);
    transform: scale(1.02);
    transition: all 0.2s ease;
    cursor: pointer;
}

#namibiaMap svg path:focus,
#namibiaMap svg polygon:focus,
#namibiaMap svg rect:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
    filter: brightness(1.1) saturate(1.1);
}

/* Loading State */
.map-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 300px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.map-loading::after {
    content: '';
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    animation: map-spin 1s linear infinite;
}

@keyframes map-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Error State */
.map-error {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 300px;
    background: #fff5f5;
    border: 1px solid #fed7d7;
    border-radius: 8px;
    color: #c53030;
    font-weight: 500;
}

/* Region Details Panel */
.region-details {
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}

.stat-card {
    text-align: center;
    padding: 15px;
    background: #f7fafc;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    margin-bottom: 10px;
}

.stat-card h6 {
    margin: 0 0 5px 0;
    color: #4a5568;
    font-size: 14px;
    font-weight: 600;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #2d3748;
}

/* SVG Text Labels */
#namibiaMap svg text[pointer-events="none"] {
    font-family: Arial, sans-serif;
    font-weight: bold;
    pointer-events: none;
    user-select: none;
    paint-order: stroke;
    stroke-width: 1px;
    stroke: white;
}

/* Label positioning and sizing */
#namibiaMap svg text {
    text-anchor: middle;
    dominant-baseline: middle;
}

/* Hover effects for regions with labels */
#namibiaMap svg path:hover + text,
#namibiaMap svg polygon:hover + text,
#namibiaMap svg rect:hover + text {
    filter: brightness(1.2);
}

/* Responsive Design */
@media (max-width: 768px) {
    #namibiaMap {
        min-height: 400px;
        padding: 15px;
    }
    
    .map-controls {
        position: relative !important;
        top: auto !important;
        left: auto !important;
        margin-bottom: 15px;
        text-align: center;
    }
    
    .map-legend,
    .map-stats {
        position: relative !important;
        top: auto !important;
        left: auto !important;
        bottom: auto !important;
        right: auto !important;
        margin-top: 15px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .map-legend {
        max-width: 200px;
    }
    
    .map-stats {
        max-width: 180px;
    }
    
    .legend-item,
    .stats-item {
        font-size: 11px;
    }
    
    .map-controls .btn {
        font-size: 10px;
        padding: 3px 6px;
    }
}

@media (max-width: 480px) {
    #namibiaMap {
        min-height: 350px;
        padding: 10px;
    }
    
    .map-legend,
    .map-stats {
        font-size: 11px;
        padding: 10px;
    }
    
    .legend-color {
        width: 12px;
        height: 12px;
    }
    
    .map-controls .btn {
        font-size: 9px;
        padding: 2px 4px;
    }
}

/* Print Styles */
@media print {
    #namibiaMap {
        border: 1px solid #000;
        box-shadow: none;
    }
    
    .map-controls,
    .map-legend,
    .map-stats {
        display: none;
    }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    #namibiaMap {
        border: 2px solid #000;
    }
    
    .map-legend,
    .map-stats {
        border: 2px solid #000;
        background: white;
    }
    
    #namibiaMap svg path,
    #namibiaMap svg polygon,
    #namibiaMap svg rect {
        stroke: #000;
        stroke-width: 2;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    #namibiaMap svg path,
    #namibiaMap svg polygon,
    #namibiaMap svg rect,
    .map-controls .btn {
        transition: none;
    }
    
    .map-loading::after {
        animation: none;
        border-top-color: #007bff;
    }
}
	</Style>
  </head>

  <body>
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
    <div class="preloader">
      <svg
        class="tea lds-ripple"
        width="37"
        height="48"
        viewbox="0 0 37 48"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          d="M27.0819 17H3.02508C1.91076 17 1.01376 17.9059 1.0485 19.0197C1.15761 22.5177 1.49703 29.7374 2.5 34C4.07125 40.6778 7.18553 44.8868 8.44856 46.3845C8.79051 46.79 9.29799 47 9.82843 47H20.0218C20.639 47 21.2193 46.7159 21.5659 46.2052C22.6765 44.5687 25.2312 40.4282 27.5 34C28.9757 29.8188 29.084 22.4043 29.0441 18.9156C29.0319 17.8436 28.1539 17 27.0819 17Z"
          stroke="#1e88e5"
          stroke-width="2"
        ></path>
        <path
          d="M29 23.5C29 23.5 34.5 20.5 35.5 25.4999C36.0986 28.4926 34.2033 31.5383 32 32.8713C29.4555 34.4108 28 34 28 34"
          stroke="#1e88e5"
          stroke-width="2"
        ></path>
        <path
          id="teabag"
          fill="#1e88e5"
          fill-rule="evenodd"
          clip-rule="evenodd"
          d="M16 25V17H14V25H12C10.3431 25 9 26.3431 9 28V34C9 35.6569 10.3431 37 12 37H18C19.6569 37 21 35.6569 21 34V28C21 26.3431 19.6569 25 18 25H16ZM11 28C11 27.4477 11.4477 27 12 27H18C18.5523 27 19 27.4477 19 28V34C19 34.5523 18.5523 35 18 35H12C11.4477 35 11 34.5523 11 34V28Z"
        ></path>
        <path
          id="steamL"
          d="M17 1C17 1 17 4.5 14 6.5C11 8.5 11 12 11 12"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke="#1e88e5"
        ></path>
        <path
          id="steamR"
          d="M21 6C21 6 21 8.22727 19 9.5C17 10.7727 17 13 17 13"
          stroke="#1e88e5"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        ></path>
      </svg>
    </div>
    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <div id="main-wrapper">
      <!-- ============================================================== -->
      <!-- Topbar header - style you can find in pages.scss -->
      <!-- ============================================================== -->
     <?php include("top-bar.php"); ?>
      <!-- ============================================================== -->
      <!-- End Topbar header -->
      <!-- ============================================================== -->
      <!-- ============================================================== -->
      <!-- Left Sidebar - style you can find in sidebar.scss  -->
      <!-- ============================================================== -->
      <?php include("left-sidebar.php"); ?>
      <!-- ============================================================== -->
      <!-- End Left Sidebar - style you can find in sidebar.scss  -->
      <!-- ============================================================== -->
      <!-- ============================================================== -->
      <!-- Page wrapper  -->
      <!-- ============================================================== -->
      <?php define('NURU_ADMIN_DASHBOARD', true); include("dashboard.php"); ?>
      <!-- ============================================================== -->
      <!-- End Page wrapper  -->
      <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- customizer Panel -->
    <!-- ============================================================== -->
    
    <div class="chat-windows"></div>
    <!-- ============================================================== -->
    <!-- All Jquery -->
    <!-- ============================================================== -->
    <script src="../../assets/libs/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="../../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- apps -->
    <script src="../../dist/js/app.min.js"></script>
    <script src="../../dist/js/app.init.js"></script>
    <script src="../../dist/js/app-style-switcher.js"></script>
    <!-- slimscrollbar scrollbar JavaScript -->
    <script src="../../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../../assets/extra-libs/sparkline/sparkline.js"></script>
    <!--Wave Effects -->
    <script src="../../dist/js/waves.js"></script>
    <!--Menu sidebar -->
    <script src="../../dist/js/sidebarmenu.js?v=20260720"></script>
    <!--Custom JavaScript -->
    <script src="../../dist/js/feather.min.js"></script>
    <script src="../../dist/js/custom.min.js"></script>
    <!--This page JavaScript -->
    <script src="../../assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <!--This page plugins -->
    <script src="../../assets/extra-libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <!-- start - This is for export functionality only -->
    <script src="../../dist/js/pages/datatable/datatable-advanced.init.js"></script>

 <script>
        // Chart data from PHP
        const buyersData = <?php echo $buyers_by_region_json; ?>;
        const sellersData = <?php echo $sellers_by_region_json; ?>;
        
        // Create Buyers Chart
        const buyersChartElement = document.querySelector('#buyersChart');
        if (buyersData.length > 0 && buyersChartElement) {
            const buyersOptions = {
                chart: {
                    type: 'donut',
                    height: 300
                },
                labels: buyersData.map(item => item.region),
                series: buyersData.map(item => parseInt(item.c)),
                colors: ['#1e88e5', '#43a047', '#ff9800', '#e53935', '#8e24aa', '#00acc1'],
                legend: {
                    position: 'bottom'
                }
            };
            
            new ApexCharts(buyersChartElement, buyersOptions).render();
        }
        
        // Create Sellers Chart
        const sellersChartElement = document.querySelector('#sellersChart');
        if (sellersData.length > 0 && sellersChartElement) {
            const sellersOptions = {
                chart: {
                    type: 'donut',
                    height: 300
                },
                labels: sellersData.map(item => item.region),
                series: sellersData.map(item => parseInt(item.c)),
                colors: ['#43a047', '#1e88e5', '#ff9800', '#e53935', '#8e24aa', '#00acc1'],
                legend: {
                    position: 'bottom'
                }
            };
            
            new ApexCharts(sellersChartElement, sellersOptions).render();
        }
        
        // Namibia Map Interactive Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const regions = document.querySelectorAll('#regions circle');
            const namibiaMap = document.getElementById('namibia-map');
            
            // Add hover effects and tooltips for regions
            regions.forEach(region => {
                const buyerCount = region.getAttribute('data-buyer-count');
                const sellerCount = region.getAttribute('data-seller-count');
                const regionName = region.getAttribute('data-region');
                
                // Create custom tooltip
                const tooltip = document.createElement('div');
                tooltip.style.cssText = `
                    position: absolute;
                    background: rgba(0,0,0,0.8);
                    color: white;
                    padding: 8px 12px;
                    border-radius: 6px;
                    font-size: 12px;
                    pointer-events: none;
                    z-index: 1000;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                    white-space: nowrap;
                `;
                tooltip.innerHTML = `
                    <strong>${regionName}</strong><br>
                    👥 Buyers: ${buyerCount}<br>
                    🏠 Sellers: ${sellerCount}<br>
                    📊 Total: ${parseInt(buyerCount) + parseInt(sellerCount)}
                `;
                namibiaMap.appendChild(tooltip);
                
                // Mouse events
                region.addEventListener('mouseenter', function(e) {
                    const rect = namibiaMap.getBoundingClientRect();
                    const cx = parseInt(region.getAttribute('cx'));
                    const cy = parseInt(region.getAttribute('cy'));
                    
                    // Calculate position relative to the map container
                    tooltip.style.left = (cx * namibiaMap.offsetWidth / 600 - tooltip.offsetWidth / 2) + 'px';
                    tooltip.style.top = (cy * namibiaMap.offsetHeight / 400 - 40) + 'px';
                    tooltip.style.opacity = '1';
                    
                    // Animate the region
                    region.style.transform = 'scale(1.2)';
                    region.style.transition = 'transform 0.3s ease';
                });
                
                region.addEventListener('mouseleave', function() {
                    tooltip.style.opacity = '0';
                    region.style.transform = 'scale(1)';
                });
            });
            
            // Add click functionality to show detailed info
            regions.forEach(region => {
                region.style.cursor = 'pointer';
                region.addEventListener('click', function() {
                    const regionName = region.getAttribute('data-region');
                    const buyerCount = region.getAttribute('data-buyer-count');
                    const sellerCount = region.getAttribute('data-seller-count');
                    
                    // Create modal or detailed view (you can enhance this)
                    alert(`📍 ${regionName} Region Details:\n\n👥 Buyers: ${buyerCount}\n🏠 Sellers: ${sellerCount}\n📊 Total Activity: ${parseInt(buyerCount) + parseInt(sellerCount)}`);
                });
            });
            
            // Add animated pulse effect to show activity
            setInterval(function() {
                regions.forEach(region => {
                    const buyerCount = parseInt(region.getAttribute('data-buyer-count'));
                    const intensity = Math.min(buyerCount / 50, 1); // Normalize intensity
                    
                    if (Math.random() < 0.1 * intensity) {
                        region.style.filter = 'brightness(1.5)';
                        setTimeout(() => {
                            region.style.filter = 'brightness(1)';
                        }, 500);
                    }
                });
            }, 2000);
        });
        
        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
		});
		
    </script>
  <script>
        // Namibia Map JavaScript
        const NamibiaMap = {
            svgContent: '',
            buyersLookup: {},
            sellersLookup: {},
            stats: {
                totalBuyers: 0,
                totalSellers: 0,
                activeRegions: 0
            },
            currentView: 'buyers',

            async loadNamibiaMap() {
                try {
                    
                    // Load the SVG file
                    const response = await fetch('na.svg');
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    this.svgContent = await response.text();
                    
                    // Parse SVG and inject into map container
                    this.initializeMapData();
                    this.injectSVG();
                    
                } catch (error) {
                    console.error("❌ Error loading SVG:", error);
                    this.loadFallbackMap();
                }
            },

            initializeMapData() {
                // Convert PHP data to lookup objects for fast access
                this.buyersLookup = {};
                this.sellersLookup = {};
                this.stats = { totalBuyers: 0, totalSellers: 0, activeRegions: 0 };

                buyersData.forEach(item => {
                    this.buyersLookup[item.region] = item.c;
                    this.stats.totalBuyers += item.c;
                });

                sellersData.forEach(item => {
                    this.sellersLookup[item.region] = item.c;
                    this.stats.totalSellers += item.c;
                });

                // Count active regions (regions with any data)
                const allRegions = new Set([...Object.keys(this.buyersLookup), ...Object.keys(this.sellersLookup)]);
                this.stats.activeRegions = allRegions.size;

            },

            injectSVG() {
                const parser = new DOMParser();
                const doc = parser.parseFromString(this.svgContent, 'image/svg+xml');
                const paths = doc.querySelectorAll('path');
                
                
                // Clear existing content
                const mapSvg = document.getElementById('namibia-map-svg');
                const featuresGroup = mapSvg.querySelector('#features');
                featuresGroup.innerHTML = '';

                // Add each path with styling
                let processedRegions = 0;
                paths.forEach((path, index) => {
                    // Copy path data
                    const newPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    newPath.setAttribute('d', path.getAttribute('d'));
                    
                    // Add region identifiers
                    const name = path.getAttribute('name') || path.getAttribute('id') || `Region ${index + 1}`;
                    const id = path.getAttribute('id') || `region-${index}`;
                    
                    newPath.setAttribute('class', 'map-region');
                    newPath.setAttribute('data-region', name);
                    newPath.setAttribute('id', id);
                    
                    // Add event listeners
                    this.addRegionEvents(newPath);
                    
                    featuresGroup.appendChild(newPath);
                    processedRegions++;
                });

                
                // Apply styling and labels
                this.styleMapRegions();
                this.addRegionLabels();
                
                // Hide loading
                document.getElementById('loading').style.display = 'none';
                
                // Update stats
                //this.updateStatsPanel();
            },

            addRegionEvents(path) {
                path.addEventListener('mouseenter', (e) => this.showTooltip(e));
                path.addEventListener('mouseleave', () => this.hideTooltip());
                path.addEventListener('mousemove', (e) => this.updateTooltipPosition(e));
                path.addEventListener('click', (e) => this.handleRegionClick(e));
            },

            styleMapRegions() {
                const regions = document.querySelectorAll('.map-region');
                
                regions.forEach(region => {
                    const regionName = region.getAttribute('data-region');
                    const buyers = this.buyersLookup[regionName] || 0;
                    const sellers = this.sellersLookup[regionName] || 0;
                    const total = buyers + sellers;
                    
                    // Apply color based on current view
                    let color;
                    switch(this.currentView) {
                        case 'buyers':
                            color = this.getColorForValue(buyers);
                            break;
                        case 'sellers':
                            color = this.getColorForValue(sellers);
                            break;
                        case 'combined':
                            color = this.getColorForValue(total);
                            break;
                    }
                    
                    region.style.fill = color;
                    
                    // Store data for tooltips
                    region.setAttribute('data-buyers', buyers);
                    region.setAttribute('data-sellers', sellers);
                    region.setAttribute('data-total', total);
                });
                
            },

            getColorForValue(value) {
                if (value === 0) return '#e9ecef'; // No data
                if (value <= 2) return '#c3e6cb'; // Low activity
                if (value <= 5) return '#8fd19e'; // Medium activity
                return '#52b788'; // High activity
            },

addRegionLabels() {
    const mapSvg = document.getElementById('namibia-map-svg');

    const regions = document.querySelectorAll('.map-region');
    regions.forEach(region => {
        const regionName = region.getAttribute('data-region');
        const buyers = this.buyersLookup[regionName] || 0;
        const sellers = this.sellersLookup[regionName] || 0;
        const total = buyers + sellers;

        //---- Only show label if region has some data ----//
        if (buyers > 0 || sellers > 0) {

            const bbox = region.getBBox();
            const centerX = bbox.x + bbox.width / 2;
            const centerY = bbox.y + bbox.height / 2;

            // Create a group for multi-line text
            const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            g.setAttribute('class', 'region-label');
            g.setAttribute('transform', `translate(${centerX}, ${centerY})`);
            g.setAttribute('text-anchor', 'middle');

            // Line 1: Region Name
            const line1 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            line1.setAttribute('y', -20);
            line1.setAttribute('font-size', '12');
            line1.setAttribute('fill', '#000');
            line1.textContent = regionName;
            g.appendChild(line1);

            // Line 2: Buyers (green)
            const line2 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            line2.setAttribute('y', 0);
            line2.setAttribute('font-size', '12');
            line2.setAttribute('fill', 'green');
            line2.textContent = `Buyers: ${buyers}`;
            g.appendChild(line2);

            // Line 3: Sellers (orange)
            const line3 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            line3.setAttribute('y', 15);
            line3.setAttribute('font-size', '12');
            line3.setAttribute('fill', 'orange');
            line3.textContent = `Sellers: ${sellers}`;
            g.appendChild(line3);

            // Line 4: Total (blue)
            const line4 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            line4.setAttribute('y', 30);
            line4.setAttribute('font-size', '12');
            line4.setAttribute('fill', '#007bff');
            line4.textContent = `Total: ${total}`;
            g.appendChild(line4);

            // Append the group
            mapSvg.appendChild(g);
        }
    });

}
,

            clearLabels() {
                const labels = document.querySelectorAll('.region-label');
                labels.forEach(label => label.remove());
            },

            toggleView(view) {
                this.currentView = view;
                
                // Update button states
                document.querySelectorAll('.map-controls .btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                event.target.classList.add('active');
                
                // Clear existing labels
                this.clearLabels();
                
                // Restyle regions
                this.styleMapRegions();
                
                // Add new labels
                this.addRegionLabels();
                
                // Update stats
                //this.updateStatsPanel();
                
            },

            showTooltip(e) {
                const region = e.target;
                const regionName = region.getAttribute('data-region');
                const buyers = region.getAttribute('data-buyers');
                const sellers = region.getAttribute('data-sellers');
                const total = region.getAttribute('data-total');
                
                const tooltip = document.getElementById('map-tooltip');
                tooltip.innerHTML = `
                `;
                tooltip.classList.add('show');
                this.updateTooltipPosition(e);
            },

            hideTooltip() {
                document.getElementById('map-tooltip').classList.remove('show');
            },

            updateTooltipPosition(e) {
                const tooltip = document.getElementById('map-tooltip');
                tooltip.style.left = e.pageX + 'px';
                tooltip.style.top = (e.pageY - 10) + 'px';
            },

            handleRegionClick(e) {
                const region = e.target;
                region.classList.toggle('active');
                
            },



            loadFallbackMap() {
                
                // Simple fallback SVG for testing
                const mapSvg = document.getElementById('namibia-map-svg');
                const featuresGroup = mapSvg.querySelector('#features');
                
                featuresGroup.innerHTML = `
                    <text x="400" y="400" text-anchor="middle" font-size="20" fill="#666">
                        Map Loading Failed<br>
                        Please check namibia.svg file
                    </text>
                `;
                
                document.getElementById('loading').style.display = 'none';
            }
        };

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            NamibiaMap.loadNamibiaMap();
        });
    </script>

  </body>
</html>
