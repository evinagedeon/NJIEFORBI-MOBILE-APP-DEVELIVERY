// Main JavaScript file for LUCOCHER

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Load social wall posts
    loadSocialPosts();

    // Auto-refresh social wall every 30 seconds
    setInterval(loadSocialPosts, 30000);

    // Initialize cart functionality
    initializeCart();

    // Initialize search functionality
    initializeSearch();
});

// Load social wall posts
function loadSocialPosts() {
    fetch('api/social-posts.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySocialPosts(data.posts);
            }
        })
        .catch(error => {
            console.error('Error loading social posts:', error);
        });
}

// Display social posts
function displaySocialPosts(posts) {
    const container = document.getElementById('social-posts');
    if (!container) return;

    container.innerHTML = '';

    posts.forEach(post => {
        const postElement = createPostElement(post);
        container.appendChild(postElement);
    });
}

// Create post element
function createPostElement(post) {
    const div = document.createElement('div');
    div.className = 'col-md-6 col-lg-4 mb-4';
    
    const mediaContent = post.image_path ? 
        `<img src="${post.image_path}" class="card-img-top" alt="${post.title}" style="height: 200px; object-fit: cover;">` :
        (post.video_path ? 
            `<video class="card-img-top" style="height: 200px; object-fit: cover;" controls>
                <source src="${post.video_path}" type="video/mp4">
            </video>` : '');

    div.innerHTML = `
        <div class="card social-post h-100">
            ${mediaContent}
            <div class="social-post-header">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="mb-0">${post.title}</h6>
                        <small class="text-muted">${post.company_name}</small>
                    </div>
                    ${post.is_sponsored ? '<span class="badge bg-warning text-dark">Sponsorisé</span>' : ''}
                </div>
            </div>
            <div class="social-post-content">
                <p class="card-text">${truncateText(post.content, 100)}</p>
                ${post.product_id ? `<a href="product.php?id=${post.product_id}" class="btn btn-sm btn-primary">Voir le produit</a>` : ''}
            </div>
            <div class="social-post-footer d-flex justify-content-between align-items-center">
                <div class="social-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick="likePost(${post.id})">
                        <i class="fas fa-heart"></i> ${post.likes_count}
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="sharePost(${post.id})">
                        <i class="fas fa-share"></i> ${post.shares_count}
                    </button>
                </div>
                <small class="text-muted">${timeAgo(post.published_at)}</small>
            </div>
        </div>
    `;
    
    return div;
}

// Like post
function likePost(postId) {
    fetch('api/like-post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ post_id: postId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update like count in UI
            const likeButton = document.querySelector(`button[onclick="likePost(${postId})"]`);
            if (likeButton) {
                likeButton.innerHTML = `<i class="fas fa-heart"></i> ${data.likes_count}`;
            }
        }
    })
    .catch(error => {
        console.error('Error liking post:', error);
    });
}

// Share post
function sharePost(postId) {
    if (navigator.share) {
        navigator.share({
            title: 'LUCOCHER - Découvrez cette offre',
            url: `${window.location.origin}/post.php?id=${postId}`
        });
    } else {
        // Fallback: copy to clipboard
        const url = `${window.location.origin}/post.php?id=${postId}`;
        navigator.clipboard.writeText(url).then(() => {
            showToast('Lien copié dans le presse-papiers!', 'success');
        });
    }
}

// Initialize cart functionality
function initializeCart() {
    // Add to cart buttons
    document.addEventListener('click', function(e) {
        if (e.target.matches('.add-to-cart') || e.target.closest('.add-to-cart')) {
            e.preventDefault();
            const button = e.target.matches('.add-to-cart') ? e.target : e.target.closest('.add-to-cart');
            const productId = button.getAttribute('data-product-id');
            const quantity = button.getAttribute('data-quantity') || 1;
            
            addToCart(productId, quantity);
        }
    });

    // Update cart count on page load
    updateCartCount();
}

// Add to cart
function addToCart(productId, quantity = 1) {
    fetch('api/add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            product_id: productId, 
            quantity: parseInt(quantity) 
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Produit ajouté au panier!', 'success');
            updateCartCount();
        } else {
            showToast(data.message || 'Erreur lors de l\'ajout au panier', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        showToast('Erreur lors de l\'ajout au panier', 'error');
    });
}

// Update cart count
function updateCartCount() {
    fetch('api/cart-count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartBadges = document.querySelectorAll('.cart-count');
                cartBadges.forEach(badge => {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'inline' : 'none';
                });
            }
        })
        .catch(error => {
            console.error('Error updating cart count:', error);
        });
}

// Initialize search functionality
function initializeSearch() {
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            } else {
                if (searchResults) {
                    searchResults.innerHTML = '';
                    searchResults.style.display = 'none';
                }
            }
        });
        
        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && searchResults && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }
}

// Perform search
function performSearch(query) {
    fetch(`api/search.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySearchResults(data.results);
            }
        })
        .catch(error => {
            console.error('Error performing search:', error);
        });
}

// Display search results
function displaySearchResults(results) {
    const searchResults = document.getElementById('search-results');
    if (!searchResults) return;
    
    if (results.length === 0) {
        searchResults.innerHTML = '<div class="p-3 text-muted">Aucun résultat trouvé</div>';
    } else {
        let html = '';
        results.forEach(result => {
            html += `
                <div class="search-result-item p-3 border-bottom">
                    <div class="d-flex align-items-center">
                        ${result.image_path ? `<img src="${result.image_path}" class="me-3" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">` : ''}
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><a href="product.php?id=${result.id}" class="text-decoration-none">${result.name}</a></h6>
                            <p class="mb-1 text-muted small">${truncateText(result.description, 60)}</p>
                            <span class="text-primary fw-bold">${formatPrice(result.price)}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        searchResults.innerHTML = html;
    }
    
    searchResults.style.display = 'block';
}

// Show toast notification
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'primary'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

// Create toast container
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1055';
    document.body.appendChild(container);
    return container;
}

// Utility functions
function truncateText(text, length) {
    if (text.length <= length) return text;
    return text.substring(0, length) + '...';
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    const intervals = {
        année: 31536000,
        mois: 2592000,
        jour: 86400,
        heure: 3600,
        minute: 60
    };
    
    for (const [unit, secondsInUnit] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / secondsInUnit);
        if (interval >= 1) {
            return `Il y a ${interval} ${unit}${interval > 1 && unit !== 'mois' ? 's' : ''}`;
        }
    }
    
    return 'À l\'instant';
}

function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
}

// Flash sales countdown
function initializeCountdown(endDate, elementId) {
    const countdownElement = document.getElementById(elementId);
    if (!countdownElement) return;
    
    const endTime = new Date(endDate).getTime();
    
    const timer = setInterval(function() {
        const now = new Date().getTime();
        const distance = endTime - now;
        
        if (distance < 0) {
            clearInterval(timer);
            countdownElement.innerHTML = '<span class="text-danger">Terminée</span>';
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        countdownElement.innerHTML = `
            <div class="timer-unit">${days}j</div>
            <div class="timer-unit">${hours}h</div>
            <div class="timer-unit">${minutes}m</div>
            <div class="timer-unit">${seconds}s</div>
        `;
    }, 1000);
}

// Lazy loading for images
function initializeLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeLazyLoading();
});

// Smooth scrolling for anchor links
document.addEventListener('click', function(e) {
    if (e.target.matches('a[href^="#"]')) {
        e.preventDefault();
        const targetId = e.target.getAttribute('href');
        const targetElement = document.querySelector(targetId);
        
        if (targetElement) {
            targetElement.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }
});

// Form validation enhancement
function enhanceFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

// Initialize form validation
document.addEventListener('DOMContentLoaded', enhanceFormValidation);