// Menu loader script
document.addEventListener('DOMContentLoaded', () => {
    // Fetch menu data from JSON file
    fetch('menu.json')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Load restaurant data
            loadRestaurantInfo(data.restaurant);
            
            // Load menu categories
            loadMenuCategories(data.categories);
            
            // Load menu items
            loadMenuItems(data.menuItems);
            
            // Load reviews
            loadReviews(data.reviews);
        })
        .catch(error => {
            console.error('Error loading menu data:', error);
        });
});

// Function to load restaurant information
function loadRestaurantInfo(restaurantData) {
    // Update restaurant name
    document.querySelectorAll('.restaurant-header h2').forEach(element => {
        element.textContent = restaurantData.name;
    });
    
    // Update location
    const locationElements = document.querySelectorAll('.restaurant-header p:nth-child(2)');
    locationElements.forEach(element => {
        const icon = element.querySelector('span').innerHTML;
        element.innerHTML = `<span>${icon}</span> ${restaurantData.location}`;
    });
    
    // Update hours
    const hoursElements = document.querySelectorAll('.restaurant-header p:nth-child(3)');
    hoursElements.forEach(element => {
        const icon = element.querySelector('span').innerHTML;
        element.innerHTML = `<span>${icon}</span> ${restaurantData.hours}`;
    });
    
    // Update rating
    document.querySelectorAll('.rating span').forEach(element => {
        element.textContent = restaurantData.rating;
    });
    
    // Update distance
    document.querySelectorAll('.distance').forEach(element => {
        element.textContent = restaurantData.distance;
    });
    
    // Update cuisine
    const cuisineElements = document.querySelectorAll('.restaurant-details ul li:nth-child(1) p');
    cuisineElements.forEach(element => {
        element.textContent = restaurantData.cuisine;
    });
    
    // Update average cost
    const costElements = document.querySelectorAll('.restaurant-details ul li:nth-child(2) p');
    costElements.forEach(element => {
        element.textContent = restaurantData.averageCost;
    });
    
    // Update facilities
    const facilitiesElements = document.querySelectorAll('.restaurant-details ul li:nth-child(3) p');
    facilitiesElements.forEach(element => {
        element.textContent = restaurantData.facilities;
    });
    
    // Update services
    const servicesElements = document.querySelectorAll('.restaurant-details ul li:nth-child(4) p');
    servicesElements.forEach(element => {
        element.textContent = restaurantData.services;
    });
}

// Function to load menu categories
function loadMenuCategories(categories) {
    // Create HTML for category links
    const categoryLinksHTML = categories.map((category, index) => {
        return `<li><a href="#${category.id}" ${index === 0 ? 'class="active-category"' : ''}>${category.name}</a></li>`;
    }).join('');
    
    // Update category links in all menu areas
    document.querySelectorAll('.menu-categories ul').forEach(element => {
        element.innerHTML = categoryLinksHTML;
    });
    
    // Update filter menu
    const filterMenuHTML = categories.map((category, index) => {
        return `<li><a href="#${category.id}">${category.name}</a></li>`;
    }).join('');
    
    document.querySelector('.restaurant-menu-filter ul').innerHTML = filterMenuHTML;
}

// Function to load menu items
function loadMenuItems(menuItemsData) {
    const menuFoodsContainer = document.querySelector('.menu-foods');
    let menuSectionsHTML = '';
    
    // Loop through each category and create section
    Object.keys(menuItemsData).forEach(categoryId => {
        const categoryItems = menuItemsData[categoryId];
        
        // If there are items in this category
        if (categoryItems && categoryItems.length > 0) {
            // Find the category name from the DOM (a more robust solution would be to pass categories as a parameter)
            const categoryLink = document.querySelector(`.menu-categories a[href="#${categoryId}"]`);
            const categoryName = categoryLink ? categoryLink.textContent : categoryId;
            
            // Start section HTML
            menuSectionsHTML += `
            <section id="${categoryId}">
                <h2>${categoryName}</h2>
                <ul>`;
            
            // Add items for this category
            categoryItems.forEach(item => {
                menuSectionsHTML += `
                <li>
                    <a href="">
                        <div class="food-image">
                            <img src="${item.image}" alt="${item.name}">
                        </div>
                        <div class="food-details">
                            <h3>${item.name}</h3>
                            <p>${item.description}</p>
                            <h4>QAR ${item.price.toFixed(2)}</h4>
                        </div>
                    </a>
                </li>`;
            });
            
            // End section HTML
            menuSectionsHTML += `
                </ul>
            </section>`;
        }
    });
    
    // Update menu container
    menuFoodsContainer.innerHTML = menuSectionsHTML;
}

// Function to load reviews
function loadReviews(reviewsData) {
    const reviewListContainer = document.querySelector('.review-list ul');
    let reviewsHTML = '';
    
    // Create HTML for each review
    reviewsData.forEach(review => {
        reviewsHTML += `
        <li>
            <div class="user-and-rating">
                <h3>${review.name}</h3>
                <p class="rating"><i class="fa fa-star"></i> <span>${review.rating}</span></p>
            </div>
            <div class="comments">
                <p>${review.comment}</p>
            </div>
        </li>`;
    });
    
    // Update reviews container
    reviewListContainer.innerHTML = reviewsHTML;
}