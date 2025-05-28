// Enhanced Menu Renderer with Arabic Support
// Add this to your menu.html or create a new file: assets/js/menu-renderer.js

// Enhanced menu rendering functions that respect language settings
function renderMenuItemCard(item, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const currentLang = window.languageManager?.getCurrentLanguage() || 'en';
    
    // Get localized content
    const itemName = currentLang === 'ar' && item.nameAr ? item.nameAr : item.name;
    const itemDescription = currentLang === 'ar' && item.descriptionAr ? item.descriptionAr : item.description;
    const itemCategory = currentLang === 'ar' && item.categoryAr ? item.categoryAr : item.category;
    
    // Determine price display
    let priceDisplay;
    if (item.isHalfFull) {
        priceDisplay = `${formatPrice(item.halfPrice)} - ${formatPrice(item.fullPrice)}`;
    } else {
        priceDisplay = formatPrice(item.price);
    }
    
    // Popular badge text
    const popularText = currentLang === 'ar' ? 'شائع' : 'Popular';
    
    // Popular badge
    const popularBadge = item.isPopular ? 
        `<span class="absolute top-2 left-2 bg-orange-500 text-white text-xs px-2 py-1 rounded-full">${popularText}</span>` : '';
    
    const menuItem = document.createElement('div');
    menuItem.className = 'menu-item menu-card rounded-xl shadow-sm overflow-hidden flex cursor-pointer';
    menuItem.onclick = () => {
        if (window.pageTransition) {
            window.pageTransition(`menu-item-details.html?id=${item.id}`);
        } else {
            window.location.href = `menu-item-details.html?id=${item.id}`;
        }
    };
    
    menuItem.innerHTML = `
        <div class="w-24 h-24 flex-shrink-0 relative">
            <img src="${item.image}" alt="${itemName}" class="w-full h-full object-cover">
            ${popularBadge}
        </div>
        <div class="p-3 flex-1 flex flex-col justify-between">
            <div>
                <h3 class="font-medium text-sm mb-0.5 line-clamp-1" data-item-name="${item.id}">${itemName}</h3>
                <p class="text-gray-500 text-xs line-clamp-2 mb-2" data-item-description="${item.id}">${itemDescription}</p>
                <p class="text-gray-400 text-xs" data-item-category="${item.id}">${itemCategory}</p>
            </div>
            <div class="flex justify-between items-center mt-2">
                <span class="price-badge">${priceDisplay}</span>
                <button class="add-to-cart-btn w-8 h-8 rounded-full bg-primary/10 hover:bg-primary/20 flex items-center justify-center transition-colors" data-id="${item.id}">
                    <i data-feather="plus" class="h-4 w-4 text-primary"></i>
                </button>
            </div>
        </div>
    `;
    
    return menuItem;
}

// Enhanced function to render multiple menu items with language support
function renderMenuItems(items, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = '';
    
    if (items.length === 0) {
        const currentLang = window.languageManager?.getCurrentLanguage() || 'en';
        const noItemsText = currentLang === 'ar' ? 'لا توجد عناصر' : 'No items found';
        
        container.innerHTML = `
            <div class="text-center py-8">
                <p class="text-gray-500">${noItemsText}</p>
            </div>
        `;
        return;
    }
    
    const itemsGrid = document.createElement('div');
    itemsGrid.className = 'grid grid-cols-1 gap-4';
    
    items.forEach(item => {
        const menuItem = renderMenuItemCard(item);
        itemsGrid.appendChild(menuItem);
    });
    
    container.appendChild(itemsGrid);
    
    // Re-initialize feather icons
    if (window.feather) {
        window.feather.replace();
    }
    
    // Setup add to cart buttons
    setupAddToCartButtons();
}

// Enhanced function to render special/popular items
function renderSpecialItems(items, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = '';
    
    if (items.length === 0) return;
    
    const currentLang = window.languageManager?.getCurrentLanguage() || 'en';
    
    const itemsWrapper = document.createElement('div');
    itemsWrapper.className = 'flex space-x-3 overflow-x-auto py-1 special-scroll';
    
    items.forEach(item => {
        // Get localized content
        const itemName = currentLang === 'ar' && item.nameAr ? item.nameAr : item.name;
        const itemCategory = currentLang === 'ar' && item.categoryAr ? item.categoryAr : item.category;
        
        const price = item.isHalfFull ? formatPrice(item.halfPrice) : formatPrice(item.price);
        
        const specialItem = document.createElement('div');
        specialItem.className = 'flex-shrink-0 w-36 rounded-lg overflow-hidden special-item shadow-sm bg-white';
        specialItem.onclick = () => {
            if (window.pageTransition) {
                window.pageTransition(`menu-item-details.html?id=${item.id}`);
            } else {
                window.location.href = `menu-item-details.html?id=${item.id}`;
            }
        };
        
        specialItem.innerHTML = `
            <div class="h-24 overflow-hidden">
                <img src="${item.image}" alt="${itemName}" class="w-full h-full object-cover">
            </div>
            <div class="p-2.5">
                <h3 class="font-medium text-sm leading-tight line-clamp-1" data-item-name="${item.id}">${itemName}</h3>
                <p class="text-gray-500 text-xs mt-0.5 line-clamp-1" data-item-category="${item.id}">${itemCategory}</p>
                <div class="flex justify-between items-center mt-2">
                    <span class="text-primary font-semibold text-sm">${price}</span>
                </div>
            </div>
        `;
        
        itemsWrapper.appendChild(specialItem);
    });
    
    container.appendChild(itemsWrapper);
}

// Function to update existing menu items when language changes
function updateMenuItemsLanguage() {
    const currentLang = window.languageManager?.getCurrentLanguage() || 'en';
    
    // Update all menu item names
    document.querySelectorAll('[data-item-name]').forEach(element => {
        const itemId = parseInt(element.getAttribute('data-item-name'));
        if (window.menuManager && window.menuManager.menuData) {
            const item = window.menuManager.menuData.find(i => i.id === itemId);
            if (item) {
                const itemName = currentLang === 'ar' && item.nameAr ? item.nameAr : item.name;
                element.textContent = itemName;
            }
        }
    });
    
    // Update all menu item descriptions
    document.querySelectorAll('[data-item-description]').forEach(element => {
        const itemId = parseInt(element.getAttribute('data-item-description'));
        if (window.menuManager && window.menuManager.menuData) {
            const item = window.menuManager.menuData.find(i => i.id === itemId);
            if (item) {
                const itemDescription = currentLang === 'ar' && item.descriptionAr ? item.descriptionAr : item.description;
                element.textContent = itemDescription;
            }
        }
    });
    
    // Update all menu item categories
    document.querySelectorAll('[data-item-category]').forEach(element => {
        const itemId = parseInt(element.getAttribute('data-item-category'));
        if (window.menuManager && window.menuManager.menuData) {
            const item = window.menuManager.menuData.find(i => i.id === itemId);
            if (item) {
                const itemCategory = currentLang === 'ar' && item.categoryAr ? item.categoryAr : item.category;
                element.textContent = itemCategory;
            }
        }
    });
    
    // Update popular badges
    document.querySelectorAll('.absolute.top-2.left-2').forEach(badge => {
        if (badge.textContent.includes('Popular') || badge.textContent.includes('شائع')) {
            const popularText = currentLang === 'ar' ? 'شائع' : 'Popular';
            badge.textContent = popularText;
        }
    });
}

// Enhanced category rendering with language support
function renderCategoriesWithLanguage(categories) {
    const categoryContainer = document.getElementById('category-buttons');
    if (!categoryContainer) return;
    
    const currentLang = window.languageManager?.getCurrentLanguage() || 'en';
    
    // Clear existing buttons
    categoryContainer.innerHTML = '';
    
    categories.forEach(category => {
        const button = document.createElement('button');
        button.className = 'category-btn px-4 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-700 scale-button border border-gray-200 hover:border-primary/30 transition-all';
        
        // Set category text based on language
        const categoryText = window.languageManager?.getCategoryTranslation(category, currentLang) || category;
        button.textContent = categoryText;
        button.setAttribute('data-category', category);
        button.setAttribute('data-category-en', category);
        
        // Add event listener
        button.addEventListener('click', function() {
            // Activate this category button
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.remove('active', 'bg-primary', 'text-white', 'border-primary');
                btn.classList.add('bg-gray-100', 'text-gray-700', 'border-gray-200');
            });
            
            this.classList.add('active', 'bg-primary', 'text-white', 'border-primary');
            this.classList.remove('bg-gray-100', 'text-gray-700', 'border-gray-200');
            
            // Filter items by category
            if (window.menuManager) {
                const items = window.menuManager.getItemsByCategory(category);
                renderMenuItems(items, 'menu-items-container');
                
                // Hide popular section when filtering
                const popularSection = document.getElementById('popular-section');
                if (popularSection) {
                    popularSection.classList.add('hidden');
                }
                
                // Update section title
                const sectionTitle = document.querySelector('#all-menu-title');
                if (sectionTitle) {
                    sectionTitle.textContent = categoryText;
                }
            }
        });
        
        categoryContainer.appendChild(button);
    });
}

// Enhanced setup for add to cart buttons
function setupAddToCartButtons() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    addToCartButtons.forEach(button => {
        // Remove existing listeners by cloning
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
        
        newButton.addEventListener('click', function(e) {
            e.stopPropagation();
            
            const itemId = this.getAttribute('data-id');
            if (window.menuManager && window.cartManager) {
                const item = window.menuManager.getItemById(itemId);
                
                if (item) {
                    let variants = {};
                    if (item.isHalfFull) {
                        variants.size = 'half';
                    }
                    
                    // Add to cart
                    window.cartManager.addToCart(item, 1, variants);
                    
                    // Show language-appropriate toast
                    const currentLang = window.languageManager?.getCurrentLanguage() || 'en';
                    const message = currentLang === 'ar' ? 'تمت الإضافة إلى السلة' : 'Added to cart';
                    
                    if (window.toast) {
                        window.toast.show(message, 'success');
                    }
                    
                    // Update cart badge
                    if (window.cartManager.updateCartBadge) {
                        window.cartManager.updateCartBadge();
                    }
                    
                    // Visual feedback
                    this.classList.add('animate-pulse');
                    setTimeout(() => {
                        this.classList.remove('animate-pulse');
                    }, 600);
                }
            }
        });
    });
}

// Listen for language changes and update menu items
window.addEventListener('languageChanged', function(e) {
    updateMenuItemsLanguage();
});

// Export functions for global use
window.renderMenuItems = renderMenuItems;
window.renderSpecialItems = renderSpecialItems;
window.renderCategoriesWithLanguage = renderCategoriesWithLanguage;
window.updateMenuItemsLanguage = updateMenuItemsLanguage;