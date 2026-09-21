/**
 * Rose SMM Panel - Vanilla JavaScript (Admin Panel)
 */

document.addEventListener('DOMContentLoaded', function () {
  // Mobile admin sidebar toggle
  const adminToggle = document.querySelector('.mobile-toggle');
  const adminSidebar = document.querySelector('.panel-sidebar');
  if (adminToggle && adminSidebar) {
    adminToggle.addEventListener('click', function () {
      adminSidebar.classList.toggle('open');
    });
  }

  // Margin calculation preview
  const baseRateInput = document.getElementById('provider_rate');
  const marginInput = document.getElementById('margin_percentage');
  const finalRateDisplay = document.getElementById('final_rate_preview');

  if (baseRateInput && marginInput && finalRateDisplay) {
    function calcMargin() {
      const base = parseFloat(baseRateInput.value) || 0;
      const margin = parseFloat(marginInput.value) || 0;
      const finalVal = base * (1 + (margin / 100));
      finalRateDisplay.textContent = '$' + finalVal.toFixed(4);
    }
    baseRateInput.addEventListener('input', calcMargin);
    marginInput.addEventListener('input', calcMargin);
  }
});
