// Search Modal Implementation
// This file should be placed in assets/js/search-modal.js

// Create and inject the search modal HTML into the document
function createSearchModal() {
  // Check if modal already exists
  if (document.getElementById('search-modal')) return;

  // Create modal container
  const modalHTML = `
    <div id="search-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden fade-in">
      <div class="absolute top-0 left-0 right-0 bg-white rounded-b-2xl p-4 slide-in">
        <div class="flex items-center mb-4">
          <button id="close-search-modal" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mr-3">
            <i data-feather="arrow-left" class="h-5 w-5 text-gray-600"></i>
          </button>
          <div class="relative flex-1">
            <input type="text" id="global-search-input" class="w-full bg-gray-100 rounded-xl py-3 pl-10 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Search for foods, drinks...">
            <i data-feather="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400"></i>
          </div>
        </div>
        
        <!-- Recent searches -->
        <div class="mb-4">
          <h3 class="text-sm font-medium text-gray-700 mb-2">Recent Searches</h3>
          <div class="flex flex-wrap gap-2" id="recent-searches">
            <span class="px-3 py-1.5 bg-gray-100 rounded-full text-sm">Burger</span>
            <span class="px-3 py-1.5 bg-gray-100 rounded-full text-sm">Pizza</span>
            <span class="px-3 py-1.5 bg-gray-100 rounded-full text-sm">Biryani</span>
          </div>
        </div>
        
        <!-- Popular categories -->
        <div class="mb-4">
          <h3 class="text-sm font-medium text-gray-700 mb-2">Popular Categories</h3>
          <div class="flex overflow-x-auto py-1 space-x-3 special-scroll">
            <a href="menu.html?category=Burgers" class="category-chip px-4 py-2 bg-primary/10 text-primary rounded-full text-sm whitespace-nowrap">Burgers</a>
            <a href="menu.html?category=Indian" class="category-chip px-4 py-2 bg-gray-100 rounded-full text-sm whitespace-nowrap">Indian</a>
            <a href="menu.html?category=Pizzas" class="category-chip px-4 py-2 bg-gray-100 rounded-full text-sm whitespace-nowrap">Pizzas</a>
            <a href="menu.html?category=Italian" class="category-chip px-4 py-2 bg-gray-100 rounded-full text-sm whitespace-nowrap">Italian</a>
            <a href="menu.html?category=Desserts" class="category-chip px-4 py-2 bg-gray-100 rounded-full text-sm whitespace-nowrap">Desserts</a>
          </div>
        </div>
        
        <!-- Search results -->
        <div id="search-results" class="max-h-[60vh] overflow-y-auto">
          <!-- Results will be dynamically populated here -->
        </div>
      </div>
    </div>
  `;
  
  // Inject modal into the DOM
  const modalContainer = document.createElement('div');
  modalContainer.innerHTML = modalHTML;
  document.body.appendChild(modalContainer.firstElementChild);
  
  // Initialize Feather icons
  if (window.feather) {
    feather.replace(document.querySelectorAll('#search-modal [data-feather]'));
  }
  
  // Add event listeners
  setupSearchModalListeners();
}

// Set up event listeners for the search modal
function setupSearchModalListeners() {
  const searchModal = document.getElementById('search-modal');
  const closeButton = document.getElementById('close-search-modal');
  const searchInput = document.getElementById('global-search-input');
  const searchResults = document.getElementById('search-results');
  const recentSearches = document.getElementById('recent-searches');
  
  // Close button event
  closeButton.addEventListener('click', function() {
    searchModal.classList.add('hidden');
    document.body.style.overflow = 'auto';
  });
  
  // Close when clicking outside the modal content
  searchModal.addEventListener('click', function(e) {
    if (e.target === searchModal) {
      searchModal.classList.add('hidden');
      document.body.style.overflow = 'auto';
    }
  });
  
  // Search input event with debounce
  let debounceTimeout;
  searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
      performSearch(this.value);
    }, 300);
  });
  
  // Recent search chip clicks
  const searchChips = recentSearches.querySelectorAll('span');
  searchChips.forEach(chip => {
    chip.addEventListener('click', function() {
      searchInput.value = this.textContent;
      performSearch(this.textContent);
    });
  });
}

// Perform search and display results
async function performSearch(query) {
  if (!query || query.trim() === '') {
    document.getElementById('search-results').innerHTML = '';
    return;
  }
  
  try {
    // Show loading state
    document.getElementById('search-results').innerHTML = `
      <div class="animate-pulse py-4">
        <div class="h-24 rounded-xl bg-white shadow-sm overflow-hidden mb-3">
          <div class="flex">
            <div class="w-24 h-24 shimmer"></div>
            <div class="flex-1 p-3">
              <div class="h-4 w-3/4 shimmer rounded mb-2"></div>
              <div class="h-3 w-1/2 shimmer rounded mb-3"></div>
              <div class="h-4 w-1/4 shimmer rounded"></div>
            </div>
          </div>
        </div>
        <div class="h-24 rounded-xl bg-white shadow-sm overflow-hidden">
          <div class="flex">
            <div class="w-24 h-24 shimmer"></div>
            <div class="flex-1 p-3">
              <div class="h-4 w-3/4 shimmer rounded mb-2"></div>
              <div class="h-3 w-1/2 shimmer rounded mb-3"></div>
              <div class="h-4 w-1/4 shimmer rounded"></div>
            </div>
          </div>
        </div>
      </div>
    `;
    
    // Load menu data
    let menuData;
    try {
      // Try to use menuManager if available
      if (window.menuManager && typeof window.menuManager.loadMenuData === 'function') {
        await window.menuManager.loadMenuData();
        menuData = window.menuManager.menuData;
      } else {
        // Fallback to direct fetch
        const response = await fetch('data/menu.json');
        if (!response.ok) throw new Error('Failed to load menu data');
        menuData = await response.json();
      }
    } catch (error) {
      console.error('Error loading menu data:', error);
      document.getElementById('search-results').innerHTML = `
        <div class="py-8 text-center">
          <p class="text-gray-500">Failed to load menu data. Please try again.</p>
        </div>
      `;
      return;
    }
    
    // Filter items based on search query
    const searchTerm = query.toLowerCase().trim();
    const filteredItems = menuData.filter(item => {
      return (
        item.name.toLowerCase().includes(searchTerm) || 
        item.description.toLowerCase().includes(searchTerm) || 
        item.category.toLowerCase().includes(searchTerm)
      );
    });
    
    // Create and display search results
    if (filteredItems.length === 0) {
      document.getElementById('search-results').innerHTML = `
        <div class="py-8 text-center">
          <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
            <i data-feather="search" class="h-8 w-8 text-gray-400"></i>
          </div>
          <p class="text-gray-700 font-medium">No results found</p>
          <p class="text-gray-500 text-sm">Try a different search term</p>
        </div>
      `;
      
      // Initialize Feather icons for the no results message
      if (window.feather) {
        feather.replace(document.querySelectorAll('#search-results [data-feather]'));
      }
      
      return;
    }
    
    // Format price helper
    const formatPrice = (price) => {
      return 'QAR ' + price.toFixed(0);
    };
    
    // Generate results HTML
    let resultsHTML = `<h3 class="text-sm font-medium text-gray-700 mb-2">Search Results (${filteredItems.length})</h3>`;
    resultsHTML += `<div class="grid grid-cols-1 gap-3 pb-4">`;
    
    filteredItems.forEach(item => {
      // Determine price display
      let priceDisplay;
      if (item.isHalfFull) {
        priceDisplay = `${formatPrice(item.halfPrice)} - ${formatPrice(item.fullPrice)}`;
      } else {
        priceDisplay = formatPrice(item.price);
      }
      
      resultsHTML += `
        <div class="menu-item bg-white rounded-xl shadow-sm overflow-hidden flex" onclick="window.location.href='menu-item-details.html?id=${item.id}'">
          <div class="w-24 h-24 flex-shrink-0">
            <img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover">
          </div>
          <div class="p-3 flex-1 flex flex-col justify-between">
            <div>
              <h3 class="font-medium text-sm mb-0.5">${item.name}</h3>
              <p class="text-gray-500 text-xs line-clamp-1">${item.description}</p>
            </div>
            <div class="flex justify-between items-center mt-1">
              <span class="text-primary font-semibold">${priceDisplay}</span>
              <button class="add-to-cart-btn w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center" data-id="${item.id}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              </button>
            </div>
          </div>
        </div>
      `;
    });
    
    resultsHTML += `</div>`;
    
    // Update the results container
    document.getElementById('search-results').innerHTML = resultsHTML;
    
    // Add event listeners to "Add to cart" buttons
    setupAddToCartButtons();
    
    // Save search term to recent searches (would typically save to localStorage)
    saveRecentSearch(query);
    
  } catch (error) {
    console.error('Search error:', error);
    document.getElementById('search-results').innerHTML = `
      <div class="py-8 text-center">
        <p class="text-gray-500">Something went wrong. Please try again.</p>
      </div>
    `;
  }
}

// Set up "Add to cart" buttons in search results
function setupAddToCartButtons() {
  const addToCartButtons = document.querySelectorAll('#search-results .add-to-cart-btn');
  
  addToCartButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      // Prevent bubbling to parent (which would navigate to details)
      e.stopPropagation();
      
      const itemId = this.getAttribute('data-id');
      
      // Add to cart using window.cartManager if available
      if (window.menuManager && window.cartManager) {
        const item = window.menuManager.getItemById(itemId);
        
        if (item) {
          // For half/full items, default to half
          let variants = {};
          if (item.isHalfFull) {
            variants.size = 'half';
          }
          
          // Add to cart
          window.cartManager.addToCart(item, 1, variants);
          
          // Show toast notification
          if (window.toast) {
            window.toast.show('Added to cart', 'success');
          }
          
          // Update cart badge in all places
          if (typeof window.cartManager.updateCartBadge === 'function') {
            window.cartManager.updateCartBadge();
          }
        }
      } else {
        // Fallback if cartManager not available
        alert('Item added to cart');
      }
    });
  });
}

// Save search term to recent searches
function saveRecentSearch(term) {
  // This would typically save to localStorage
  // For demo purposes, we'll just log it
  console.log('Saved recent search:', term);
}

// Toggle search modal visibility
function toggleSearchModal() {
  const searchModal = document.getElementById('search-modal');
  if (!searchModal) {
    createSearchModal();
  }
  
  const modal = document.getElementById('search-modal');
  const isHidden = modal.classList.contains('hidden');
  
  if (isHidden) {
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.getElementById('global-search-input').focus();
  } else {
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
  }
}

// Initialize search functionality
function initSearch() {
  createSearchModal();
  
  // Find the search button in the bottom nav and add event listener
  const searchButtons = document.querySelectorAll('.search-button, [data-search-button]');
  searchButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      toggleSearchModal();
    });
  });
  
  // Also initialize for the main search button if present
  const mainSearchButton = document.querySelector('.main-search-button');
  if (mainSearchButton) {
    mainSearchButton.addEventListener('click', toggleSearchModal);
  }
}

// Export functions
export { initSearch, toggleSearchModal };

// Auto-initialize if the script is loaded directly
document.addEventListener('DOMContentLoaded', function() {
  // Give time for other scripts to load
  setTimeout(initSearch, 100);
});