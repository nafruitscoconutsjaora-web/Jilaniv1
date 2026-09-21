/**
 * Rose SMM Panel - Vanilla JavaScript (User Panel, Admin & Public Landing)
 * No frameworks - pure vanilla JS
 */

document.addEventListener('DOMContentLoaded', function () {
  // --------------------------------------------------------------------------
  // 1. Landing Page Navigation Toggle (Mobile & Tablet)
  // --------------------------------------------------------------------------
  const landingNavToggle = document.getElementById('landing_nav_toggle');
  const landingNavMenu = document.getElementById('public_nav_menu');

  if (landingNavToggle && landingNavMenu) {
    // Create backdrop overlay element for landing navigation
    let landingBackdrop = document.querySelector('.landing-nav-backdrop');
    if (!landingBackdrop) {
      landingBackdrop = document.createElement('div');
      landingBackdrop.className = 'landing-nav-backdrop';
      document.body.appendChild(landingBackdrop);
    }

    function openLandingMenu() {
      landingNavToggle.classList.add('open');
      landingNavMenu.classList.add('open');
      landingNavToggle.setAttribute('aria-expanded', 'true');
      landingBackdrop.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeLandingMenu() {
      landingNavToggle.classList.remove('open');
      landingNavMenu.classList.remove('open');
      landingNavToggle.setAttribute('aria-expanded', 'false');
      landingBackdrop.classList.remove('active');
      document.body.style.overflow = '';
    }

    landingNavToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      if (landingNavMenu.classList.contains('open')) {
        closeLandingMenu();
      } else {
        openLandingMenu();
      }
    });

    landingBackdrop.addEventListener('click', closeLandingMenu);

    // Close when clicking any link inside the mobile drawer
    const menuLinks = landingNavMenu.querySelectorAll('a');
    menuLinks.forEach(link => {
      link.addEventListener('click', closeLandingMenu);
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && landingNavMenu.classList.contains('open')) {
        closeLandingMenu();
      }
    });

    // Close automatically if screen is resized beyond mobile breakpoint (> 860px)
    window.addEventListener('resize', function () {
      if (window.innerWidth > 860 && landingNavMenu.classList.contains('open')) {
        closeLandingMenu();
      }
    });
  }

  // --------------------------------------------------------------------------
  // 2. User & Admin Panel Mobile Sidebar Toggle
  // --------------------------------------------------------------------------
  const mobileToggle = document.querySelector('.mobile-toggle');
  const sidebar = document.querySelector('.panel-sidebar');

  if (mobileToggle && sidebar) {
    let sidebarBackdrop = document.querySelector('.sidebar-backdrop');
    if (!sidebarBackdrop) {
      sidebarBackdrop = document.createElement('div');
      sidebarBackdrop.className = 'sidebar-backdrop';
      document.body.appendChild(sidebarBackdrop);
    }

    function openSidebar() {
      sidebar.classList.add('open');
      sidebarBackdrop.classList.add('open');
      mobileToggle.setAttribute('aria-expanded', 'true');
    }

    function closeSidebar() {
      sidebar.classList.remove('open');
      sidebarBackdrop.classList.remove('open');
      mobileToggle.setAttribute('aria-expanded', 'false');
    }

    mobileToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      if (sidebar.classList.contains('open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });

    sidebarBackdrop.addEventListener('click', closeSidebar);

    // Close when clicking outside
    document.addEventListener('click', function (e) {
      if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target) && sidebar.classList.contains('open')) {
        closeSidebar();
      }
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && sidebar.classList.contains('open')) {
        closeSidebar();
      }
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 900 && sidebar.classList.contains('open')) {
        closeSidebar();
      }
    });
  }

  // 2. Dynamic Order Calculation (New Order Page)
  const categorySelect = document.getElementById('order_category');
  const serviceSelect = document.getElementById('order_service');
  const quantityInput = document.getElementById('order_quantity');
  const totalPriceDisplay = document.getElementById('order_total_price');
  const serviceDescDisplay = document.getElementById('service_description');
  const minMaxDisplay = document.getElementById('service_min_max');

  if (categorySelect && serviceSelect && quantityInput) {
    const servicesData = window.smmServices || [];
    const userCurrencySymbol = window.userCurrencySymbol || '$';
    const userCurrencyRate = parseFloat(window.userCurrencyRate || 1.0);

    // Category and Service icon box elements
    const catIconBox = document.getElementById('category_select_icon_box');
    const srvIconBox = document.getElementById('service_select_icon_box');
    const categorySvgs = window.smmCategorySvgs || {};

    function getCategorySlug(name) {
      const n = (name || '').toLowerCase().trim();
      if (n.includes('insta')) return 'instagram';
      if (n.includes('face') || n.includes('fb')) return 'facebook';
      if (n.includes('you') || n.includes('tube') || n.includes('yt')) return 'youtube';
      if (n.includes('tik') || n.includes('tok')) return 'tiktok';
      if (n.includes('tele') || n.includes('tg')) return 'telegram';
      if (n.includes('twit') || n.includes(' x ') || n.includes('x.com') || n.endsWith(' x')) return 'twitter';
      if (n.includes('spot')) return 'spotify';
      if (n.includes('linke')) return 'linkedin';
      if (n.includes('disc')) return 'discord';
      if (n.includes('twitc')) return 'twitch';
      if (n.includes('whats')) return 'whatsapp';
      if (n.includes('thread')) return 'threads';
      if (n.includes('pinter')) return 'pinterest';
      if (n.includes('reddi')) return 'reddit';
      if (n.includes('soundc')) return 'soundcloud';
      if (n.includes('web') || n.includes('traffic') || n.includes('visit') || n.includes('seo')) return 'traffic';
      return 'general';
    }

    function updateCategoryIcons() {
      const selectedCatOption = categorySelect.options[categorySelect.selectedIndex];
      if (!selectedCatOption) return;
      const catName = selectedCatOption.dataset.name || selectedCatOption.textContent.trim();
      const slug = selectedCatOption.dataset.slug || getCategorySlug(catName);
      const svg = categorySvgs[slug] || categorySvgs['general'] || '';
      if (catIconBox && svg) {
        catIconBox.innerHTML = svg;
      }
      if (srvIconBox && svg) {
        srvIconBox.innerHTML = svg;
      }
    }

    function populateServices(categoryId) {
      updateCategoryIcons();
      serviceSelect.innerHTML = '<option value="">-- Choose a Service --</option>';
      const filtered = servicesData.filter(s => s.category_id == categoryId);
      
      filtered.forEach(s => {
        const option = document.createElement('option');
        option.value = s.id;
        const rateInUserCurr = (parseFloat(s.rate) * userCurrencyRate).toFixed(4);
        option.textContent = `${s.name} - ${userCurrencySymbol}${rateInUserCurr} per 1,000`;
        option.dataset.rate = s.rate;
        option.dataset.min = s.min_quantity;
        option.dataset.max = s.max_quantity;
        option.dataset.desc = s.description || '';
        serviceSelect.appendChild(option);
      });

      if (filtered.length > 0) {
        serviceSelect.selectedIndex = 1;
        updateServiceDetails();
      } else {
        updateServiceDetails();
      }
    }

    function updateServiceDetails() {
      const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
      if (!selectedOption || !selectedOption.value) {
        if (serviceDescDisplay) serviceDescDisplay.textContent = 'Select a service above to view details.';
        if (minMaxDisplay) minMaxDisplay.textContent = 'Min: - / Max: -';
        if (totalPriceDisplay) totalPriceDisplay.textContent = `${userCurrencySymbol}0.00`;
        return;
      }

      const min = selectedOption.dataset.min;
      const max = selectedOption.dataset.max;
      const desc = selectedOption.dataset.desc || 'No specific instructions provided.';

      if (minMaxDisplay) minMaxDisplay.textContent = `Min: ${Number(min).toLocaleString()} | Max: ${Number(max).toLocaleString()}`;
      if (serviceDescDisplay) serviceDescDisplay.textContent = desc;

      quantityInput.min = min;
      quantityInput.max = max;
      if (!quantityInput.value || parseInt(quantityInput.value) < parseInt(min)) {
        quantityInput.value = min;
      }

      calculatePrice();
    }

    function calculatePrice() {
      const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
      if (!selectedOption || !selectedOption.value) {
        if (totalPriceDisplay) totalPriceDisplay.textContent = `${userCurrencySymbol}0.00`;
        return;
      }

      const baseRatePer1000 = parseFloat(selectedOption.dataset.rate);
      const quantity = parseInt(quantityInput.value) || 0;

      const costUsd = (baseRatePer1000 / 1000) * quantity;
      const costUserCurrency = costUsd * userCurrencyRate;

      if (totalPriceDisplay) {
        totalPriceDisplay.textContent = `${userCurrencySymbol}${costUserCurrency.toFixed(4)}`;
      }
    }

    categorySelect.addEventListener('change', function () {
      populateServices(this.value);
    });

    serviceSelect.addEventListener('change', updateServiceDetails);
    quantityInput.addEventListener('input', calculatePrice);

    // Initial trigger
    if (categorySelect.value) {
      populateServices(categorySelect.value);
    }
  }

  // 3. Auto dismiss flash alerts
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s ease';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500);
    }, 6000);
  });
});
