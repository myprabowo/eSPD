# Design System Specification — eSPD

> Extracted from the active implementation in [assets/css/style.css](assets/css/style.css), [assets/js/app.js](assets/js/app.js), [index.php](index.php), [pages/login.php](pages/login.php), [pages/dashboard.php](pages/dashboard.php), [pages/kegiatan.php](pages/kegiatan.php), [pages/spd_list.php](pages/spd_list.php), and [pages/spd_detail.php](pages/spd_detail.php).

---

## 1. Design Identity & Personality

- **Product**: **eSPD** — *Sistem Pengisian Surat Perintah Perjalanan Dinas Online* (Official Travel Order & Expense Management Web Application for Unit Pengembangan Layanan dan Bisnis).
- **Aesthetic Direction**: **Frosted Light Glassmorphism** — a cool slate-tinted canvas (`#f1f5f9`) layered with ambient radial blue/cyan glows (`body::before`), translucent frosted-glass cards (`rgba(255, 255, 255, 0.7)` with `backdrop-filter: blur(16px)`), and crisp royal-blue-to-sky-blue gradients (`--primary-500` → `--accent-500`). *(Note: `style.css` includes a legacy header comment mentioning "Dark Theme", but all active `:root` tokens implement a light-mode palette.)*
- **Theme Strategy**: **Single Light Mode** (`:root` variables only; no dark mode toggle or alternate theme selector).
- **Visual Character**: High-clarity administrative utility UI featuring spreadsheet-style inline-editable tables, tabular-numeral currency columns, role-specific attention cues for PIC Keuangan (alert banner, highlighted table rows, pulsing status badges), and inline SVG icons.

---

## 2. Color Tokens

### 2.1 CSS Custom Properties (`:root` in `assets/css/style.css`)

| Category | Token | Value | Usage |
| :--- | :--- | :--- | :--- |
| **Primary (Blue)** | `--primary-50` | `#eff6ff` | Lightest blue tint |
| | `--primary-100` | `#dbeafe` | Soft blue tint |
| | `--primary-200` | `#bfdbfe` | Light blue border/tint |
| | `--primary-300` | `#93c5fd` | Breadcrumb link hover |
| | `--primary-400` | `#60a5fa` | Brand gradient start, focus borders, active tabs, editable pencil icon, spinner |
| | `--primary-500` | `#3b82f6` | Primary button gradient start, avatar gradient start, checkbox accent |
| | `--primary-600` | `#2563eb` | Submitted badge text, total row text, attention borders/dots |
| | `--primary-700` | `#1d4ed8` | Active nav link text, submitted pulse badge text, attention gradient end |
| **Accent (Cyan / Sky)** | `--accent-400` | `#38bdf8` | Brand title gradient end |
| | `--accent-500` | `#0ea5e9` | Primary button gradient end, avatar gradient end |
| | `--accent-600` | `#0284c7` | Deep cyan accent |
| **Semantic** | `--success-500` | `#22c55e` | Paid badge text, green stat icon, success button gradient start |
| | `--success-600` | `#16a34a` | Success button gradient end |
| | `--warning-500` | `#f59e0b` | Verified badge text, orange stat icon |
| | `--warning-600` | `#d97706` | Deep amber warning |
| | `--danger-500` | `#ef4444` | Danger button text, logout hover, file delete, login error, revision banner border |
| | `--danger-600` | `#dc2626` | Deep red semantic |
| | `--info-500` | `#3b82f6` | Blue stat icon |
| **Neutrals & Surfaces** | `--bg-body` | `#f1f5f9` | Page and login canvas background |
| | `--bg-surface` | `#ffffff` | Base surface |
| | `--bg-card` | `#ffffff` | Modal surface, autocomplete dropdown, explicit card overrides |
| | `--bg-elevated` | `#f8fafc` | Secondary button background, uploaded file item rows |
| | `--bg-input` | `#ffffff` | Form inputs, selects, textareas, inline-edit inputs |
| | `--bg-hover` | `rgba(29, 78, 216, 0.05)` | Nav link, secondary button, table row, autocomplete, and checklist hover |
| **Borders** | `--border` | `rgba(15, 23, 42, 0.1)` | 1px structural borders (navbar, cards, inputs, tables, modals) |
| | `--border-hover` | `rgba(29, 78, 216, 0.25)` | Card and secondary button hover borders |
| **Typography** | `--text-primary` | `#0f172a` | Headings, table cells, primary copy, input text |
| | `--text-secondary` | `#475569` | Nav links, user name, table headers, form labels, banner body |
| | `--text-muted` | `#64748b` | Subtitles, placeholders, breadcrumbs, empty states, footer |
| | `--text-inverse` | `#ffffff` | Inverse text on solid fills |
| **Glassmorphism** | `--glass-bg` | `rgba(255, 255, 255, 0.7)` | `.card`, `.stat-card`, `.login-card` translucent background |
| | `--glass-border` | `rgba(255, 255, 255, 0.4)` | 1px glass edge border |
| | `--glass-blur` | `16px` | `backdrop-filter: blur(16px)` on cards |
| **Shadows** | `--shadow-sm` | `0 1px 2px rgba(15, 23, 42, 0.05)` | Alert banner elevation |
| | `--shadow-md` | `0 4px 6px -1px rgba(15, 23, 42, 0.1), 0 2px 4px -1px rgba(15, 23, 42, 0.06)` | Base `.card` elevation |
| | `--shadow-lg` | `0 10px 15px -3px rgba(15, 23, 42, 0.1), 0 4px 6px -2px rgba(15, 23, 42, 0.05)` | Hover cards, modals, toasts, autocomplete dropdowns |
| | `--shadow-glow` | `0 0 20px rgba(59, 130, 246, 0.2)` | Blue ambient glow on card hover and `.login-card` |

### 2.2 Additional Observed Palette Values

- **Brand SVG Logo Gradient** (`index.php`, `pages/login.php`): `linearGradient` from `#6366f1` to `#8b5cf6`.
- **Indigo Tints** (used directly in `style.css` rules): `rgba(99, 102, 241, 0.06–0.35)` for `thead`, table cell dividers, `.section-toggle`, `.upload-zone` hover, `.form-input:focus` ring, `.btn-primary` shadow, `tr.clickable:hover`, `.spinner` track, and custom scrollbar thumb.
- **Toast Gradients**:
  - `.toast-success`: `linear-gradient(135deg, #16a34a, #15803d)`
  - `.toast-error`: `linear-gradient(135deg, #dc2626, #b91c1c)`
  - `.toast-info`: `linear-gradient(135deg, #2563eb, #1d4ed8)`
- **Navbar Surface**: `rgba(255, 255, 255, 0.85)` with `backdrop-filter: blur(20px)`.
- **Overlays**: Modal backdrop `rgba(0, 0, 0, 0.6)` (`blur(4px)`); loading overlay `rgba(15, 15, 26, 0.7)`.

---

## 3. Typography System

- **Font Family**: `'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif`
- **Font Loading**: Google Fonts (`Inter:wght@300;400;500;600;700;800`) preconnected and loaded in `index.php`.
- **Root Scale**: `html { font-size: 14px; }` (`1rem = 14px`), `body { line-height: 1.6; -webkit-font-smoothing: antialiased; }`.

| Element / Class | Size | Weight | Line Height / Tracking | Notes |
| :--- | :--- | :--- | :--- | :--- |
| `.page-header h1`, `.login-logo h1` | `1.75rem` | `800` | `letter-spacing: -0.5px` | `.login-logo h1` uses gradient text clip |
| `.stat-info h3` | `1.5rem` | `800` | `line-height: 1.2` | Dashboard KPI counters |
| `.nav-title` | `1.25rem` | `800` | `letter-spacing: -0.5px` | Gradient text clip (`--primary-400` → `--accent-400`) |
| `.modal-header h2` | `1.15rem` | `700` | — | Modal title |
| `.card-header h2`, `.empty-state h3` | `1.1rem` | `700` / `600` | — | Section card headings |
| `.card-header h3` | `1rem` | `600` | — | Sub-card headings |
| `.form-input`, `.form-select`, `.form-textarea`, `.page-header .subtitle`, `.section-toggle` | `0.9rem` | `400` / `600` | — | Form controls & section toggles |
| `.nav-link` | `0.875rem` | `500` | — | Top navigation items |
| `table`, `.btn`, `.toast`, `.autocomplete-item`, `.upload-zone p`, `.app-footer` | `0.85rem` | `400`–`600` | `.btn`: `1.4` | Standard UI density |
| `.tab-btn`, `.breadcrumb` | `0.82rem` | `600` / `400` | — | Tab labels & breadcrumbs |
| `.form-label`, `.stat-info p`, `.user-avatar` | `0.8rem` | `600` / `500` / `700` | `.form-label`: `letter-spacing: 0.3px` | `.form-label` is `text-transform: uppercase` |
| `th`, `.btn-sm` | `0.78rem` | `600` | `letter-spacing: 0.5px` (`th`) | `th` is `text-transform: uppercase` |
| `.badge` | `0.72rem` | `600` | `letter-spacing: 0.3px` | Pill badges, `text-transform: uppercase` |
| `.currency` | Inherited | Inherited | `font-variant-numeric: tabular-nums` | `font-feature-settings: 'tnum'; text-align: right;` |

---

## 4. Layout, Spacing & Radii

### 4.1 Page Shell & Containers
- **Sticky Header (`.main-nav`)**: `height: 60px`, `padding: 0 1.5rem`, `gap: 1.5rem`, `z-index: 100`.
- **Main Content (`.main-content`)**: `max-width: 1600px`, `width: 100%`, `padding: 1.5rem`, `margin: 0 auto`, `flex: 1`.
- **Inner Container (`.container`)**: `max-width: 1400px`, `margin: 0 auto`.
- **Login Container (`.login-container`)**: Centered full-viewport flex layout (`.login-body`), `max-width: 400px`, `padding: 1rem`.
- **Footer (`.app-footer`)**: `padding: 2rem 1rem`, `margin-top: auto`, centered.

### 4.2 Grid Patterns
- **KPI Grid (`.stats-grid`)**: `grid-template-columns: repeat(auto-fit, minmax(220px, 1fr))`, `gap: 1rem`.
- **Dashboard Split (`pages/dashboard.php`)**: Inline 2-column grid `grid-template-columns: 1fr 1fr; gap: 1rem;`.
- **SPD Detail Split (`pages/spd_detail.php`)**: Inline 2-column grid `grid-template-columns: 1fr 340px; gap: 1rem; align-items: start;`.
- **Form Grids**:
  - `.form-grid`: `repeat(auto-fit, minmax(220px, 1fr))`, `gap: 0.8rem`.
  - `.form-grid-2`: `repeat(2, 1fr)`.
  - `.form-grid-3`: `repeat(3, 1fr)`.

### 4.3 Border Radius Tokens

| Token | Value | Applied To |
| :--- | :--- | :--- |
| `--radius-sm` | `6px` | `.btn-logout`, `.btn-icon`, inline-edit inputs, `.modal-close`, `.file-delete` |
| `--radius-md` | `10px` | `.nav-link`, `.stat-icon`, `.btn`, `.form-input`/`.form-select`/`.form-textarea`, `.section-toggle`, `.file-item`, `.toast`, `.autocomplete-dropdown`, `.pengajar-item`, `.alert-banner-icon` |
| `--radius-lg` | `16px` | `.card`, `.stat-card`, `.table-wrapper`, `.alert-banner-keuangan`, `.upload-zone` |
| `--radius-xl` | `20px` | `.modal`, `.login-card` |
| Pill / Circle | `999px` / `50%` | `.badge`, `.stat-badge-sub` (`999px`); `.user-avatar`, `.pulse-dot`, `.spinner` (`50%`) |

### 4.4 Responsive Breakpoints
- **Single Breakpoint — `@media (max-width: 768px)`**:
  - `.main-nav`: `padding: 0 0.75rem; gap: 0.5rem;`
  - `.nav-links`: `display: none;` (hidden on mobile)
  - `.main-content`: `padding: 1rem 0.75rem;`
  - `.page-header`: `flex-direction: column;`
  - `.form-grid-2`, `.form-grid-3`: `grid-template-columns: 1fr;`
  - `.stats-grid`: `grid-template-columns: 1fr 1fr;` (2-column KPI grid on mobile)
  - `.modal`: `margin: 0.5rem;`

---

## 5. Core Components

- **Top Navigation (`.main-nav`)**: Sticky frosted bar with SVG logo mark, gradient `.nav-title`, `.nav-links` (`.nav-link.active` uses `rgba(37, 99, 235, 0.12)` background and `--primary-700` text), and `.nav-user` with circular `.user-avatar` and `.btn-logout`.
- **Cards & Stat Cards**: `.card` (`padding: 1.5rem`, glassmorphism, hover glow) with `.card-header` bottom divider; `.stat-card` (`padding: 1.25rem`, flex row with `48px × 48px` `.stat-icon.{purple,blue,green,orange}`). `.stat-card-attention` adds a `3px` top gradient bar and tinted background for pending PIC Keuangan reviews.
- **Alert Banners**: `.alert-banner-keuangan` (left `4px` `--primary-600` accent border, gradient icon box) for pending verifications; inline revision/rejection banner in `pages/spd_detail.php` (`4px` `--danger-500` left border).
- **Buttons**: `.btn` base (`0.55rem 1rem`), `.btn-primary` (blue-cyan gradient + shadow), `.btn-secondary` (`--bg-elevated` + `--border`), `.btn-success` (green gradient), `.btn-danger` (translucent red tint + border), `.btn-sm` (`0.35rem 0.7rem`), `.btn-icon` (`0.4rem`).
- **Tables & Inline Editing**: `.table-wrapper` (`overflow-x: auto`, `--radius-lg`), `th` uppercase headers, `tr.clickable`, `tr.tr-submitted-highlight` (`3.5px` left `--primary-600` border). `td.editable` reveals a hover `✎` indicator, switches to `.editing` with an inline `<input>`/`<select>`, and animates `.cell-saving` → `.cell-saved`.
- **Forms, Search & Pickers**: `.form-group` + `.form-label` + `.form-input`/`.form-select`/`.form-textarea`; `.search-input-wrapper` with left-aligned `16px` icon (`padding-left: 2.2rem`); `.autocomplete-wrapper` + `.autocomplete-dropdown`; `.pengajar-list` scrollable checklist with `.pengajar-item` checkboxes (`accent-color: var(--primary-500)`).
- **Modals**: `.modal-overlay` (`z-index: 200`, `rgba(0,0,0,0.6)` + `blur(4px)`) wrapping `.modal` (`max-width: 640px`, `.modal-lg` `900px`, `max-height: 85vh`), structured into `.modal-header`, `.modal-body`, and `.modal-footer`.
- **Status Badges**: `.badge` pill with semantic variants `.badge-draft`, `.badge-submitted`, `.badge-verified`, `.badge-paid`, and `.badge-submitted-pulse` paired with `.pulse-dot`.
- **Accordions & Tabs**: Collapsible `.section-group` (`.section-toggle` + `.section-content` max-height transition); `.tab-nav` with `.tab-btn.active` (`2px` `--primary-400` bottom border) and `.tab-panel`.
- **File Upload Zone**: Dashed `.upload-zone` supporting click and drag-and-drop (`.dragover`), paired with `.file-list` / `.file-item` rows.
- **Feedback & Navigation Helpers**: Fixed `#toast-container` (`top: 70px; right: 1rem; z-index: 300;`), `.loading-overlay` (`z-index: 250`), `.spinner`, `.empty-state`, and `.breadcrumb`.

---

## 6. Motion, Interaction & Accessibility

- **Transition Tokens**:
  - `--transition-fast`: `150ms cubic-bezier(0.4, 0, 0.2, 1)` (links, buttons, inputs, table rows, accordion arrow)
  - `--transition-base`: `250ms cubic-bezier(0.4, 0, 0.2, 1)` (cards, modals, upload zone)
  - `--transition-slow`: `400ms cubic-bezier(0.4, 0, 0.2, 1)` (accordion `max-height` expand/collapse)
- **Keyframe Animations**:
  - `cellSaved` (`0.6s ease`): Green flash on inline-edited cell save.
  - `pulseDot` (`1.5s infinite`): Expanding blue ring on `.pulse-dot`.
  - `fadeInBanner` (`0.35s ease-out`): Subtle `-6px` slide-down for `.alert-banner-keuangan`.
  - `toastIn` / `toastOut` (`0.3s ease`): `40px` horizontal slide-in/out for toasts (auto-dismissed after `3500ms`).
  - `fadeIn` (`0.2s ease`): `4px` upward fade for active `.tab-panel`.
  - `spin` (`0.6s linear infinite`): Loading `.spinner`.
- **Interactive & Keyboard Behavior (`assets/js/app.js`)**:
  - **Modals**: Close on backdrop click or `Escape` key; sets `document.body.style.overflow = 'hidden'` while open.
  - **Inline Table Editing**: Click `.editable` cell to focus/select input; `Enter` blurs and saves via `api/spd_api.php`; `Escape` cancels and restores original value.
  - **Upload Zone**: Supports dragover/dragleave/drop and click-to-browse.
- **Accessibility Notes**:
  - Semantic landmarks (`<nav>`, `<main>`, `<footer>`) and `<label for="...">` associations are used across pages.
  - Login form uses `autocomplete="username"`, `autocomplete="current-password"`, `required`, and `autofocus`.
  - Icon-only action buttons include `title` attributes (`Logout`, `Edit`, `Hapus`).
  - Native `outline: none` is applied on `.form-input:focus` and `td.editable input`, compensated on form controls with `border-color: var(--primary-400)` and `box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15)`. Explicit `aria-*` and `role` attributes are not present in the current templates.
