/**
 * Fenyal - Global Search Module
 * 
 * This script adds universal search functionality to all pages via the search button
 * in the bottom navigation bar. It handles creating a search modal, performing searches,
 * displaying results, and managing recent searches.
 *
 * How to use:
 * 1. Include this script at the end of each page before closing body tag:
 *    <script src="assets/js/global-search.js"></script>
 *
 * 2. The script will automatically:
 *    - Find all search buttons in bottom navigation
 *    - Add event listeners to open the search modal
 *    - Create the search modal when needed
 *    - Handle search functionality
 */

// Global state
let menuData = null;
let recentSearches = [];

// Main initialization function
function initializeGlobalSearch() {
  // Load recent searches from localStorage
  loadRecentSearches();
  
  // Fix all search buttons in the app
  fixSearchButtons();
  
  // Preload menu data for faster searching
  preloadMenuData();
}

// Load recent searches from localStorage
function loadRecentSearches() {
  try {
    const stored = localStorage.getItem('recentSearches');
    if (stored) {
      recentSearches = JSON.parse(stored);
    }
  } catch (e) {
    console.error('Error loading recent searches:', e);
    recentSearches = [];
  }
}

// Find and fix all search buttons
function fixSearchButtons() {
  // Find the search button in the bottom navigation
  const searchButtons = document.querySelectorAll([
    // The central search button in bottom nav
    'nav .w-12.h-12.rounded-full.bg-primary',
    // Parent links containing the search button
    'nav a:has(.w-12.h-12.rounded-full.bg-primary)',
    // Container divs that might be used instead of direct buttons
    'nav .search-trigger',
    // Any element with search-related data attributes
    '[data-search="true"]',
    // Explicitly labeled search buttons
    '.search-button'
  ].join(','));
  
  searchButtons.forEach(button => {
    // If this is inside a link, disable the link navigation
    const parentLink = button.closest('a');
    if (parentLink && parentLink.hasAttribute('href')) {
      parentLink.removeAttribute('href');
      parentLink.style.cursor = 'pointer';
    }
    
    // Clone and replace to remove existing event listeners
    const newElement = button.cloneNode(true);
    if (button.parentNode) {
      button.parentNode.replaceChild(newElement, button);
    }
    
    // Determine the click target (button or its parent link)
    const clickTarget = parentLink || newElement;
    
    // Add the click event to open search
    clickTarget.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      openSearchModal();
    });
  });
}

// Create the search modal
function createSearchModal() {
  // Check if modal already exists
  if (document.getElementById('search-modal')) return;
  
  // Create modal HTML
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
            <!-- Will be populated dynamically -->
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
          <!-- Will be populated dynamically -->
        </div>
      </div>
    </div>
  `;
  
  // Create and append to body
  const modalContainer = document.createElement('div');
  modalContainer.innerHTML = modalHTML;
  document.body.appendChild(modalContainer.firstElementChild);
  
  // Initialize icons
  if (window.feather) {
    feather.replace(document.querySelectorAll('#search-modal [data-feather]'));
  }
  
  // Update recent searches UI
  updateRecentSearchesUI();
  
  // Set up events
  setupSearchModalEvents();
}

// Set up search modal event listeners
function setupSearchModalEvents() {
  // Close button
  document.getElementById('close-search-modal').addEventListener('click', closeSearchModal);
  
  // Close on background click
  document.getElementById('search-modal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeSearchModal();
    }
  });
  
  // Search input with debounce
  const searchInput = document.getElementById('global-search-input');
  let debounceTimeout;
  
  searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
      performSearch(this.value);
    }, 300); // 300ms debounce
  });
  
  // Handle escape key to close modal
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const modal = document.getElementById('search-modal');
      if (modal && !modal.classList.contains('hidden')) {
        closeSearchModal();
      }
    }
  });
}

// Open the search modal
function openSearchModal() {
  // Create if not exists
  createSearchModal();
  
  // Show modal
  document.getElementById('search-modal').classList.remove('hidden');
  document.body.style.overflow = 'hidden'; // Prevent scrolling
  
  // Focus search input
  setTimeout(() => {
    document.getElementById('global-search-input').focus();
  }, 100);
}

// Close the search modal
function closeSearchModal() {
  const modal = document.getElementById('search-modal');
  if (modal) {
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto'; // Re-enable scrolling
  }
}

// Update recent searches UI
function updateRecentSearchesUI() {
  const container = document.getElementById('recent-searches');
  if (!container) return;
  
  // Clear existing content
  container.innerHTML = '';
  
  if (recentSearches.length === 0) {
    container.innerHTML = '<span class="text-sm text-gray-500">No recent searches</span>';
    return;
  }
  
  // Add chips for each recent search
  recentSearches.forEach(term => {
    const chip = document.createElement('span');
    chip.className = 'px-3 py-1.5 bg-gray-100 rounded-full text-sm cursor-pointer transition hover:bg-gray-200';
    chip.textContent = term;
    
    chip.addEventListener('click', function() {
      document.getElementById('global-search-input').value = term;
      performSearch(term);
    });
    
    container.appendChild(chip);
  });
}

// Save a search term to recent searches
function saveRecentSearch(term) {
  if (!term || term.trim() === '') return;
  
  // Clean the term
  const cleanTerm = term.trim();
  
  // Check if it already exists
  const existingIndex = recentSearches.indexOf(cleanTerm);
  if (existingIndex !== -1) {
    // Move to the front if it exists
    recentSearches.splice(existingIndex, 1);
  }
  
  // Add to the front
  recentSearches.unshift(cleanTerm);
  
  // Limit to 5 recent searches
  if (recentSearches.length > 5) {
    recentSearches = recentSearches.slice(0, 5);
  }
  
  // Save to localStorage
  try {
    localStorage.setItem('recentSearches', JSON.stringify(recentSearches));
  } catch (e) {
    console.error('Error saving recent searches:', e);
  }
  
  // Update the UI
  updateRecentSearchesUI();
}

// Preload menu data for faster searching
async function preloadMenuData() {
  if (menuData) return; // Already loaded
  
  try {
    // Try to use menuManager if available
    if (window.menuManager && typeof window.menuManager.loadMenuData === 'function') {
      await window.menuManager.loadMenuData();
      menuData = window.menuManager.menuData;
      return;
    }
    
    // Fallback to direct fetch
    const response = await fetch('data/menu.json');
    if (!response.ok) throw new Error('Failed to load menu data');
    menuData = await response.json();
  } catch (error) {
    console.error('Error preloading menu data:', error);
  }
}

// Get menu data (with loading if needed)
async function getMenuData() {
  if (menuData) return menuData;
  
  try {
    // Try to use menuManager if available
    if (window.menuManager && typeof window.menuManager.loadMenuData === 'function') {
      await window.menuManager.loadMenuData();
      menuData = window.menuManager.menuData;
      return menuData;
    }
    
    // Fallback to direct fetch
    const response = await fetch('data/menu.json');
    if (!response.ok) throw new Error('Failed to load menu data');
    menuData = await response.json();
    return menuData;
  } catch (error) {
    console.error('Error loading menu data:', error);
    throw error;
  }
}

// Format price helper function
function formatPrice(price) {
  // Try to use window.formatPrice if available
  if (window.formatPrice && typeof window.formatPrice === 'function') {
    return window.formatPrice(price);
  }
  
  // Fallback implementation
  return 'QAR ' + price.toFixed(0);
}

// Perform search with results
async function performSearch(query) {
  if (!query || query.trim() === '') {
    document.getElementById('search-results').innerHTML = '';
    return;
  }
  
  const searchResults = document.getElementById('search-results');
  
  // Show loading state
  searchResults.innerHTML = `
    <div class="animate-pulse py-4">
      <div class="h-24 rounded-xl bg-white shadow-sm overflow-hidden mb-3">
        <div class="flex">
          <div class="w-24 h-24 bg-gray-200"></div>
          <div class="flex-1 p-3">
            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
            <div class="h-3 bg-gray-200 rounded w-1/2 mb-3"></div>
            <div class="h-4 bg-gray-200 rounded w-1/4"></div>
          </div>
        </div>
      </div>
      <div class="h-24 rounded-xl bg-white shadow-sm overflow-hidden">
        <div class="flex">
          <div class="w-24 h-24 bg-gray-200"></div>
          <div class="flex-1 p-3">
            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
            <div class="h-3 bg-gray-200 rounded w-1/2 mb-3"></div>
            <div class="h-4 bg-gray-200 rounded w-1/4"></div>
          </div>
        </div>
      </div>
    </div>
  `;
  
  try {
    // Get menu data
    const items = await getMenuData();
    
    // Filter items based on search query
    const searchTerm = query.toLowerCase().trim();
    const filteredItems = items.filter(item => {
      return (
        item.name.toLowerCase().includes(searchTerm) || 
        item.description.toLowerCase().includes(searchTerm) || 
        item.category.toLowerCase().includes(searchTerm)
      );
    });
    
    // Save the search term
    saveRecentSearch(searchTerm);
    
    // Generate results HTML
    if (filteredItems.length === 0) {
      searchResults.innerHTML = `
        <div class="py-8 text-center">
          <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
            <i data-feather="search" class="h-8 w-8 text-gray-400"></i>
          </div>
          <p class="text-gray-700 font-medium">No results found</p>
          <p class="text-gray-500 text-sm">Try a different search term</p>
        </div>
      `;
      
      // Initialize icons
      if (window.feather) {
        feather.replace(document.querySelectorAll('#search-results [data-feather]'));
      }
      
      return;
    }
    
    // Build results HTML
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
              <span class="text-xs text-gray-500">${item.category}</span>
            </div>
          </div>
        </div>
      `;
    });
    
    resultsHTML += `</div>`;
    
    // Update the results container
    searchResults.innerHTML = resultsHTML;
    
  } catch (error) {
    console.error('Search error:', error);
    searchResults.innerHTML = `
      <div class="py-8 text-center">
        <p class="text-gray-500">Something went wrong. Please try again.</p>
      </div>
    `;
  }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  // Initialize with a slight delay to ensure other scripts are loaded
  setTimeout(initializeGlobalSearch, 100);
});

// Export functions for use in other modules (if needed)
if (typeof window !== 'undefined') {
  window.globalSearch = {
    open: openSearchModal,
    close: closeSearchModal,
    search: performSearch
  };
}