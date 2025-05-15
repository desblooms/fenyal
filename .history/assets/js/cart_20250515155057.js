/**
 * Cart Management JavaScript
 * Handles cart operations: add, remove, update quantity
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart functionality
    initCartFunctions();
});

function initCartFunctions() {
    // Add to cart buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.getAttribute('data-item-id');
            const itemName = this.getAttribute('data-item-name');
            const itemPrice = parseFloat(this.getAttribute('data-item-price'));
            
            addToCart(itemId, itemName, itemPrice, 1);
        });
    });
    
    // Quantity buttons in cart page
    setupQuantityButtons();
    
    // Update quantity inputs in cart page
    setupQuantityInputs();
    
    // Remove from cart buttons
    setupRemoveButtons();
}

function addToCart(itemId, itemName, itemPrice, quantity, addons = []) {
    // Create item data
    const item = {
        id: itemId,
        name: itemName,
        price: itemPrice,
        quantity: quantity,
        addons: addons
    };
    
    // Send AJAX request to add item to cart
    fetch('includes/cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            item: item
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showToast('Item added to cart');
            
            // Update cart count in navbar
            updateCartCount(data.cart_count);
            
            // If we're on the item detail page, redirect to cart or stay
            if (document.querySelector('.item-detail-page')) {
                const viewCartBtn = document.querySelector('.view-cart-btn');
                viewCartBtn.classList.remove('hidden');
            }
        } else {
            showToast('Error adding item to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error adding item to cart');
    });
}

function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        element.textContent = count;
        
        if (count > 0) {
            element.classList.remove('hidden');
        } else {
            element.classList.add('hidden');
        }
    });
}

function setupQuantityButtons() {
    // Plus buttons
    const plusButtons = document.querySelectorAll('.quantity-plus');
    plusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentNode.querySelector('input');
            const currentValue = parseInt(input.value);
            input.value = currentValue + 1;
            
            // Trigger change event to update cart
            const event = new Event('change', { bubbles: true });
            input.dispatchEvent(event);
        });
    });
    
    // Minus buttons
    const minusButtons = document.querySelectorAll('.quantity-minus');
    minusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentNode.querySelector('input');
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
                
                // Trigger change event to update cart
                const event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);
            }
        });
    });
}

function setupQuantityInputs() {
    const quantityInputs = document.querySelectorAll('.cart-quantity-input');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const itemIndex = this.getAttribute('data-item-index');
            const quantity = parseInt(this.value);
            
            if (quantity <= 0) {
                // If quantity is 0 or negative, ask for confirmation to remove
                if (confirm('Remove this item from cart?')) {
                    updateCartItem(itemIndex, 0);
                } else {
                    // Reset to 1 if user cancels
                    this.value = 1;
                }
            } else {
                updateCartItem(itemIndex, quantity);
            }
        });
    });
}

function setupRemoveButtons() {
    const removeButtons = document.querySelectorAll('.remove-from-cart');
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemIndex = this.getAttribute('data-item-index');
            if (confirm('Remove this item from cart?')) {
                updateCartItem(itemIndex, 0);
            }
        });
    });
}

function updateCartItem(itemIndex, quantity) {
    // Send AJAX request to update cart
    fetch('includes/cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update',
            index: itemIndex,
            quantity: quantity
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to reflect changes
            location.reload();
        } else {
            showToast('Error updating cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating cart');
    });
}

function showToast(message) {
    // Create toast element if it doesn't exist
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.className = 'fixed bottom-20 left-1/2 transform -translate-x-1/2 bg-[#3a001e] text-white py-2 px-4 rounded-full shadow-lg z-50 opacity-0 transition-opacity duration-300';
        document.body.appendChild(toast);
    }
    
    // Set message and show toast
    toast.textContent = message;
    toast.classList.remove('opacity-0');
    
    // Hide after 3 seconds
    setTimeout(() => {
        toast.classList.add('opacity-0');
    }, 3000);
}