<?php
/**
 * Main Landing Page
 * Mega School Plaza Management System
 */

require_once 'config/config.php';

$page_title = 'Home';
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-building text-2xl" style="color: var(--primary);"></i>
                    <div>
                        <h1 class="text-xl font-heading text-gray-900"><?php echo APP_NAME; ?></h1>
                        <p class="text-xs text-gray-500">Shop Management Platform</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>
                        Complete System
                    </span>
                    <a href="auth/login.php" class="btn-primary">Get Started</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative py-20 lg:py-32" style="background: linear-gradient(135deg, var(--background) 0%, var(--muted) 50%, rgba(212, 165, 116, 0.1) 100%);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="text-center lg:text-left">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border border-orange-200 text-orange-800 bg-orange-50 mb-6">
                        🚀 Complete PHP Solution
                    </span>
                    <h1 class="text-4xl lg:text-6xl font-heading text-gray-900 mb-6 leading-tight">
                        Transform Your <span style="color: var(--primary);">Shop Rental</span> Management
                    </h1>
                    <p class="text-xl text-gray-600 mb-8 max-w-2xl leading-relaxed">
                        A comprehensive platform that streamlines shop rentals, tenant management, billing, and analytics. Built
                        with modern PHP architecture and designed for scalability.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start items-center">
                        <a href="auth/login.php" class="btn-primary text-lg px-8 py-3">
                            Try Live Demo
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                        <button class="btn-outline text-lg px-8 py-3">
                            <i class="fas fa-play mr-2"></i>
                            Watch Overview
                        </button>
                    </div>
                </div>

                <div class="relative">
                    <div class="relative rounded-2xl overflow-hidden shadow-2xl">
                        <img
                            src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/WhatsApp%20Image%202025-08-18%20at%2015.32.02_cf6f3660.jpg-EwG9oDZdThSgO2f4J9G9HNo6r2Jij3.jpeg"
                            alt="Mega School Plaza - Modern Commercial Complex"
                            class="w-full h-96 object-cover"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                        <div class="absolute bottom-4 left-4 right-4">
                            <div class="bg-white/90 backdrop-blur-sm rounded-lg p-4">
                                <div class="flex items-center text-sm text-gray-700">
                                    <i class="fas fa-map-marker-alt mr-2" style="color: var(--primary);"></i>
                                    <span class="font-medium">Mega School Plaza - Active Commercial Hub</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="text-3xl lg:text-4xl font-bold mb-2" style="color: var(--primary);">150+</div>
                    <div class="text-lg font-semibold text-gray-900 mb-1">Active Shops</div>
                    <div class="text-sm text-gray-500">Successfully managed</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl lg:text-4xl font-bold mb-2" style="color: var(--primary);">500+</div>
                    <div class="text-lg font-semibold text-gray-900 mb-1">Happy Tenants</div>
                    <div class="text-sm text-gray-500">Across all locations</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl lg:text-4xl font-bold mb-2" style="color: var(--primary);">80%</div>
                    <div class="text-lg font-semibold text-gray-900 mb-1">Time Saved</div>
                    <div class="text-sm text-gray-500">In administrative tasks</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl lg:text-4xl font-bold mb-2" style="color: var(--primary);">25%</div>
                    <div class="text-lg font-semibold text-gray-900 mb-1">Revenue Growth</div>
                    <div class="text-sm text-gray-500">Average increase</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20" style="background-color: var(--muted);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border border-orange-200 text-orange-800 bg-orange-50 mb-4">
                    ✨ Powerful Features
                </span>
                <h2 class="text-3xl lg:text-4xl font-heading text-gray-900 mb-4">
                    Everything You Need to Manage Your Plaza
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    From tenant applications to financial reporting, our platform handles every aspect of shop rental
                    management.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php
                $features = [
                    [
                        'title' => 'Smart User Management',
                        'description' => 'Role-based access control with secure authentication for tenants, managers, and administrators.',
                        'icon' => 'fas fa-users',
                        'color' => 'var(--primary)'
                    ],
                    [
                        'title' => 'Interactive Shop Catalog',
                        'description' => 'Advanced filtering, search, and detailed shop information with high-quality images and amenities.',
                        'icon' => 'fas fa-building',
                        'color' => 'var(--accent)'
                    ],
                    [
                        'title' => 'Streamlined Applications',
                        'description' => 'Digital application process with document upload, real-time status tracking, and automated workflows.',
                        'icon' => 'fas fa-file-alt',
                        'color' => 'var(--secondary)'
                    ],
                    [
                        'title' => 'Automated Billing',
                        'description' => 'Invoice generation, payment processing, and financial tracking with overdue management.',
                        'icon' => 'fas fa-credit-card',
                        'color' => '#c4956c'
                    ],
                    [
                        'title' => 'Analytics Dashboard',
                        'description' => 'Real-time insights, comprehensive reporting, and management tools with actionable data.',
                        'icon' => 'fas fa-chart-bar',
                        'color' => '#a68b5b'
                    ],
                    [
                        'title' => 'Enterprise Security',
                        'description' => 'CSRF protection, audit logging, and secure file handling with comprehensive data protection.',
                        'icon' => 'fas fa-shield-alt',
                        'color' => 'var(--primary)'
                    ]
                ];

                foreach ($features as $feature): ?>
                    <div class="card hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4" style="background-color: var(--muted);">
                            <i class="<?php echo $feature['icon']; ?> text-xl" style="color: <?php echo $feature['color']; ?>;"></i>
                        </div>
                        <h3 class="text-xl font-heading text-gray-900 mb-3"><?php echo $feature['title']; ?></h3>
                        <p class="text-gray-600 leading-relaxed"><?php echo $feature['description']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl lg:text-4xl font-heading text-white mb-4">
                Ready to Transform Your Business?
            </h2>
            <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                Join hundreds of property managers who have streamlined their operations with our comprehensive platform.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="auth/login.php" class="bg-white text-gray-900 px-8 py-3 rounded-lg font-semibold text-lg hover:bg-gray-100 transition-colors">
                    Try Demo Now
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
                <a href="auth/register.php" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold text-lg hover:bg-white hover:text-gray-900 transition-colors">
                    Get Started Free
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-3 mb-4">
                        <i class="fas fa-building text-2xl" style="color: var(--primary);"></i>
                        <div>
                            <h3 class="text-xl font-heading text-gray-900"><?php echo APP_NAME; ?></h3>
                            <p class="text-sm text-gray-500">Shop Management Platform</p>
                        </div>
                    </div>
                    <p class="text-gray-600 max-w-md">
                        Complete shop rental and management solution built with modern PHP architecture, designed for
                        scalability and ease of use.
                    </p>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 mb-4">Features</h4>
                    <ul class="space-y-2 text-gray-600">
                        <li>User Management</li>
                        <li>Shop Catalog</li>
                        <li>Application System</li>
                        <li>Billing & Payments</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 mb-4">Technology</h4>
                    <ul class="space-y-2 text-gray-600">
                        <li>PHP 8+</li>
                        <li>MySQL Database</li>
                        <li>Responsive Design</li>
                        <li>Modern Architecture</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-200 mt-8 pt-8 text-center text-gray-500">
                <p>&copy; 2024 <?php echo APP_NAME; ?>. Built with modern web technologies.</p>
            </div>
        </div>
    </footer>
</div>

<?php include 'includes/footer.php'; ?>
