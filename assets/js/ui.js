(() => {
  "use strict";

  const themeKey = "fedpoffa_theme";
  const icons = {
    x: '<path d="M18 6 6 18"></path><path d="m6 6 12 12"></path>',
    sun: '<circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path>',
    moon: '<path d="M20.99 13.25A8.5 8.5 0 1 1 10.75 3.01 7 7 0 0 0 20.99 13.25z"></path>',
    plus: '<path d="M5 12h14"></path><path d="M12 5v14"></path>',
    printer: '<path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><path d="M6 14h12v8H6z"></path>',
    receipt: '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2z"></path><path d="M8 7h8"></path><path d="M8 11h8"></path><path d="M8 15h5"></path>',
    search: '<circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path>',
    save: '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><path d="M17 21v-8H7v8"></path><path d="M7 3v5h8"></path>',
    login: '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><path d="m10 17 5-5-5-5"></path><path d="M15 12H3"></path>',
    userPlus: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M19 8v6"></path><path d="M22 11h-6"></path>',
    users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
    wallet: '<path d="M20 7H5a2 2 0 0 1 0-4h12"></path><path d="M4 7v12a2 2 0 0 0 2 2h14V7"></path><path d="M16 14h.01"></path>',
    book: '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"></path>',
    building: '<path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path><path d="M9 9h1"></path><path d="M9 13h1"></path><path d="M9 17h1"></path>',
    calendar: '<path d="M8 2v4"></path><path d="M16 2v4"></path><path d="M3 10h18"></path><rect x="3" y="4" width="18" height="18" rx="2"></rect>',
    table: '<path d="M3 3h18v18H3z"></path><path d="M3 9h18"></path><path d="M3 15h18"></path><path d="M9 3v18"></path><path d="M15 3v18"></path>',
    edit: '<path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>',
    trash: '<path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path>',
    eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle>',
    eyeOff: '<path d="M10.73 5.08A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a13.2 13.2 0 0 1-2.16 3.19"></path><path d="M6.61 6.61A13.5 13.5 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.39-1.61"></path><path d="M2 2l20 20"></path><path d="M9.88 9.88A3 3 0 0 0 14.12 14.12"></path>',
    menu: '<path d="M4 6h16"></path><path d="M4 12h16"></path><path d="M4 18h16"></path>',
    panelLeft: '<rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M9 3v18"></path><path d="m16 15-3-3 3-3"></path>',
    chevronDown: '<path d="m6 9 6 6 6-6"></path>',
    mail: '<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"></path><path d="m22 6-10 7L2 6"></path>',
    settings: '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.5a2 2 0 0 1-1 1.72l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.09a2 2 0 0 1-1-1.72v-.5a2 2 0 0 1 1-1.72l.15-.1a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle>'
  };

  function renderIcon(name, className = "icon") {
    return `<svg class="${className}" data-rendered-icon="${name}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${icons[name] || icons.table}</svg>`;
  }

  function hydrateIcons() {
    document.querySelectorAll("[data-icon]").forEach((node) => {
      const name = node.dataset.icon;
      const className = node.dataset.iconClass || "icon";
      node.innerHTML = renderIcon(name, className);
    });
  }

  function setThemeIcon() {
    const isDark = document.documentElement.classList.contains("dark");
    document.querySelectorAll("[data-theme-toggle]").forEach((button) => {
      button.innerHTML = renderIcon(isDark ? "sun" : "moon");
      button.setAttribute("title", isDark ? "Switch to light mode" : "Switch to dark mode");
    });
  }

  function setupTheme() {
    const saved = localStorage.getItem(themeKey);
    const dark = saved ? saved === "dark" : window.matchMedia("(prefers-color-scheme: dark)").matches;
    document.documentElement.classList.toggle("dark", dark);
    setThemeIcon();

    document.querySelectorAll("[data-theme-toggle]").forEach((button) => {
      button.addEventListener("click", () => {
        const isDark = document.documentElement.classList.toggle("dark");
        localStorage.setItem(themeKey, isDark ? "dark" : "light");
        setThemeIcon();
      });
    });
  }

  function setupLoader() {
    const loader = document.querySelector("#pageLoader");
    if (!loader) return;
    window.setTimeout(() => {
      loader.classList.add("opacity-0", "pointer-events-none");
      window.setTimeout(() => loader.remove(), 260);
    }, 260);
  }

  function toast(type, message) {
    const toastStack = document.querySelector("#toastStack");
    if (!toastStack) return;
    const palette = {
      success: "border-green-200 bg-green-50 text-green-800 dark:border-green-500/30 dark:bg-green-500/15 dark:text-green-200",
      error: "border-red-200 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/15 dark:text-red-200",
      warning: "border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/15 dark:text-amber-200"
    };
    const node = document.createElement("div");
    node.className = `rounded-lg border p-4 text-sm font-bold shadow-xl ${palette[type] || palette.success}`;
    node.textContent = message;
    toastStack.appendChild(node);
    window.setTimeout(() => {
      node.style.opacity = "0";
      node.style.transform = "translateY(-8px)";
      node.style.transition = "opacity 180ms ease, transform 180ms ease";
      window.setTimeout(() => node.remove(), 220);
    }, 3000);
  }

  function openModal(markup) {
    const modalRoot = document.querySelector("#modalRoot");
    if (!modalRoot) return;
    modalRoot.innerHTML = markup;
    hydrateIcons();
    modalRoot.classList.remove("hidden");
    modalRoot.classList.add("flex");
    document.body.style.overflow = "hidden";
  }

  function closeModal() {
    const modalRoot = document.querySelector("#modalRoot");
    if (!modalRoot) return;
    modalRoot.classList.add("hidden");
    modalRoot.classList.remove("flex");
    modalRoot.innerHTML = "";
    document.body.style.overflow = "";
  }

  function bindModalEvents() {
    const modalRoot = document.querySelector("#modalRoot");
    modalRoot?.addEventListener("click", (event) => {
      if (event.target === modalRoot || event.target.closest("[data-close-modal]")) closeModal();
    });
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") closeModal();
    });
  }

  function bindAdminShell() {
    document.addEventListener("click", (event) => {
      if (event.target.closest("[data-admin-collapse]")) {
        document.body.classList.toggle("admin-collapsed");
      }
      if (event.target.closest("[data-admin-drawer-open]")) {
        document.body.classList.add("admin-drawer-open");
      }
      if (event.target.closest("[data-admin-drawer-close]")) {
        document.body.classList.remove("admin-drawer-open");
      }
      const groupToggle = event.target.closest("[data-admin-nav-toggle]");
      if (groupToggle) {
        groupToggle.closest(".admin-nav-group")?.classList.toggle("is-open");
      }
      const confirmButton = event.target.closest("[data-confirm]");
      if (confirmButton && !window.confirm(confirmButton.dataset.confirm || "Continue with this action?")) {
        event.preventDefault();
      }
      const submitButton = event.target.closest('button[type="submit"]');
      if (submitButton && submitButton.form && submitButton.form.checkValidity()) {
        submitButton.classList.add("is-busy");
      }
      const checkAll = event.target.closest("[data-check-all]");
      if (checkAll) {
        checkAll.closest("table")?.querySelectorAll("[data-check-row]").forEach((checkbox) => {
          checkbox.checked = checkAll.checked;
        });
      }
      const adminLink = event.target.closest('.admin-side-nav a, .admin-sidebar-footer a');
      if (adminLink) {
        document.body.classList.add("admin-loading");
      }
    });
    window.addEventListener("beforeunload", () => document.body.classList.add("admin-loading"));
    window.setTimeout(() => document.body.classList.remove("admin-loading"), 260);
  }

  function bindPasswordToggles() {
    document.addEventListener("click", (event) => {
      const button = event.target.closest("[data-password-toggle]");
      if (!button) return;
      const field = button.closest("[data-password-field]")?.querySelector("input");
      if (!field) return;
      const showing = field.type === "text";
      field.type = showing ? "password" : "text";
      button.innerHTML = renderIcon(showing ? "eye" : "eyeOff");
      button.setAttribute("aria-label", showing ? "Show password" : "Hide password");
      button.setAttribute("title", showing ? "Show password" : "Hide password");
      field.focus();
    });
  }

  window.FedpoffaUI = {
    closeModal,
    hydrateIcons,
    icon: renderIcon,
    openModal,
    toast
  };

  setupTheme();
  setupLoader();
  bindModalEvents();
  bindAdminShell();
  bindPasswordToggles();
  hydrateIcons();
})();
