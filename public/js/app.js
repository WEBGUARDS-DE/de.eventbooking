const stripe = Stripe(stripePublicKey);
let selectedTerminId = null;

/**
 * Load termine and display
 */
async function loadTermine() {
    try {
        const response = await fetch(apiUrl + '/get-termine');
        const data = await response.json();
        
        if (!data.success) throw new Error(data.error);
        
        const container = document.getElementById('termine-container');
        container.innerHTML = '';
        
        if (data.termine.length === 0) {
            container.innerHTML = '<p>Keine Termine verfügbar</p>';
            return;
        }
        
        data.termine.forEach(termin => {
            const btn = document.createElement('button');
            btn.className = 'termin-btn' + (termin.ausgebucht ? ' disabled' : '');
            btn.disabled = termin.ausgebucht;
            btn.dataset.terminId = termin.id;
            
            btn.onclick = (e) => {
                e.preventDefault();
                selectTermin(termin.id, btn);
            };
            
            const status = termin.ausgebucht 
                ? '❌ Ausgebucht' 
                : `✅ noch ${termin.verfuegbar} Plätze`;
            
            btn.innerHTML = `
                <span class="termin-label">${escapeHtml(termin.label)}</span>
                <span class="termin-verfuegbar ${termin.ausgebucht ? 'ausgebucht' : ''}">
                    ${status}
                </span>
            `;
            
            container.appendChild(btn);
        });
    } catch (error) {
        console.error('Error loading termine:', error);
        document.getElementById('termine-container').innerHTML = '❌ Fehler beim Laden: ' + error.message;
    }
}

/**
 * Select termin and show checkout
 */
function selectTermin(terminId, element) {
    selectedTerminId = terminId;
    
    // Highlight selected
    document.querySelectorAll('.termin-btn').forEach(btn => btn.classList.remove('selected'));
    element.classList.add('selected');
    
    // Show checkout
    document.getElementById('checkout-container').style.display = 'block';
}

/**
 * Trigger checkout
 */
document.addEventListener('DOMContentLoaded', function() {
    const checkoutBtn = document.getElementById('checkout-btn');
    
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', async function() {
            if (!selectedTerminId) {
                alert('Bitte wähle einen Termin aus');
                return;
            }
            
            this.disabled = true;
            const originalText = this.textContent;
            this.textContent = '⏳ Wird weitergeleitet...';
            
            try {
                const response = await fetch(apiUrl + '/create-checkout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ termin_id: selectedTerminId })
                });
                
                const data = await response.json();
                
                if (!data.success) throw new Error(data.error);
                
                // Redirect to Stripe
                window.location.href = data.url;
            } catch (error) {
                alert('❌ Fehler: ' + error.message);
                this.disabled = false;
                this.textContent = originalText;
            }
        });
    }
    
    // Initial load
    loadTermine();
    
    // Refresh every 30 seconds
    setInterval(loadTermine, 30000);
});

/**
 * HTML escape
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
