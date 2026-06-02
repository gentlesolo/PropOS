/**
 * PropOS Embeddable Widgets — Web Components
 * Usage: <script src="https://cdn.propos.app/widgets.js" defer></script>
 *
 * Available components:
 *   <propos-listings-grid agency-key="..." primary-color="#1E40AF" items-per-page="9" view-type="grid">
 *   <propos-listing-details agency-key="..." listing-id="123">
 *   <propos-inquiry-form agency-key="..." listing-id="123">
 *   <propos-booking-scheduler agency-key="..." agent-id="1">
 */

(function () {
  'use strict';

  const API_BASE = 'https://propos.app/api/v1/public';

  // ─── Shared helpers ──────────────────────────────────────────────────────

  async function apiFetch(path, apiKey, options = {}) {
    const res = await fetch(`${API_BASE}${path}`, {
      ...options,
      headers: {
        'Authorization': `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
        ...(options.headers || {}),
      },
    });
    if (!res.ok) throw new Error(`PropOS API error: ${res.status}`);
    return res.json();
  }

  function currencyFmt(amount) {
    if (amount == null) return '';
    return new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(amount);
  }

  function baseStyles(primary) {
    return `
      :host { display: block; font-family: system-ui, -apple-system, sans-serif; color: #111; }
      * { box-sizing: border-box; }
      .propos-btn {
        display: inline-block; padding: 10px 20px; background: ${primary}; color: #fff;
        border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600;
        text-decoration: none; transition: opacity .2s;
      }
      .propos-btn:hover { opacity: .85; }
      .propos-spinner {
        width: 36px; height: 36px; border: 3px solid #e5e7eb;
        border-top-color: ${primary}; border-radius: 50%;
        animation: spin .7s linear infinite; margin: 40px auto; display: block;
      }
      @keyframes spin { to { transform: rotate(360deg); } }
      .propos-error { padding: 16px; color: #b91c1c; background: #fef2f2; border-radius: 8px; font-size: 14px; }
      .propos-badge {
        display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600;
      }
      .badge-active { background: #dcfce7; color: #166534; }
      .badge-rental { background: #eff6ff; color: #1e40af; }
    `;
  }

  // ─── <propos-listings-grid> ───────────────────────────────────────────────

  class PropOSListingsGrid extends HTMLElement {
    static get observedAttributes() {
      return ['agency-key', 'primary-color', 'items-per-page', 'view-type', 'city', 'mandate-type'];
    }

    constructor() {
      super();
      this.attachShadow({ mode: 'open' });
      this._page = 1;
    }

    connectedCallback() { this._render(); }
    attributeChangedCallback() { this._page = 1; this._render(); }

    get _primary() { return this.getAttribute('primary-color') || '#1E40AF'; }
    get _key()     { return this.getAttribute('agency-key') || ''; }
    get _perPage() { return parseInt(this.getAttribute('items-per-page') || '9', 10); }
    get _view()    { return this.getAttribute('view-type') || 'grid'; }

    async _render() {
      if (!this._key) {
        this.shadowRoot.innerHTML = `<p class="propos-error">agency-key attribute is required.</p>`;
        return;
      }

      this.shadowRoot.innerHTML = `<style>${baseStyles(this._primary)}</style><span class="propos-spinner"></span>`;

      try {
        const params = new URLSearchParams({
          per_page: this._perPage,
          page: this._page,
        });
        if (this.getAttribute('city'))         params.set('city',         this.getAttribute('city'));
        if (this.getAttribute('mandate-type')) params.set('mandate_type', this.getAttribute('mandate-type'));

        const data = await apiFetch(`/listings?${params}`, this._key);
        this._renderGrid(data);
      } catch (e) {
        this.shadowRoot.innerHTML = `<style>${baseStyles(this._primary)}</style>
          <p class="propos-error">Could not load listings. ${e.message}</p>`;
      }
    }

    _renderGrid(data) {
      const listings = data.data || [];
      const meta     = data.meta || {};

      const cards = listings.map(l => {
        const badge = l.mandate_type === 'rental'
          ? `<span class="propos-badge badge-rental">For Rent</span>`
          : `<span class="propos-badge badge-active">For Sale</span>`;

        const img = l.cover_photo
          ? `<img src="${l.cover_photo}" alt="${l.headline || ''}" style="width:100%;height:190px;object-fit:cover;">`
          : `<div style="width:100%;height:190px;background:#e5e7eb;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:13px;">No Photo</div>`;

        return `
          <div class="propos-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;cursor:pointer;"
               data-id="${l.id}">
            <div style="position:relative;">${img}
              <div style="position:absolute;top:10px;left:10px;">${badge}</div>
            </div>
            <div style="padding:14px 16px 16px;">
              <div style="font-weight:700;font-size:16px;color:${this._primary};margin-bottom:4px;">
                ${l.property?.city ? l.property.city + ' · ' : ''}${currencyFmt(l.listing_price)}
              </div>
              <div style="font-size:13px;color:#374151;font-weight:600;margin-bottom:6px;line-height:1.4;">
                ${l.headline || l.property?.address || ''}
              </div>
              <div style="font-size:12px;color:#6b7280;display:flex;gap:12px;flex-wrap:wrap;">
                ${l.property?.bedrooms  != null ? `<span>🛏 ${l.property.bedrooms} bed</span>` : ''}
                ${l.property?.bathrooms != null ? `<span>🚿 ${l.property.bathrooms} bath</span>` : ''}
                ${l.property?.floor_area_sqm ? `<span>📐 ${l.property.floor_area_sqm} m²</span>` : ''}
              </div>
            </div>
          </div>`;
      }).join('');

      const pager = `
        <div style="display:flex;justify-content:center;align-items:center;gap:12px;margin-top:24px;font-size:14px;">
          ${meta.current_page > 1
            ? `<button class="propos-btn propos-prev" style="padding:8px 16px;background:${this._primary};">← Prev</button>` : ''}
          <span style="color:#6b7280;">Page ${meta.current_page || 1} of ${meta.last_page || 1}</span>
          ${meta.current_page < meta.last_page
            ? `<button class="propos-btn propos-next" style="padding:8px 16px;background:${this._primary};">Next →</button>` : ''}
        </div>`;

      this.shadowRoot.innerHTML = `
        <style>
          ${baseStyles(this._primary)}
          .propos-grid { display: grid; gap: 20px;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
        </style>
        <div class="propos-grid">${cards}</div>
        ${pager}`;

      this.shadowRoot.querySelectorAll('.propos-card').forEach(card => {
        card.addEventListener('click', () => {
          this.dispatchEvent(new CustomEvent('propos-listing-selected', {
            bubbles: true, composed: true, detail: { id: card.dataset.id }
          }));
        });
      });

      this.shadowRoot.querySelector('.propos-prev')?.addEventListener('click', () => {
        this._page--; this._render();
      });
      this.shadowRoot.querySelector('.propos-next')?.addEventListener('click', () => {
        this._page++; this._render();
      });
    }
  }

  // ─── <propos-listing-details> ─────────────────────────────────────────────

  class PropOSListingDetails extends HTMLElement {
    static get observedAttributes() { return ['agency-key', 'listing-id', 'primary-color']; }

    constructor() { super(); this.attachShadow({ mode: 'open' }); }

    connectedCallback() { this._render(); }
    attributeChangedCallback() { this._render(); }

    get _primary() { return this.getAttribute('primary-color') || '#1E40AF'; }
    get _key()     { return this.getAttribute('agency-key') || ''; }
    get _id()      { return this.getAttribute('listing-id') || ''; }

    async _render() {
      if (!this._key || !this._id) {
        this.shadowRoot.innerHTML = `<p class="propos-error">agency-key and listing-id are required.</p>`;
        return;
      }
      this.shadowRoot.innerHTML = `<style>${baseStyles(this._primary)}</style><span class="propos-spinner"></span>`;

      try {
        const l = await apiFetch(`/listings/${this._id}`, this._key);

        const photos = (l.media || []).filter(m => m.type === 'image');
        const gallery = photos.length
          ? `<div style="display:flex;gap:8px;overflow-x:auto;padding-bottom:8px;">
              ${photos.map(p => `<img src="${p.url}" alt="" style="height:220px;border-radius:8px;flex-shrink:0;">`).join('')}
            </div>`
          : (l.cover_photo ? `<img src="${l.cover_photo}" alt="" style="width:100%;border-radius:10px;max-height:320px;object-fit:cover;">` : '');

        this.shadowRoot.innerHTML = `
          <style>
            ${baseStyles(this._primary)}
            .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 24px; margin: 16px 0; }
            .detail-item { font-size: 13px; }
            .detail-item label { color: #6b7280; font-size: 11px; display: block; margin-bottom: 2px; text-transform: uppercase; letter-spacing: .04em; }
          </style>
          <div>
            ${gallery}
            <h2 style="font-size:22px;font-weight:800;margin:16px 0 4px;color:${this._primary};">
              ${currencyFmt(l.listing_price)}
            </h2>
            <p style="font-size:15px;font-weight:600;color:#111;margin:0 0 4px;">${l.headline || ''}</p>
            <p style="font-size:13px;color:#6b7280;margin:0 0 16px;">
              ${[l.property?.address, l.property?.city, l.property?.state].filter(Boolean).join(', ')}
            </p>

            <div class="detail-grid">
              ${l.property?.bedrooms  != null ? `<div class="detail-item"><label>Bedrooms</label>${l.property.bedrooms}</div>` : ''}
              ${l.property?.bathrooms != null ? `<div class="detail-item"><label>Bathrooms</label>${l.property.bathrooms}</div>` : ''}
              ${l.property?.floor_area_sqm ? `<div class="detail-item"><label>Floor Area</label>${l.property.floor_area_sqm} m²</div>` : ''}
              ${l.property?.land_area_sqm  ? `<div class="detail-item"><label>Land Area</label>${l.property.land_area_sqm} m²</div>` : ''}
              ${l.property?.parking_spaces ? `<div class="detail-item"><label>Parking</label>${l.property.parking_spaces}</div>` : ''}
              ${l.property?.type           ? `<div class="detail-item"><label>Type</label>${l.property.type}</div>` : ''}
              <div class="detail-item"><label>Status</label>${l.mandate_type === 'rental' ? 'For Rent' : 'For Sale'}</div>
              ${l.days_on_market != null ? `<div class="detail-item"><label>Days on Market</label>${l.days_on_market}</div>` : ''}
            </div>

            ${l.description ? `<p style="font-size:14px;line-height:1.7;color:#374151;margin:16px 0;">${l.description}</p>` : ''}

            ${l.features?.length ? `
              <div style="margin:16px 0;">
                <p style="font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;margin-bottom:8px;">Features</p>
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                  ${l.features.map(f => `<span style="background:#f3f4f6;border-radius:999px;padding:4px 10px;font-size:12px;">${f}</span>`).join('')}
                </div>
              </div>` : ''}

            ${l.agent ? `
              <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;background:#f9fafb;border-radius:10px;margin-top:20px;">
                ${l.agent.avatar ? `<img src="${l.agent.avatar}" alt="" style="width:44px;height:44px;border-radius:50%;object-fit:cover;">` : ''}
                <div>
                  <div style="font-weight:700;font-size:14px;">${l.agent.name}</div>
                  <div style="font-size:12px;color:#6b7280;">${l.agent.job_title || ''}</div>
                  <div style="font-size:12px;color:${this._primary};">${l.agent.phone || ''}</div>
                </div>
              </div>` : ''}
          </div>`;
      } catch (e) {
        this.shadowRoot.innerHTML = `<style>${baseStyles(this._primary)}</style>
          <p class="propos-error">Could not load listing. ${e.message}</p>`;
      }
    }
  }

  // ─── <propos-inquiry-form> ────────────────────────────────────────────────

  class PropOSInquiryForm extends HTMLElement {
    static get observedAttributes() { return ['agency-key', 'listing-id', 'primary-color']; }

    constructor() { super(); this.attachShadow({ mode: 'open' }); }

    connectedCallback() { this._render(); }
    attributeChangedCallback() { this._render(); }

    get _primary() { return this.getAttribute('primary-color') || '#1E40AF'; }
    get _key()     { return this.getAttribute('agency-key') || ''; }
    get _listingId(){ return this.getAttribute('listing-id') || ''; }

    _render() {
      this.shadowRoot.innerHTML = `
        <style>
          ${baseStyles(this._primary)}
          .propos-form { display: flex; flex-direction: column; gap: 12px; }
          .propos-field label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px; }
          .propos-field input,
          .propos-field textarea {
            width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px;
            font-size: 14px; outline: none; transition: border-color .15s; font-family: inherit;
          }
          .propos-field input:focus,
          .propos-field textarea:focus { border-color: ${this._primary}; }
          .propos-success { padding: 16px; background: #f0fdf4; color: #166534; border-radius: 8px; font-size: 14px; font-weight: 600; }
        </style>
        <form class="propos-form" id="pf">
          <div class="propos-field">
            <label>First Name *</label>
            <input type="text" name="first_name" required placeholder="Jane">
          </div>
          <div class="propos-field">
            <label>Last Name *</label>
            <input type="text" name="last_name" required placeholder="Smith">
          </div>
          <div class="propos-field">
            <label>Email</label>
            <input type="email" name="email" placeholder="jane@example.com">
          </div>
          <div class="propos-field">
            <label>Phone</label>
            <input type="tel" name="phone" placeholder="+27 82 000 0000">
          </div>
          <div class="propos-field">
            <label>Message</label>
            <textarea name="message" rows="4" placeholder="I'd like to know more about this property…"></textarea>
          </div>
          <div id="pf-error" style="display:none;" class="propos-error"></div>
          <button type="submit" class="propos-btn" id="pf-btn">Send Inquiry</button>
        </form>`;

      const form   = this.shadowRoot.getElementById('pf');
      const btn    = this.shadowRoot.getElementById('pf-btn');
      const errBox = this.shadowRoot.getElementById('pf-error');

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        btn.disabled = true;
        btn.textContent = 'Sending…';
        errBox.style.display = 'none';

        const fd = new FormData(form);
        const body = {
          first_name: fd.get('first_name'),
          last_name:  fd.get('last_name'),
          email:      fd.get('email')   || undefined,
          phone:      fd.get('phone')   || undefined,
          message:    fd.get('message') || undefined,
        };
        if (this._listingId) body.listing_id = parseInt(this._listingId, 10);

        try {
          await apiFetch('/leads', this._key, { method: 'POST', body: JSON.stringify(body) });
          form.innerHTML = `<div class="propos-success">✓ Thank you! An agent will be in touch shortly.</div>`;
        } catch (err) {
          errBox.textContent = 'Something went wrong. Please try again.';
          errBox.style.display = 'block';
          btn.disabled = false;
          btn.textContent = 'Send Inquiry';
        }
      });
    }
  }

  // ─── <propos-booking-scheduler> ───────────────────────────────────────────

  class PropOSBookingScheduler extends HTMLElement {
    static get observedAttributes() { return ['agency-key', 'agent-id', 'primary-color', 'listing-id']; }

    constructor() {
      super();
      this.attachShadow({ mode: 'open' });
      this._selectedSlot = null;
    }

    connectedCallback() { this._render(); }
    attributeChangedCallback() { this._render(); }

    get _primary()   { return this.getAttribute('primary-color') || '#1E40AF'; }
    get _key()       { return this.getAttribute('agency-key') || ''; }
    get _agentId()   { return this.getAttribute('agent-id') || ''; }
    get _listingId() { return this.getAttribute('listing-id') || ''; }

    async _render() {
      if (!this._key || !this._agentId) {
        this.shadowRoot.innerHTML = `<p class="propos-error">agency-key and agent-id are required.</p>`;
        return;
      }
      this.shadowRoot.innerHTML = `<style>${baseStyles(this._primary)}</style><span class="propos-spinner"></span>`;

      try {
        const tz     = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const params = new URLSearchParams({ timezone: tz });
        const data   = await apiFetch(`/agents/${this._agentId}/availability?${params}`, this._key);

        this._slots = data.slots || [];
        this._agent = data.agent || {};
        this._renderScheduler();
      } catch (e) {
        this.shadowRoot.innerHTML = `<style>${baseStyles(this._primary)}</style>
          <p class="propos-error">Could not load availability. ${e.message}</p>`;
      }
    }

    _renderScheduler() {
      const byDate = {};
      for (const slot of this._slots) {
        (byDate[slot.date] = byDate[slot.date] || []).push(slot);
      }

      const dates = Object.keys(byDate).slice(0, 7);

      const dateBtns = dates.map(d => {
        const dt = new Date(d + 'T00:00:00');
        const label = dt.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
        return `<button class="propos-date-btn propos-btn" data-date="${d}"
                  style="background:transparent;color:${this._primary};border:1.5px solid ${this._primary};padding:8px 14px;font-size:12px;">
                  ${label}
                </button>`;
      }).join('');

      this.shadowRoot.innerHTML = `
        <style>
          ${baseStyles(this._primary)}
          .date-row { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
          .propos-date-btn.selected { background: ${this._primary} !important; color: #fff !important; }
          .time-grid { display: flex; flex-wrap: wrap; gap: 8px; }
          .propos-time-btn { padding: 8px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px;
            background: #fff; font-size: 13px; cursor: pointer; transition: all .15s; }
          .propos-time-btn:hover, .propos-time-btn.selected {
            border-color: ${this._primary}; color: ${this._primary}; font-weight: 700; }
          .confirm-btn { margin-top: 16px; }
          .propos-success { padding: 16px; background: #f0fdf4; color: #166534; border-radius: 8px; font-size: 14px; font-weight: 600; }
        </style>

        ${this._agent.name ? `<p style="font-size:13px;color:#6b7280;margin:0 0 12px;">Book a viewing with <strong>${this._agent.name}</strong></p>` : ''}

        <div class="date-row">${dateBtns}</div>
        <div id="time-slots" class="time-grid"></div>
        <div id="confirm-area"></div>`;

      // Wire date buttons
      this.shadowRoot.querySelectorAll('.propos-date-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          this.shadowRoot.querySelectorAll('.propos-date-btn').forEach(b => b.classList.remove('selected'));
          btn.classList.add('selected');
          this._selectedSlot = null;
          this._renderTimes(byDate[btn.dataset.date] || []);
        });
      });
    }

    _renderTimes(slots) {
      const container = this.shadowRoot.getElementById('time-slots');
      const confirm   = this.shadowRoot.getElementById('confirm-area');
      confirm.innerHTML = '';

      container.innerHTML = slots.map(s =>
        `<button class="propos-time-btn" data-slot='${JSON.stringify(s)}'>${s.time}</button>`
      ).join('');

      container.querySelectorAll('.propos-time-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          container.querySelectorAll('.propos-time-btn').forEach(b => b.classList.remove('selected'));
          btn.classList.add('selected');
          this._selectedSlot = JSON.parse(btn.dataset.slot);
          this._renderConfirm();
        });
      });
    }

    _renderConfirm() {
      const confirm = this.shadowRoot.getElementById('confirm-area');
      const slot = this._selectedSlot;
      confirm.innerHTML = `
        <div style="margin-top:16px;padding:14px;background:#f9fafb;border-radius:10px;">
          <p style="font-size:13px;font-weight:600;margin:0 0 12px;">
            Selected: ${new Date(slot.datetime).toLocaleDateString(undefined,{weekday:'long',month:'short',day:'numeric'})} at ${slot.time}
          </p>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <input id="bs-name" type="text" placeholder="Your full name *"
              style="padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:inherit;">
            <input id="bs-phone" type="tel" placeholder="Phone number *"
              style="padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:inherit;">
            <input id="bs-email" type="email" placeholder="Email address"
              style="padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:inherit;">
          </div>
          <div id="bs-error" style="display:none;margin-top:8px;" class="propos-error"></div>
          <button id="bs-confirm" class="propos-btn confirm-btn">Confirm Booking</button>
        </div>`;

      confirm.querySelector('#bs-confirm').addEventListener('click', async () => {
        const name  = confirm.querySelector('#bs-name').value.trim();
        const phone = confirm.querySelector('#bs-phone').value.trim();
        const email = confirm.querySelector('#bs-email').value.trim();
        const err   = confirm.querySelector('#bs-error');

        if (!name || !phone) {
          err.textContent = 'Name and phone are required.';
          err.style.display = 'block';
          return;
        }

        const [firstName, ...rest] = name.split(' ');

        try {
          const body = {
            first_name:   firstName,
            last_name:    rest.join(' ') || '-',
            phone,
            email:        email || undefined,
            listing_id:   this._listingId ? parseInt(this._listingId, 10) : undefined,
            agent_id:     parseInt(this._agentId, 10),
            scheduled_at: slot.datetime_local,
            timezone:     slot.timezone,
            message:      `Viewing request — ${slot.date} at ${slot.time}`,
          };

          const result = await apiFetch('/bookings', this._key, { method: 'POST', body: JSON.stringify(body) });

          this.dispatchEvent(new CustomEvent('propos-booking-confirmed', {
            bubbles: true, composed: true,
            detail: { slot, viewing_id: result.viewing_id, name, phone, email },
          }));

          confirm.innerHTML = `<div class="propos-success" style="margin-top:16px;">
            ✓ Viewing booked for <strong>${new Date(slot.datetime).toLocaleDateString(undefined,{weekday:'long',month:'short',day:'numeric'})}</strong> at <strong>${slot.time}</strong>.<br>
            <span style="font-weight:400;font-size:13px;">${result.agent?.name ?? ''} will confirm shortly.</span>
          </div>`;
        } catch (e) {
          const msg = e.message.includes('409') ? 'That slot was just taken. Please choose another time.' : 'Could not submit booking. Please try again.';
          err.textContent = msg;
          err.style.display = 'block';
        }
      });
    }
  }

  // ─── Register all custom elements ─────────────────────────────────────────

  if (!customElements.get('propos-listings-grid')) {
    customElements.define('propos-listings-grid',       PropOSListingsGrid);
  }
  if (!customElements.get('propos-listing-details')) {
    customElements.define('propos-listing-details',     PropOSListingDetails);
  }
  if (!customElements.get('propos-inquiry-form')) {
    customElements.define('propos-inquiry-form',        PropOSInquiryForm);
  }
  if (!customElements.get('propos-booking-scheduler')) {
    customElements.define('propos-booking-scheduler',   PropOSBookingScheduler);
  }

})();
