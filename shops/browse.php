<?php
/**
 * Shop Browsing (Public/Tenant)
 * Mega School Plaza Management System
 */

require_once '../config/config.php';

$page_title = 'Browse Shops';

// Get shops with pagination
$database = new Database();
$db = $database->getConnection();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Build query with filters
$where_conditions = ["s.status = 'available'"]; // Only show available shops
$params = [];

if (!empty($_GET['category'])) {
    $where_conditions[] = "s.category_id = :category_id";
    $params[':category_id'] = $_GET['category'];
}

if (!empty($_GET['min_rent'])) {
    $where_conditions[] = "s.monthly_rent >= :min_rent";
    $params[':min_rent'] = $_GET['min_rent'];
}

if (!empty($_GET['max_rent'])) {
    $where_conditions[] = "s.monthly_rent <= :max_rent";
    $params[':max_rent'] = $_GET['max_rent'];
}

if (!empty($_GET['min_area'])) {
    $where_conditions[] = "s.area_sqft >= :min_area";
    $params[':min_area'] = $_GET['min_area'];
}

if (!empty($_GET['max_area'])) {
    $where_conditions[] = "s.area_sqft <= :max_area";
    $params[':max_area'] = $_GET['max_area'];
}

if (!empty($_GET['search'])) {
    $where_conditions[] = "(s.title LIKE :search OR s.description LIKE :search)";
    $params[':search'] = '%' . $_GET['search'] . '%';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM shops s 
                LEFT JOIN categories c ON s.category_id = c.id 
                $where_clause";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_shops = $count_stmt->fetch()['total'];
$total_pages = ceil($total_shops / ITEMS_PER_PAGE);

// Get shops
$query = "SELECT s.*, c.name as category_name, c.icon as category_icon, c.color as category_color
          FROM shops s 
          LEFT JOIN categories c ON s.category_id = c.id 
          $where_clause
          ORDER BY s.monthly_rent ASC 
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$shops = $stmt->fetchAll();

// Get categories for filter
$categories = $db->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order, name")->fetchAll();

// Get amenities for each shop
foreach ($shops as &$shop) {
    $amenity_query = "SELECT a.name, a.icon FROM shop_amenities sa 
                      JOIN amenities a ON sa.amenity_id = a.id 
                      WHERE sa.shop_id = :shop_id AND a.status = 'active'";
    $amenity_stmt = $db->prepare($amenity_query);
    $amenity_stmt->bindParam(':shop_id', $shop['id']);
    $amenity_stmt->execute();
    $shop['amenities'] = $amenity_stmt->fetchAll();
}

include '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <a href="../index.php" class="flex items-center space-x-3">
                        <i class="fas fa-building text-2xl" style="color: var(--primary);"></i>
                        <div>
                            <h1 class="text-xl font-heading text-gray-900"><?php echo APP_NAME; ?></h1>
                            <p class="text-xs text-gray-500">Browse Available Shops</p>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <?php if (is_logged_in()): ?>
                        <a href="../dashboard/index.php" class="text-sm text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="../auth/logout.php" class="btn-outline">Logout</a>
                    <?php else: ?>
                        <a href="../auth/login.php" class="text-sm text-gray-600 hover:text-gray-900">Login</a>
                        <a href="../auth/register.php" class="btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="py-16" style="background: linear-gradient(135deg, var(--background) 0%, var(--muted) 100%);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl lg:text-5xl font-heading text-gray-900 mb-4">
                Find Your Perfect <span style="color: var(--primary);">Shop Space</span>
            </h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto mb-8">
                Discover premium retail and office spaces in our modern plaza. From boutique stores to professional offices, find the perfect location for your business.
            </p>
            <div class="flex justify-center">
                <div class="bg-white rounded-lg p-2 shadow-lg">
                    <form method="GET" class="flex items-center space-x-4">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Search shops..." 
                            class="form-input border-0 focus:ring-0" 
                            value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                        >
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters Sidebar -->
            <div class="lg:w-1/4">
                <div class="card sticky top-24">
                    <h3 class="text-lg font-heading text-gray-900 mb-4">Filter Shops</h3>
                    
                    <form method="GET">
                        <!-- Preserve search term -->
                        <?php if (!empty($_GET['search'])): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
                        <?php endif; ?>
                        
                        <!-- Category Filter -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select name="category" class="form-input">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($_GET['category'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Rent Range -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Rent</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input 
                                    type="number" 
                                    name="min_rent" 
                                    placeholder="Min" 
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($_GET['min_rent'] ?? ''); ?>"
                                >
                                <input 
                                    type="number" 
                                    name="max_rent" 
                                    placeholder="Max" 
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($_GET['max_rent'] ?? ''); ?>"
                                >
                            </div>
                        </div>

                        <!-- Area Range -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Area (sq ft)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input 
                                    type="number" 
                                    name="min_area" 
                                    placeholder="Min" 
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($_GET['min_area'] ?? ''); ?>"
                                >
                                <input 
                                    type="number" 
                                    name="max_area" 
                                    placeholder="Max" 
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($_GET['max_area'] ?? ''); ?>"
                                >
                            </div>
                        </div>

                        <div class="space-y-2">
                            <button type="submit" class="btn-primary w-full">
                                <i class="fas fa-filter mr-2"></i>Apply Filters
                            </button>
                            <a href="browse.php" class="btn-outline w-full text-center">Clear All</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Shops Grid -->
            <div class="lg:w-3/4">
                <!-- Results Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-heading text-gray-900">Available Shops</h2>
                        <p class="text-gray-600"><?php echo $total_shops; ?> shops found</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">Sort by:</span>
                        <select class="form-input text-sm" onchange="window.location.href='?sort=' + this.value + '&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'sort'; }, ARRAY_FILTER_USE_KEY)); ?>'">
                            <option value="rent_asc" <?php echo ($_GET['sort'] ?? 'rent_asc') === 'rent_asc' ? 'selected' : ''; ?>>Rent: Low to High</option>
                            <option value="rent_desc" <?php echo ($_GET['sort'] ?? '') === 'rent_desc' ? 'selected' : ''; ?>>Rent: High to Low</option>
                            <option value="area_asc" <?php echo ($_GET['sort'] ?? '') === 'area_asc' ? 'selected' : ''; ?>>Area: Small to Large</option>
                            <option value="area_desc" <?php echo ($_GET['sort'] ?? '') === 'area_desc' ? 'selected' : ''; ?>>Area: Large to Small</option>
                        </select>
                    </div>
                </div>

                <!-- Shops Grid -->
                <?php if (empty($shops)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-heading text-gray-900 mb-2">No shops found</h3>
                        <p class="text-gray-600 mb-4">Try adjusting your search criteria or browse all available shops.</p>
                        <a href="browse.php" class="btn-primary">View All Shops</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <?php foreach ($shops as $shop): ?>
                            <div class="card hover:shadow-lg transition-all duration-300">
                                <!-- Shop Image Placeholder -->
                                <div class="h-48 bg-gray-200 rounded-lg mb-4 flex items-center justify-center">
                                    <i class="fas fa-building text-4xl text-gray-400"></i>
                                </div>

                                <!-- Category Badge -->
                                <?php if ($shop['category_name']): ?>
                                    <div class="flex items-center mb-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="<?php echo $shop['category_icon']; ?> mr-1"></i>
                                            <?php echo htmlspecialchars($shop['category_name']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <!-- Shop Info -->
                                <div class="mb-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="text-lg font-heading text-gray-900">
                                            <?php echo htmlspecialchars($shop['title']); ?>
                                        </h3>
                                        <span class="text-lg font-bold" style="color: var(--primary);">
                                            <?php echo format_currency($shop['monthly_rent']); ?>/mo
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-3">
                                        Shop <?php echo htmlspecialchars($shop['shop_number']); ?> • Floor <?php echo $shop['floor_number']; ?>
                                    </p>
                                    <p class="text-gray-700 text-sm mb-3">
                                        <?php echo htmlspecialchars(substr($shop['description'], 0, 120)); ?>...
                                    </p>
                                </div>

                                <!-- Shop Details -->
                                <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-ruler-combined mr-2"></i>
                                        <?php echo number_format($shop['area_sqft']); ?> sq ft
                                    </div>
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-car mr-2"></i>
                                        <?php echo $shop['parking_spaces']; ?> parking
                                    </div>
                                    <?php if ($shop['air_conditioning']): ?>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-snowflake mr-2"></i>
                                            A/C included
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($shop['internet_ready']): ?>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-wifi mr-2"></i>
                                            Internet ready
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Amenities -->
                                <?php if (!empty($shop['amenities'])): ?>
                                    <div class="mb-4">
                                        <p class="text-xs font-medium text-gray-500 mb-2">Amenities:</p>
                                        <div class="flex flex-wrap gap-1">
                                            <?php foreach (array_slice($shop['amenities'], 0, 4) as $amenity): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700">
                                                    <i class="<?php echo $amenity['icon']; ?> mr-1"></i>
                                                    <?php echo htmlspecialchars($amenity['name']); ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($shop['amenities']) > 4): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700">
                                                    +<?php echo count($shop['amenities']) - 4; ?> more
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Actions -->
                                <div class="flex space-x-2">
                                    <a href="view-shop.php?id=<?php echo $shop['id']; ?>" class="flex-1 btn-outline text-center">
                                        <i class="fas fa-eye mr-2"></i>View Details
                                    </a>
                                    <?php if (is_logged_in()): ?>
                                        <a href="../applications/apply.php?shop_id=<?php echo $shop['id']; ?>" class="flex-1 btn-primary text-center">
                                            <i class="fas fa-paper-plane mr-2"></i>Apply Now
                                        </a>
                                    <?php else: ?>
                                        <a href="../auth/login.php?redirect=<?php echo urlencode('applications/apply.php?shop_id=' . $shop['id']); ?>" class="flex-1 btn-primary text-center">
                                            <i class="fas fa-sign-in-alt mr-2"></i>Login to Apply
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="flex justify-center">
                            <nav class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Previous
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" 
                                       class="px-3 py-2 text-sm font-medium <?php echo $i === $page ? 'text-white bg-blue-600' : 'text-gray-500 bg-white hover:bg-gray-50'; ?> border border-gray-300 rounded-md">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
