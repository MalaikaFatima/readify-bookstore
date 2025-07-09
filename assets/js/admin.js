// assets/js/admin.js
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    document.getElementById(tabName).classList.add('active');
    document.querySelector(`button[onclick="showTab('${tabName}')"]`).classList.add('active');
    localStorage.setItem('activeTab', tabName);
}

function toggleOrderDetails(orderId) {
    const details = document.getElementById('order-details-' + orderId);
    details.style.display = details.style.display === 'none' ? 'table-row' : 'none';
    return false;
}

document.addEventListener('DOMContentLoaded', function() {
    const activeTab = localStorage.getItem('activeTab') || 'categories';
    showTab(activeTab);
    
    document.getElementById('add-category-form').addEventListener('submit', function(e) {
        const name = document.querySelector('input[name="category_name"]').value.trim();
        const file = document.querySelector('input[name="category_image"]').files[0];
        if (!name) {
            alert('Category name is required.');
            e.preventDefault();
        }
        if (!file || !['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
            alert('Please upload a valid image (JPG, PNG, or GIF).');
            e.preventDefault();
        }
    });
    
    document.getElementById('add-product-form').addEventListener('submit', function(e) {
        const title = document.querySelector('input[name="title"]').value.trim();
        const description = document.querySelector('textarea[name="description"]').value.trim();
        const price = parseFloat(document.querySelector('input[name="price"]').value);
        const category = document.querySelector('select[name="category_id"]').value;
        const file = document.querySelector('input[name="product_image"]').files[0];
        
        if (!title || !description || !category) {
            alert('All fields are required.');
            e.preventDefault();
        }
        if (isNaN(price) || price < 0) {
            alert('Please enter a valid price.');
            e.preventDefault();
        }
        if (!file || !['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
            alert('Please upload a valid image (JPG, PNG, or GIF).');
            e.preventDefault();
        }
    });
});