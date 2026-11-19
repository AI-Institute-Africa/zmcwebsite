import './bootstrap';
// Bootstrap JS (make available globally for inline Blade scripts)
import * as bootstrap from 'bootstrap';
// Some Blade templates call `bootstrap.Modal` directly.
// Ensure a global is available in both Vite and non-Vite contexts.
window.bootstrap = bootstrap;
