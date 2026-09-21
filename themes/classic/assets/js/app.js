/**
 * Rose SMM Panel - Vanilla JavaScript (User Panel & Public)
 * No frameworks - pure vanilla JS
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Mobile Sidebar Toggle
  const mobileToggle = document.querySelector('.mobile-toggle');
  const sidebar = document.querySelector('.panel-sidebar');
  if (mobileToggle && sidebar) {
    mobileToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });

    // Close when clicking outside
    document.addEventListener('click', function (e) {
      if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target) && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
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

    function populateServices(categoryId) {
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
