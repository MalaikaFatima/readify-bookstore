document.addEventListener('DOMContentLoaded', () => {
    
    const banners = ['assets/img/banner1.png', 'assets/img/banner2.png', 'assets/img/banner3.png'];
    let currentBanner = 0;
    const bannerImg = document.getElementById('banner-img');

    if (bannerImg) {
        window.changeBanner = (direction) => {
            currentBanner = (currentBanner + direction + banners.length) % banners.length;
            bannerImg.src = banners[currentBanner];
        };
    }


    const searchForm = document.querySelector('.search-bar form');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            const searchInput = searchForm.querySelector('input[name="search"]');
            if (!searchInput.value.trim()) {
                e.preventDefault();
                alert('Please enter a search term.');
                searchInput.focus();
            }
        });
    }
});
